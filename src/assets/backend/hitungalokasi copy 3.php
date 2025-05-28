<?php

session_start();

require_once('../../services/database.php');
include 'update_cmo_data.php';

// --- Validasi data sesi ---
if (empty($_SESSION['cmo_temp_data_update']) || empty($_SESSION['stock_temp_data']) || empty($_SESSION['allocation_settings'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data CMO, Stock, atau Pengaturan Alokasi tidak ditemukan di sesi. Mohon pastikan semua data telah dimuat sebelum menjalankan proses alokasi.']);
    exit;
}

$cmo_data = $_SESSION['cmo_temp_data_update'];
$stock_data = $_SESSION['stock_temp_data'];
$allocationsettings = $_SESSION['allocation_settings'];

// --- Inisialisasi variabel setting ---
$minCarton = (int)($allocationsettings['minCarton'] ?? 0); // Pastikan integer
$rangeFullCMO = (int)($allocationsettings['rangeFullCMO'] ?? 0); // Pastikan integer
$skuLimit = ($allocationsettings['SkuLimit'] ?? 0) == 0 ? PHP_INT_MAX : (int)($allocationsettings['SkuLimit'] ?? PHP_INT_MAX); // Pastikan integer
$MaxCMO = (int)($allocationsettings['MaxCMO'] ?? 0); // Pastikan ini integer

// --- Mapping stock untuk akses cepat (key: SKU_CHANNEL) ---
$stock_map = [];
foreach ($stock_data as $stock) {
    $key = $stock['SKU'] . '_' . $stock['CHANNEL'];
    $stock_map[$key] = max((int)($stock['Sisa QTY'] ?? 0), 0);
}

// --- Mapping mixcust (kapasitas truk, dll) ---
$mixcust_map = [];
$res_mix = mysqli_query($conn, "SELECT cust, mix_cust, shipsizemajalengka, channel_cust, pulau, shipment_type, region_scm FROM mixcust");
if (!$res_mix) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data mixcust: ' . mysqli_error($conn)]);
    exit;
}
while ($row = mysqli_fetch_assoc($res_mix)) {
    $mixcust_map[$row['cust']] = [
        'mix_cust' => $row['mix_cust'],
        'shipsize' => (float)($row['shipsizemajalengka'] ?? 0),
        'channel_cust' => $row['channel_cust'] ?? '',
        'pulau' => $row['pulau'] ?? '',
        'shipment_type' => $row['shipment_type'] ?? '',
        'region_scm' => $row['region_scm'] ?? ''
    ];
}

// --- Mapping volume per SKU (itembank) ---
$itembank_map = [];
$res_itembank = mysqli_query($conn, "SELECT sku, volume FROM itembank");
if (!$res_itembank) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data itembank: ' . mysqli_error($conn)]);
    exit;
}
while ($row = mysqli_fetch_assoc($res_itembank)) {
    $itembank_map[$row['sku']] = (float)($row['volume'] ?? 0);
}

// --- Inisialisasi array untuk hasil akhir dan data sementara per mixcust ---
$final_allocated_data = [];
$allocation_summary_per_mixcust = []; // Untuk melacak total volume yang dialokasikan per mixcust

// --- Persiapan data CMO dengan informasi tambahan ---
$cmo_processed_data = [];
foreach ($cmo_data as $row) {
    $cust = $row['CUST#'] ?? '';
    $sku = $row['SKU'] ?? '';
    $channel = $row['CHANNEL'] ?? '';
    $cmo_otd = (int)($row['OTD CMO'] ?? 0);
    $cmo_final_for_csl = max((int)($row['CMO FINAL'] ?? 1), 1);
    $actual_for_csl = (int)($row['ACTUAL'] ?? 0);
    $do_planning_for_csl = (int)($row['DO PLANNING'] ?? 0);
    $stock = $stock_map[$sku . '_' . $channel] ?? 0;
    $volume_per_ctn = $itembank_map[$sku] ?? 0;
    $csl = round((($actual_for_csl + $do_planning_for_csl) / $cmo_final_for_csl) * 100, 2);

    if (empty($cust) || empty($sku) || !isset($mixcust_map[$cust])) {
        continue;
    }

    $mix_cust = $mixcust_map[$cust]['mix_cust'];
    $shipsize = $mixcust_map[$cust]['shipsize'] ?? 0;

    // VALIDASI KRITIS: Pastikan Volume per CTN tidak NOL dan Shipsize tidak NOL
    // Jika salah satunya 0 atau kurang, SKU/Mixcust ini tidak bisa dialokasikan secara volume
    if ($volume_per_ctn <= 0 || $shipsize <= 0) {
        // Jika Anda ingin melihat SKU mana yang diskip, Anda bisa uncomment baris di bawah
        // error_log("Skipping SKU {$sku} for CUST {$cust} (MIXCUST {$mix_cust}): Volume per CTN ({$volume_per_ctn}) or Shipment Size ({$shipsize}) is zero or less.");
        continue;
    }

    $cmo_processed_data[] = [
        'MIXCUST' => $mix_cust,
        'CUST#' => $cust,
        'CUSTOMER - CITY/CHANNEL' => $row['CUSTOMER - CITY/CHANNEL'] ?? '',
        'CHANNEL CUST' => $mixcust_map[$cust]['channel_cust'] ?? '',
        'SKU' => $sku,
        'MATERIAL DESCRIPTION' => $row['MATERIAL DESCRIPTION'] ?? '',
        'CHANNEL' => $channel,
        'PULAU' => $mixcust_map[$cust]['pulau'] ?? '',
        'SHIPMENT TYPE' => $mixcust_map[$cust]['shipment_type'] ?? '',
        'REGION SCM' => $mixcust_map[$cust]['region_scm'] ?? '',
        'OTD CMO' => $cmo_otd,
        'CMO FINAL' => $cmo_final_for_csl,
        'ACTUAL' => (int)($row['ACTUAL'] ?? 0),
        'DO PLANNING' => (int)($row['DO PLANNING'] ?? 0),
        'SISA QTY' => $stock, // Ini adalah stock awal yang tidak berubah
        'Volume per CTN' => $volume_per_ctn,
        'SHIPMENT SIZE' => $shipsize,
        'CSL (%)' => $csl,
        'Final Allocated QTY' => 0, // Akan diisi di akhir
        'Allocated QTY Stage 1' => 0,
        'Allocated QTY Stage 2' => 0,
        'Allocated QTY Stage 3' => 0,
        'Allocated QTY Stage 4' => 0,
        'Total Allocated QTY' => 0, // Akumulasi dari semua stage
        'Used Volume (m3)' => 0, // Akumulasi volume
        'Volume Ratio (%)' => 0,
        'Truck Size' => 0
    ];

    // Inisialisasi total volume teralokasi per mixcust
    if (!isset($allocation_summary_per_mixcust[$mix_cust])) {
        $allocation_summary_per_mixcust[$mix_cust] = [
            'total_volume_allocated' => 0, // Volume yang sudah dialokasikan dari semua stage
            'shipment_size' => $shipsize,
            'remaining_shipment_size' => $shipsize, // Sisa kapasitas yang perlu diisi
            'current_sku_count' => 0,
            'allocated_skus' => [], // Untuk melacak SKU yang sudah dialokasikan per mixcust
            // Menambahkan key untuk melacak volume yang sudah dialokasikan per stage di level mixcust summary (opsional, untuk debug)
            'stage1_volume_allocated_per_mixcust' => 0,
            'stage2_volume_allocated_per_mixcust' => 0,
            'stage3_volume_allocated_per_mixcust' => 0,
            'stage4_volume_allocated_per_mixcust' => 0,
        ];
    }
}

// Mengurutkan data CMO processed untuk konsistensi alokasi (misalnya, berdasarkan CSL atau OTD CMO)
// Ini bisa diatur sesuai prioritas bisnis Anda. Contoh: CSL terkecil atau OTD CMO terkecil duluan.
usort($cmo_processed_data, function($a, $b) {
    // Contoh: Urutkan berdasarkan MIXCUST, lalu CSL terkecil, lalu OTD CMO terkecil
    if ($a['MIXCUST'] == $b['MIXCUST']) {
        if ($a['CSL (%)'] == $b['CSL (%)']) {
            return $a['OTD CMO'] <=> $b['OTD CMO'];
        }
        return $a['CSL (%)'] <=> $b['CSL (%)'];
    }
    return $a['MIXCUST'] <=> $b['MIXCUST'];
});


// --- Stage 1: Ambil SKU yang OTD CMO > minCarton dan < dari rangeFullCMO, ambil full OTD CMO ---
foreach ($cmo_processed_data as &$data) {
    $mix_cust = $data['MIXCUST'];
    
    // Pastikan mix_cust ada di summary dan masih ada kapasitas
    if (!isset($allocation_summary_per_mixcust[$mix_cust]) || $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'] <= 0) {
        continue;
    }

    $cmo_otd = $data['OTD CMO'];
    $stock_available = $data['SISA QTY']; // Ini stock awal, tidak berubah per stage
    $volume_per_ctn = $data['Volume per CTN'];

    $remaining_volume_needed_for_mixcust = $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'];
    $current_sku_count = $allocation_summary_per_mixcust[$mix_cust]['current_sku_count'];
    $current_total_allocated_qty_this_sku = $data['Total Allocated QTY'];

    // Hitung sisa OTD CMO dan stock setelah alokasi dari stage sebelumnya (saat ini 0)
    $remaining_otd_cmo_for_this_stage = $cmo_otd - $current_total_allocated_qty_this_sku;
    $remaining_stock_for_this_stage = $stock_available - $current_total_allocated_qty_this_sku;

    // Hanya jika masih ada kapasitas di shipment size dan belum mencapai SKU limit
    if ($remaining_volume_needed_for_mixcust > 0 && $current_sku_count < $skuLimit) {
        if ($remaining_otd_cmo_for_this_stage >= $minCarton && $remaining_stock_for_this_stage >= $minCarton) {
            $qty_to_allocate_tentative = min($remaining_otd_cmo_for_this_stage, $remaining_stock_for_this_stage);
            
            // Tambahan filter untuk Stage 1: OTD CMO harus < rangeFullCMO
            if ($cmo_otd < $rangeFullCMO) { // Menggunakan $cmo_otd asli untuk filter ini
                $volume_to_allocate_tentative = $qty_to_allocate_tentative * $volume_per_ctn;

                $actual_qty_allocated_this_sku = 0;
                $actual_volume_allocated_this_sku = 0;

                // Alokasikan hanya jika tidak melebihi sisa kapasitas shipment size
                if ($volume_to_allocate_tentative <= $remaining_volume_needed_for_mixcust) {
                    $actual_qty_allocated_this_sku = $qty_to_allocate_tentative;
                    $actual_volume_allocated_this_sku = $volume_to_allocate_tentative;
                } else {
                    // Alokasikan sebagian jika melebihi shipment size
                    $actual_qty_allocated_this_sku = floor($remaining_volume_needed_for_mixcust / $volume_per_ctn);
                    $actual_volume_allocated_this_sku = $actual_qty_allocated_this_sku * $volume_per_ctn;
                }

                if ($actual_qty_allocated_this_sku > 0) {
                    $data['Allocated QTY Stage 1'] = $actual_qty_allocated_this_sku;
                    $data['Total Allocated QTY'] += $actual_qty_allocated_this_sku;
                    $data['Used Volume (m3)'] += $actual_volume_allocated_this_sku;
                    
                    $allocation_summary_per_mixcust[$mix_cust]['total_volume_allocated'] += $actual_volume_allocated_this_sku;
                    $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'] -= $actual_volume_allocated_this_sku;
                    $allocation_summary_per_mixcust[$mix_cust]['stage1_volume_allocated_per_mixcust'] += $actual_volume_allocated_this_sku;

                    // Tambahkan SKU ke daftar yang sudah dialokasikan jika belum ada
                    if (!in_array($data['SKU'], $allocation_summary_per_mixcust[$mix_cust]['allocated_skus'])) {
                        $allocation_summary_per_mixcust[$mix_cust]['allocated_skus'][] = $data['SKU'];
                        $allocation_summary_per_mixcust[$mix_cust]['current_sku_count']++;
                    }
                }
            }
        }
    }
}
unset($data); // Unset reference


// --- Stage 2: Ambil SKU yang OTD CMO >= rangeFullCMO (full truck load) ---
// Serta kekurangan dari Stage 1 untuk SKU lainnya dengan CSL terkecil, dibatasi MaxCMO
foreach ($cmo_processed_data as &$data) {
    $mix_cust = $data['MIXCUST'];
    
    // Pastikan mix_cust ada di summary dan masih ada kapasitas
    if (!isset($allocation_summary_per_mixcust[$mix_cust]) || $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'] <= 0) {
        continue;
    }

    $cmo_otd = $data['OTD CMO'];
    $stock_available = $data['SISA QTY'];
    $volume_per_ctn = $data['Volume per CTN'];

    $remaining_volume_needed_for_mixcust = $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'];
    $current_sku_count = $allocation_summary_per_mixcust[$mix_cust]['current_sku_count'];
    $current_total_allocated_qty_this_sku = $data['Total Allocated QTY'];

    $remaining_otd_cmo_for_this_stage = $cmo_otd - $current_total_allocated_qty_this_sku;
    $remaining_stock_for_this_stage = $stock_available - $current_total_allocated_qty_this_sku;

    if ($remaining_volume_needed_for_mixcust > 0 && $current_sku_count < $skuLimit) {
        if ($remaining_otd_cmo_for_this_stage >= $minCarton && $remaining_stock_for_this_stage >= $minCarton) {
            
            $qty_to_allocate_tentative = 0;
            // Kriteria Stage 2: Ambil SKU yang OTD CMO >= rangeFullCMO (prioritas full load)
            // ATAU, jika masih ada kapasitas dan SKU belum terisi penuh, alokasikan sisa dengan batas MaxCMO
            if ($cmo_otd >= $rangeFullCMO) { // Menggunakan $cmo_otd asli untuk filter ini
                $qty_to_allocate_tentative = min($remaining_otd_cmo_for_this_stage, $remaining_stock_for_this_stage);
            } else {
                // Untuk SKU yang tidak masuk kriteria full CMO, alokasikan sisa dengan batasan MaxCMO
                $qty_to_allocate_tentative = min($remaining_otd_cmo_for_this_stage, $remaining_stock_for_this_stage, $MaxCMO);
            }
            
            // Pastikan qty_to_allocate_tentative tidak negatif
            $qty_to_allocate_tentative = max(0, $qty_to_allocate_tentative);

            $volume_to_allocate_tentative = $qty_to_allocate_tentative * $volume_per_ctn;

            $actual_qty_allocated_this_sku = 0;
            $actual_volume_allocated_this_sku = 0;

            if ($volume_to_allocate_tentative <= $remaining_volume_needed_for_mixcust) {
                $actual_qty_allocated_this_sku = $qty_to_allocate_tentative;
                $actual_volume_allocated_this_sku = $volume_to_allocate_tentative;
            } else {
                $actual_qty_allocated_this_sku = floor($remaining_volume_needed_for_mixcust / $volume_per_ctn);
                $actual_volume_allocated_this_sku = $actual_qty_allocated_this_sku * $volume_per_ctn;
            }
            
            if ($actual_qty_allocated_this_sku > 0) {
                $data['Allocated QTY Stage 2'] = $actual_qty_allocated_this_sku;
                $data['Total Allocated QTY'] += $actual_qty_allocated_this_sku;
                $data['Used Volume (m3)'] += $actual_volume_allocated_this_sku;
                
                $allocation_summary_per_mixcust[$mix_cust]['total_volume_allocated'] += $actual_volume_allocated_this_sku;
                $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'] -= $actual_volume_allocated_this_sku;
                $allocation_summary_per_mixcust[$mix_cust]['stage2_volume_allocated_per_mixcust'] += $actual_volume_allocated_this_sku;

                // Tambahkan SKU ke daftar yang sudah dialokasikan jika belum ada
                if (!in_array($data['SKU'], $allocation_summary_per_mixcust[$mix_cust]['allocated_skus'])) {
                    $allocation_summary_per_mixcust[$mix_cust]['allocated_skus'][] = $data['SKU'];
                    $allocation_summary_per_mixcust[$mix_cust]['current_sku_count']++;
                }
            }
        }
    }
}
unset($data); // Unset reference



// --- Stage 3: Setelah mendapatkan stage 1 + stage 2 <> shipmentsize atau masih ada kekurangan ---
// maka lanjutkan ke stage 3 dengan menghitung kekurangan dari s1 dan s2 tadi
// dengan meempertimbangkan min ctn dan max sku limit dari total 1 sampai 3 jadikan key baru
foreach ($cmo_processed_data as &$data) {
    $mix_cust = $data['MIXCUST'];

    if (!isset($allocation_summary_per_mixcust[$mix_cust])) {
        continue;
    }

    $remaining_volume_needed_for_mixcust = $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'];
    $current_sku_count = $allocation_summary_per_mixcust[$mix_cust]['current_sku_count'];
    $sku = $data['SKU'];
    $cmo_otd = $data['OTD CMO'];
    $stock = $data['SISA QTY'];
    $volume_per_ctn = $data['Volume per CTN'];

    // Jika target shipment size belum tercapai dan masih ada kapasitas SKU
    if ($remaining_volume_needed_for_mixcust > 0 && $current_sku_count < $skuLimit) {
        // Sisa OTD CMO dan Stock setelah alokasi sebelumnya (Stage 1 + Stage 2)
        $current_allocated_qty_this_sku = $data['Total Allocated QTY'];
        $remaining_otd_cmo = $cmo_otd - $current_allocated_qty_this_sku;
        $remaining_stock = $stock - $current_allocated_qty_this_sku;

        if ($remaining_otd_cmo >= $minCarton && $remaining_stock >= $minCarton) {
            $qty_to_allocate_tentative = min($remaining_otd_cmo, $remaining_stock); // Tidak dibatasi MaxCMO lagi
            $volume_to_allocate_tentative = $qty_to_allocate_tentative * $volume_per_ctn;

            $actual_volume_allocated_this_sku = 0;
            $actual_qty_allocated_this_sku = 0;

            if ($volume_to_allocate_tentative <= $remaining_volume_needed_for_mixcust) {
                $actual_volume_allocated_this_sku = $volume_to_allocate_tentative;
                $actual_qty_allocated_this_sku = $qty_to_allocate_tentative;
            } else {
                $actual_qty_allocated_this_sku = floor($remaining_volume_needed_for_mixcust / $volume_per_ctn);
                $actual_volume_allocated_this_sku = $actual_qty_allocated_this_sku * $volume_per_ctn;
            }
            
            if ($actual_qty_allocated_this_sku > 0) {
                $data['Allocated QTY Stage 3'] = $actual_qty_allocated_this_sku;
                $data['Total Allocated QTY'] += $actual_qty_allocated_this_sku;
                $data['Used Volume (m3)'] += $actual_volume_allocated_this_sku;
                
                $allocation_summary_per_mixcust[$mix_cust]['total_volume_allocated'] += $actual_volume_allocated_this_sku;
                $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'] -= $actual_volume_allocated_this_sku;
                $allocation_summary_per_mixcust[$mix_cust]['stage3_volume_allocated'] += $actual_volume_allocated_this_sku;

                // Tambahkan SKU ke daftar yang sudah dialokasikan jika belum ada
                if (!in_array($sku, $allocation_summary_per_mixcust[$mix_cust]['allocated_skus'])) {
                    $allocation_summary_per_mixcust[$mix_cust]['allocated_skus'][] = $sku;
                    $allocation_summary_per_mixcust[$mix_cust]['current_sku_count']++;
                }
            }
        }
    }
}
unset($data); // Unset reference



// --- Stage 4: Lepaskan semua limiter kecuali minCarton untuk mengisi kekurangan total ---
foreach ($cmo_processed_data as &$data) {
    $mix_cust = $data['MIXCUST'];

    if (!isset($allocation_summary_per_mixcust[$mix_cust]) || $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'] <= 0) {
        continue;
    }

    $cmo_otd = $data['OTD CMO'];
    $stock_available = $data['SISA QTY'];
    $volume_per_ctn = $data['Volume per CTN'];

    $remaining_volume_needed_for_mixcust = $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'];
    $current_total_allocated_qty_this_sku = $data['Total Allocated QTY'];

    $remaining_otd_cmo_for_this_stage = $cmo_otd - $current_total_allocated_qty_this_sku;
    $remaining_stock_for_this_stage = $stock_available - $current_total_allocated_qty_this_sku;

    // Di Stage 4, skuLimit diabaikan (hanya fokus mengisi volume)
    if ($remaining_volume_needed_for_mixcust > 0) {
        if ($remaining_otd_cmo_for_this_stage >= $minCarton && $remaining_stock_for_this_stage >= $minCarton) {
            $qty_to_allocate_tentative = min($remaining_otd_cmo_for_this_stage, $remaining_stock_for_this_stage);
            
            // Pastikan qty_to_allocate_tentative tidak negatif
            $qty_to_allocate_tentative = max(0, $qty_to_allocate_tentative);

            $volume_to_allocate_tentative = $qty_to_allocate_tentative * $volume_per_ctn;

            $actual_qty_allocated_this_sku = 0;
            $actual_volume_allocated_this_sku = 0;

            if ($volume_to_allocate_tentative <= $remaining_volume_needed_for_mixcust) {
                $actual_qty_allocated_this_sku = $qty_to_allocate_tentative;
                $actual_volume_allocated_this_sku = $volume_to_allocate_tentative;
            } else {
                $actual_qty_allocated_this_sku = floor($remaining_volume_needed_for_mixcust / $volume_per_ctn);
                $actual_volume_allocated_this_sku = $actual_qty_allocated_this_sku * $volume_per_ctn;
            }
            
            if ($actual_qty_allocated_this_sku > 0) {
                $data['Allocated QTY Stage 4'] = $actual_qty_allocated_this_sku;
                $data['Total Allocated QTY'] += $actual_qty_allocated_this_sku;
                $data['Used Volume (m3)'] += $actual_volume_allocated_this_sku;
                
                $allocation_summary_per_mixcust[$mix_cust]['total_volume_allocated'] += $actual_volume_allocated_this_sku;
                $allocation_summary_per_mixcust[$mix_cust]['remaining_shipment_size'] -= $actual_volume_allocated_this_sku;
                $allocation_summary_per_mixcust[$mix_cust]['stage4_volume_allocated_per_mixcust'] += $actual_volume_allocated_this_sku;
            }
        }
    }
}
unset($data); // Unset reference


// --- Finalisasi perhitungan untuk output dan mengisi 'Final Allocated QTY' ---
foreach ($cmo_processed_data as &$data) {
    $mix_cust = $data['MIXCUST'];
    
    if (!isset($allocation_summary_per_mixcust[$mix_cust])) {
        continue;
    }

    $shipsize = $allocation_summary_per_mixcust[$mix_cust]['shipment_size'];
    $final_volume = $data['Used Volume (m3)'];

    // SALIN NILAI AKHIR DARI 'Total Allocated QTY' KE 'Final Allocated QTY'
    $data['Final Allocated QTY'] = $data['Total Allocated QTY'];

    // Hitung volume ratio (jika shipsize > 0)
    $data['Volume Ratio (%)'] = $shipsize > 0 ? round(($final_volume / $shipsize) * 100, 2) : 0;

    // Hitung jumlah truk (truck size) berdasarkan shipsize
    $data['Truck Size'] = $shipsize > 0 ? ceil($final_volume / $shipsize) : 0;
}
unset($data); // Unset reference

$_SESSION['allocation_result'] = $cmo_processed_data;

// --- Output hasil JSON ---
// echo json_encode([
//     'status' => 'success',
//     'data' => $cmo_processed_data,
//     'summary' => $allocation_summary_per_mixcust
// ]);

?>