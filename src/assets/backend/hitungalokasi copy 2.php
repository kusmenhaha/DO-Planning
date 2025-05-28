<?php
session_start();

// Asumsi path file ini relatif terhadap file yang memanggilnya
require_once('../../services/database.php');
include 'update_cmo_data.php';

// Pastikan semua data sesi yang diperlukan ada
if (empty($_SESSION['cmo_temp_data_update']) || empty($_SESSION['stock_temp_data']) || empty($_SESSION['allocation_settings'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data CMO, Stock, atau Pengaturan Alokasi tidak ditemukan di sesi. Mohon pastikan semua data telah dimuat sebelum menjalankan proses alokasi.']);
    exit;
}

$cmo_data = $_SESSION['cmo_temp_data_update'];
$stock_data = $_SESSION['stock_temp_data'];
$allocationsettings = $_SESSION['allocation_settings'];

// Ambil pengaturan alokasi
$minCarton = $allocationsettings['minCarton'] ?? 0;
$rangeFullCMO = (int)($allocationsettings['rangeFullCMO'] ?? 0);
// Jika SkuLimit diatur 0, artinya tidak ada batasan SKU, maka set ke PHP_INT_MAX
$maxTotalSkuPerMixCust = ($allocationsettings['SkuLimit'] ?? 0) == 0 ? PHP_INT_MAX : (int)($allocationsettings['SkuLimit'] ?? PHP_INT_MAX);
$maxCMOPercentageAllocationForNonFullCMO = ($allocationsettings['MaxCMO'] ?? 0); // Asumsi ini dalam format desimal (misal 0.2 untuk 20%)

// Define a small epsilon for floating point comparison
// Ini berarti jika selisih antara shipsize dan current_volume kurang dari 0.01,
// kita akan menganggap volume sudah mencukupi.
$epsilon = 0.01; 

// Buat map untuk akses cepat data stock
$stock_map = [];
foreach ($stock_data as $stock) {
    $key = $stock['SKU'] . '_' . $stock['CHANNEL'];
    $stock_map[$key] = max((int)($stock['Sisa QTY'] ?? 0), 0);
}

// Buat map untuk akses cepat data mixcust
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

// Buat map untuk akses cepat data itembank (volume)
$itembank_map = [];
$res_itembank = mysqli_query($conn, "SELECT sku, volume FROM itembank");
if (!$res_itembank) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data itembank: ' . mysqli_error($conn)]);
    exit;
}
while ($row = mysqli_fetch_assoc($res_itembank)) {
    $itembank_map[$row['sku']] = (float)($row['volume'] ?? 0);
}

$skus_by_mixcust = [];

// ==============================================================================
// Bagian 3: Inisialisasi Potensi Alokasi Awal dan Hitung CSL
// Ini adalah fase di mana kita menentukan potensi alokasi awal untuk setiap SKU
// berdasarkan min(OTD CMO, Stock) dan batasan minCarton awal.
// ==============================================================================
foreach ($cmo_data as $row) {
    $cust = $row['CUST#'] ?? '';
    $sku = $row['SKU'] ?? '';
    $channel = $row['CHANNEL'] ?? '';
    $cmo_otd = (int)($row['OTD CMO'] ?? 0);

    // Hitung CSL (Customer Service Level)
    $cmo_final_for_csl = max((int)($row['CMO FINAL'] ?? 0), 1); // Hindari pembagian nol
    $actual_for_csl = (int)($row['ACTUAL'] ?? 0);
    $do_planning_for_csl = (int)($row['DO PLANNING'] ?? 0);
    $csl = round((($actual_for_csl + $do_planning_for_csl) / $cmo_final_for_csl) * 100, 2);

    // Lewati jika data kunci tidak valid
    if (empty($cust) || empty($sku) || !isset($mixcust_map[$cust]) || !isset($itembank_map[$sku])) {
        continue;
    }

    $mix_cust = $mixcust_map[$cust]['mix_cust'];
    $volume_per_ctn = $itembank_map[$sku];
    $stock = $stock_map[$sku . '_' . $channel] ?? 0;

    $potential_alloc_qty = 0;
    // Aturan A: Totalkan semua OTD CMO vs STOCK kecuali angka yang < dari min ctn.
    // Ini berarti, jika min(OTD CMO, Stock) >= minCarton, maka ini adalah potensi alokasi awal.
    // Jika minCarton adalah 0, maka semua yang min(OTD CMO, Stock) > 0 eligible.
    if ($minCarton == 0 || (min($cmo_otd, $stock) >= $minCarton)) {
        $potential_alloc_qty = min($cmo_otd, $stock);
    }
    
    // Lewati jika volume per karton tidak valid atau alokasi awal nol (setelah filter minCarton)
    if ($volume_per_ctn <= 0 || $potential_alloc_qty <= 0) {
        continue;
    }

    if (!isset($skus_by_mixcust[$mix_cust])) {
        $skus_by_mixcust[$mix_cust] = [];
    }

    $skus_by_mixcust[$mix_cust][] = [
        'row' => $row,
        'cust' => $cust,
        'sku' => $sku,
        'channel' => $channel,
        'mix_cust' => $mix_cust,
        'volume_per_ctn' => $volume_per_ctn,
        'alloc_qty' => $potential_alloc_qty, // Ini adalah alokasi awal berdasarkan min(CMO, Stock) & minCarton
        'used_volume' => $potential_alloc_qty * $volume_per_ctn,
        'cmo' => $cmo_otd,
        'stock' => $stock,
        'csl' => $csl,
        'final_cmo_for_csl' => $cmo_final_for_csl,
        // Flag untuk aturan B.2: Qty > Minctn tapi kurang dari rangeFullCMO, kirim full OTD
        'is_priority_cmo_full_range' => ($cmo_otd >= $minCarton && $cmo_otd < $rangeFullCMO), // HANYA berdasarkan CMO OTD
    ];
}

$final_allocated_data = [];

// ==============================================================================
// Bagian 4: Proses Alokasi Per Mixcust
// Iterasi melalui setiap mixcust untuk melakukan alokasi sesuai aturan.
// ==============================================================================
foreach ($skus_by_mixcust as $mix_cust_id => $entries_for_processing) {
    $shipsize = $mixcust_map[$mix_cust_id]['shipsize'] ?? 0;

    // --- Log Awal untuk Debugging ---
    error_log("--- Processing Mixcust: " . $mix_cust_id . " ---");
    error_log("SHIPMENT SIZE for " . $mix_cust_id . ": " . $shipsize . " m3");


    // Jika shipment size adalah 0 atau tidak ada SKU untuk mixcust ini, lewati proses alokasi utama.
    // Namun, jika ada alokasi awal (potential_alloc_qty > 0), tetap masukkan ke output final.
    if ($shipsize <= 0 || empty($entries_for_processing)) {
        error_log("Skipping allocation process for " . $mix_cust_id . " due to shipsize <= 0 or no eligible SKUs.");
        foreach ($entries_for_processing as $entry) {
            if ($entry['alloc_qty'] > 0) {
                $final_qty = min($entry['alloc_qty'], $entry['cmo'], $entry['stock']);
                // Final validasi minCarton
                if ($minCarton > 0 && $final_qty > 0 && $final_qty < $minCarton) {
                     $final_qty = 0;
                }
                $final_volume = $final_qty * $entry['volume_per_ctn'];

                if ($final_qty > 0) {
                     $csl_output = 0;
                     if ($entry['final_cmo_for_csl'] > 0) {
                         $csl_output = round(((($entry['row']['ACTUAL'] ?? 0) + ($entry['row']['DO PLANNING'] ?? 0)) / $entry['final_cmo_for_csl']) * 100, 2);
                     }
                     $final_allocated_data[] = [
                         'MIXCUST' => $entry['mix_cust'],
                         'CUST#' => $entry['cust'],
                         'CUSTOMER - CITY/CHANNEL' => $entry['row']['CUSTOMER - CITY/CHANNEL'] ?? '',
                         'CHANNEL CUST' => $mixcust_map[$entry['cust']]['channel_cust'] ?? '',
                         'SKU' => $entry['sku'],
                         'MATERIAL DESCRIPTION' => $entry['row']['MATERIAL DESCRIPTION'] ?? '',
                         'CHANNEL' => $entry['channel'],
                         'PULAU' => $mixcust_map[$entry['cust']]['pulau'] ?? '',
                         'SHIPMENT TYPE' => $mixcust_map[$entry['cust']]['shipment_type'] ?? '',
                         'REGION SCM' => $mixcust_map[$entry['cust']]['region_scm'] ?? '',
                         'OTD CMO' => $entry['cmo'],
                         'CMO FINAL' => $entry['final_cmo_for_csl'],
                         'ACTUAL' => (int)($entry['row']['ACTUAL'] ?? 0),
                         'DO PLANNING' => (int)($entry['row']['DO PLANNING'] ?? 0),
                         'SISA QTY' => $entry['stock'],
                         'Final Allocated QTY' => $final_qty,
                         'Volume per CTN' => $entry['volume_per_ctn'],
                         'Used Volume (m3)' => round($final_volume, 2),
                         'SHIPMENT SIZE' => $shipsize,
                         'Volume Ratio (%)' => 0, // Karena shipsize 0 atau tidak ada SKU
                         'Truck Size' => 0, // Karena shipsize 0 atau tidak ada SKU
                         'CSL (%)' => $csl_output
                     ];
                }
            }
        }
        continue; // Lanjut ke mixcust berikutnya
    }

    // Buat referensi map untuk memanipulasi entry secara langsung
    $current_mixcust_entries = array_map(function($item) { return $item; }, $entries_for_processing);
    $allocated_skus_ref_map = [];
    foreach ($current_mixcust_entries as $key => &$entry) {
        $allocated_skus_ref_map[$entry['sku'] . '_' . $entry['channel']] = &$entry;
    }
    unset($entry); // Putuskan referensi terakhir

    // Hitung total volume awal dari semua yang sudah eligible dan diinisialisasi
    $total_volume_current = 0;
    $current_sku_count = 0;
    $processed_skus_for_shipment = []; // Melacak SKU unik yang sudah masuk shipment
    
    foreach ($allocated_skus_ref_map as $sku_channel_key => $entry) {
        if ($entry['alloc_qty'] > 0) {
            $total_volume_current += $entry['used_volume'];
            if (!in_array($entry['sku'], $processed_skus_for_shipment)) {
                 $processed_skus_for_shipment[] = $entry['sku'];
                 $current_sku_count++;
            }
        }
    }
    // --- Log Setelah Inisialisasi ---
    error_log("Initial total_volume_current for " . $mix_cust_id . ": " . $total_volume_current . " m3");


    // ==============================================================================
    // Langkah 1: Kurangi jika total volume melebihi shipment size (Aturan B.4, note)
    // ==============================================================================
    if ($total_volume_current > $shipsize) {
        error_log("Langkah 1: Reducing excess for " . $mix_cust_id . ". Excess: " . ($total_volume_current - $shipsize) . " m3");
        $excess_volume = $total_volume_current - $shipsize;

        $skus_to_reduce = [];
        foreach ($allocated_skus_ref_map as $sku_channel_key => &$entry) {
            // Hanya kurangi yang BUKAN prioritas rangeFullCMO dan punya alokasi > 0
            if (!$entry['is_priority_cmo_full_range'] && $entry['alloc_qty'] > 0) {
                $skus_to_reduce[] = &$entry;
            }
        }
        unset($entry); // Putuskan referensi terakhir

        // Urutkan CSL terbesar duluan untuk pengurangan
        usort($skus_to_reduce, function($a, $b) {
            return $b['csl'] <=> $a['csl'];
        });

        foreach ($skus_to_reduce as &$entry) {
            if ($excess_volume <= $epsilon) break; // Berhenti jika kelebihan sudah hampir nol (gunakan epsilon)

            // Batas minimal alokasi setelah pengurangan: tidak boleh kurang dari minCarton
            $min_alloc_floor_for_reduction = ($minCarton > 0) ? $minCarton : 0;

            // Jumlah karton yang bisa dikurangi dari SKU ini
            $qty_can_be_reduced = $entry['alloc_qty'] - $min_alloc_floor_for_reduction;
            $qty_can_be_reduced = max(0, $qty_can_be_reduced); // Pastikan tidak negatif

            if ($qty_can_be_reduced > 0 && $entry['volume_per_ctn'] > 0) {
                $qty_to_reduce_from_excess_volume = floor($excess_volume / $entry['volume_per_ctn']);
                $qty_actual_reduce = min($qty_can_be_reduced, $qty_to_reduce_from_excess_volume);

                $entry['alloc_qty'] -= $qty_actual_reduce;
                $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                $excess_volume -= ($qty_actual_reduce * $entry['volume_per_ctn']);
            }

            // Setelah pengurangan: Jika alokasi menjadi > 0 tapi < minCarton, nolkan (jika bukan priority)
            if ($minCarton > 0 && $entry['alloc_qty'] > 0 && $entry['alloc_qty'] < $minCarton && !$entry['is_priority_cmo_full_range']) {
                 $excess_volume += $entry['used_volume']; // Kembalikan volume ke excess
                 $entry['alloc_qty'] = 0;
                 $entry['used_volume'] = 0;
                 // Jika SKU ini dinolkan, perbarui hitungan SKU unik
                 $sku_index = array_search($entry['sku'], $processed_skus_for_shipment);
                 if ($sku_index !== false) {
                     unset($processed_skus_for_shipment[$sku_index]);
                     $processed_skus_for_shipment = array_values($processed_skus_for_shipment);
                     $current_sku_count--;
                 }
            }
        }
        unset($entry); // Putuskan referensi terakhir

        // Re-calculate total volume and SKU count after reduction
        $total_volume_current = 0;
        $current_sku_count = 0;
        $processed_skus_for_shipment = [];
        foreach ($allocated_skus_ref_map as $key => $entry) {
            if ($entry['alloc_qty'] > 0) {
                $total_volume_current += $entry['used_volume'];
                if (!in_array($entry['sku'], $processed_skus_for_shipment)) {
                    $processed_skus_for_shipment[] = $entry['sku'];
                    $current_sku_count++;
                }
            }
        }
        // --- Log Setelah Langkah 1 ---
        error_log("After Step 1, total_volume_current for " . $mix_cust_id . ": " . $total_volume_current . " m3");
    } else {
        error_log("Langkah 1 skipped for " . $mix_cust_id . ": total_volume_current (" . $total_volume_current . ") <= shipment_size (" . $shipsize . ")");
    }


    // ==============================================================================
    // Langkah 2: Alokasi Proporsional dengan Batasan (Aturan B.4)
    // ==============================================================================
    // Gunakan epsilon untuk membandingkan
    if (($shipsize - $total_volume_current) > $epsilon) {
        error_log("Langkah 2: Proportional allocation with limits for " . $mix_cust_id . ". Remaining: " . ($shipsize - $total_volume_current) . " m3");
        $remaining_volume_to_fill = $shipsize - $total_volume_current;

        $eligible_for_proportional_with_limits = [];
        foreach ($allocated_skus_ref_map as $sku_channel_key => &$entry) {
            // Hanya SKU yang alokasinya kurang dari min(cmo, stock) awal
            // dan BUKAN prioritas rangeFullCMO
            if (!$entry['is_priority_cmo_full_range'] && $entry['alloc_qty'] < min($entry['cmo'], $entry['stock'])) {
                $eligible_for_proportional_with_limits[] = &$entry;
            }
        }
        unset($entry); // Putuskan referensi terakhir

        // Urutkan CSL terkecil duluan untuk penambahan
        usort($eligible_for_proportional_with_limits, function($a, $b) {
            return $a['csl'] <=> $b['csl'];
        });

        foreach ($eligible_for_proportional_with_limits as &$entry) {
            if ($remaining_volume_to_fill <= $epsilon) break; // Berhenti jika sisa volume sudah hampir nol (gunakan epsilon)

            // Batasan max total SKU per mixcust
            if ($current_sku_count >= $maxTotalSkuPerMixCust && !in_array($entry['sku'], $processed_skus_for_shipment)) {
                // --- Log Skipped by SkuLimit ---
                // error_log("Skipped SKU " . $entry['sku'] . " for " . $mix_cust_id . " in Step 2: SkuLimit reached.");
                continue;
            }

            $current_alloc_qty_for_sku = $entry['alloc_qty'];
            $available_from_initial_potential = min($entry['cmo'], $entry['stock']) - $current_alloc_qty_for_sku;

            if ($available_from_initial_potential <= 0) continue;

            // Terapkan maxCMOPercentageAllocationForNonFullCMO dari OTD CMO total SKU
            $max_qty_allowed_by_cmo_percent = floor($entry['cmo'] * $maxCMOPercentageAllocationForNonFullCMO);
            $qty_can_add_considering_percent = $max_qty_allowed_by_cmo_percent - $current_alloc_qty_for_sku;
            $qty_can_add_considering_percent = max(0, $qty_can_add_considering_percent); // Pastikan tidak negatif

            $qty_to_add_this_pass = min($available_from_initial_potential, $qty_can_add_considering_percent);

            if ($qty_to_add_this_pass <= 0) continue;

            // Hitung berapa banyak karton yang bisa ditambahkan untuk memenuhi defisit
            $qty_can_add_by_volume = floor($remaining_volume_to_fill / $entry['volume_per_ctn']);
            $qty_actual_add = min($qty_to_add_this_pass, $qty_can_add_by_volume);

            // Periksa MinCarton: Jika alokasi awal 0, dan penambahan masih < minCarton, coba capai minCarton dulu
            if ($minCarton > 0 && $current_alloc_qty_for_sku == 0 && $qty_actual_add > 0 && $qty_actual_add < $minCarton) {
                if ($available_from_initial_potential >= $minCarton && ($minCarton * $entry['volume_per_ctn']) <= $remaining_volume_to_fill) {
                    $qty_actual_add = $minCarton;
                } else {
                    $qty_actual_add = 0; // Tidak bisa dialokasikan jika tidak bisa mencapai minCarton
                }
            }

            if ($qty_actual_add > 0) {
                $entry['alloc_qty'] += $qty_actual_add;
                $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                $total_volume_current += ($qty_actual_add * $entry['volume_per_ctn']);
                $remaining_volume_to_fill -= ($qty_actual_add * $entry['volume_per_ctn']);
                // Update SKU count jika SKU baru ditambahkan
                if (!in_array($entry['sku'], $processed_skus_for_shipment)) {
                    $processed_skus_for_shipment[] = $entry['sku'];
                    $current_sku_count++;
                }
            }
        }
        unset($entry); // Putuskan referensi terakhir

        // --- Log Setelah Langkah 2 ---
        error_log("After Step 2, total_volume_current for " . $mix_cust_id . ": " . $total_volume_current . " m3");
    } else {
        error_log("Langkah 2 skipped for " . $mix_cust_id . ": total_volume_current (" . $total_volume_current . ") is sufficient (>= " . ($shipsize - $epsilon) . ")");
    }


    // ==============================================================================
    // Langkah 3: EndFixAdjust (Aturan B.5)
    // Mengejar Kekurangan Hingga ShipmentSize.
    // Di fase ini, kita melonggarkan maxCMOPercentageAllocationForNonFullCMO dan maxTotalSkuPerMixCust.
    // Hanya `minCarton` (untuk alokasi baru) dan `min(cmo, stock)` yang menjadi batasan.
    // ==============================================================================
    // Gunakan epsilon untuk membandingkan
    if (($shipsize - $total_volume_current) > $epsilon) {
        error_log("Langkah 3 (EndFixAdjust): Initiated for " . $mix_cust_id . ". Deficit: " . ($shipsize - $total_volume_current) . " m3");
        $deficit_fix = $shipsize - $total_volume_current;
        $max_fix_adj_iterations = 10000; // Tingkatkan iterasi untuk memastikan lebih banyak upaya
        $fix_adj_iteration_count = 0;

        while ($deficit_fix > $epsilon && $fix_adj_iteration_count < $max_fix_adj_iterations) { // Gunakan epsilon di sini juga
            $fix_adj_iteration_count++;
            $fix_adjustment_made = false; // Reset per iterasi while

            $eligible_for_fix_adjustment_current_loop = [];
            foreach ($allocated_skus_ref_map as $sku_channel_key => &$entry) {
                // SKU eligible jika masih ada sisa dari min(cmo, stock) DAN volume per karton > 0
                if ($entry['alloc_qty'] < min($entry['cmo'], $entry['stock']) &&
                    $entry['volume_per_ctn'] > 0) {
                    $eligible_for_fix_adjustment_current_loop[] = &$entry;
                }
            }
            unset($entry); // Putuskan referensi terakhir

            // Urutkan berdasarkan CSL terkecil (atau kriteria lain jika diperlukan)
            usort($eligible_for_fix_adjustment_current_loop, function($a, $b) {
                return $a['csl'] <=> $b['csl']; // Tetap prioritaskan CSL terkecil
            });

            if (empty($eligible_for_fix_adjustment_current_loop)) {
                error_log("EndFixAdjust: No more eligible SKUs to add for " . $mix_cust_id);
                break; // Tidak ada lagi SKU yang bisa di-fix-adjust
            }

            foreach ($eligible_for_fix_adjustment_current_loop as &$entry) {
                if ($deficit_fix <= $epsilon) break; // Berhenti jika defisit sudah hampir nol (gunakan epsilon)

                $current_alloc_qty = $entry['alloc_qty'];
                $available_to_add = min($entry['cmo'], $entry['stock']) - $current_alloc_qty;

                if ($available_to_add <= 0) {
                    continue; // Tidak ada lagi yang bisa ditambahkan untuk SKU ini
                }

                $qty_to_add_fix = 0;

                // Prioritaskan mengisi hingga minCarton jika saat ini 0 atau di bawah minCarton
                // Ini memastikan SKU yang baru ditambahkan memenuhi minCarton
                if ($minCarton > 0 && $current_alloc_qty < $minCarton) {
                    $required_to_reach_min = $minCarton - $current_alloc_qty;
                    if ($required_to_reach_min > 0 &&
                        $available_to_add >= $required_to_reach_min &&
                        ($required_to_reach_min * $entry['volume_per_ctn']) <= $deficit_fix) {
                        $qty_to_add_fix = $required_to_reach_min;
                    }
                }

                // Jika masih butuh penambahan atau sudah di atas minCarton, coba tambahkan 1 karton.
                // Logika ini hanya berjalan jika qty_to_add_fix belum ditentukan oleh minCarton.
                if ($qty_to_add_fix == 0 && $deficit_fix > 0) {
                    $potential_add_qty_per_step = 1;

                    // Jika alokasi saat ini 0, dan penambahan 1 karton tidak mencapai minCarton,
                    // coba tambahkan langsung minCarton. Jika tidak bisa, lewati SKU ini.
                    if ($minCarton > 0 && $current_alloc_qty == 0 && $potential_add_qty_per_step < $minCarton) {
                        if ($available_to_add >= $minCarton && ($minCarton * $entry['volume_per_ctn']) <= $deficit_fix) {
                            $qty_to_add_fix = $minCarton;
                        } else {
                            continue; // Tidak bisa mencapai minCarton, lewati SKU ini untuk penambahan 1 karton
                        }
                    }
                    // Jika alokasi sudah ada (>0), atau minCarton adalah 0, atau penambahan 1 karton sudah cukup
                    // untuk mencapai/melebihi minCarton, maka izinkan penambahan 1 karton.
                    else if ($available_to_add >= $potential_add_qty_per_step &&
                               ($potential_add_qty_per_step * $entry['volume_per_ctn']) <= $deficit_fix) {
                        $qty_to_add_fix = $potential_add_qty_per_step;
                    }
                }

                // Lakukan penambahan jika qty_to_add_fix sudah ditentukan
                if ($qty_to_add_fix > 0) {
                    $entry['alloc_qty'] += $qty_to_add_fix;
                    $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                    $deficit_fix -= ($qty_to_add_fix * $entry['volume_per_ctn']);
                    $fix_adjustment_made = true;
                    // **PENTING:** Kita tidak lagi memperbarui processed_skus_for_shipment dan current_sku_count
                    // di dalam loop EndFixAdjust. Ini memastikan batasan SKU diabaikan sepenuhnya.
                }
            }
            unset($entry); // Putuskan referensi terakhir

            // Jika tidak ada penyesuaian yang berhasil dalam satu iterasi penuh, hentikan.
            // Ini mencegah infinite loop jika tidak ada SKU yang bisa menambah volume.
            if (!$fix_adjustment_made && $deficit_fix > $epsilon) { // Gunakan epsilon
                error_log("EndFixAdjust: No more adjustments made in this iteration for " . $mix_cust_id);
                break;
            }
            
            // Recalculate total volume for next iteration
            $total_volume_current = 0;
            foreach ($allocated_skus_ref_map as $key => $entry) {
                if ($entry['alloc_qty'] > 0) {
                    $total_volume_current += $entry['used_volume'];
                }
            }
            $deficit_fix = $shipsize - $total_volume_current; // Update deficit for next iteration
        }
        // --- Log Setelah Langkah 3 ---
        error_log("After Step 3 (EndFixAdjust), final total_volume_current for " . $mix_cust_id . ": " . $total_volume_current . " m3");
    } else {
        error_log("Langkah 3 (EndFixAdjust) skipped for " . $mix_cust_id . ": total_volume_current (" . $total_volume_current . ") is sufficient (>= " . ($shipsize - $epsilon) . ")");
    }


    // ==============================================================================
    // Finalisasi dan Pembentukan Hasil Akhir Output Per Mixcust
    // Memastikan tidak ada qty < minCarton di output final.
    // ==============================================================================
    // Setelah semua alokasi selesai, hitung ulang SKU count untuk output final
    $current_sku_count_final = 0;
    $processed_skus_for_shipment_final = [];
    foreach ($allocated_skus_ref_map as $key => $entry) {
        if ($entry['alloc_qty'] > 0) {
            if (!in_array($entry['sku'], $processed_skus_for_shipment_final)) {
                $processed_skus_for_shipment_final[] = $entry['sku'];
                $current_sku_count_final++;
            }
        }
    }
    error_log("Final SKU Count for " . $mix_cust_id . ": " . $current_sku_count_final);


    foreach ($allocated_skus_ref_map as $sku_channel_key => $entry) {
        $final_qty = min($entry['alloc_qty'], $entry['cmo'], $entry['stock']);

        // FINAL VALIDATION PASS:
        // Jika allocated QTY > 0 TAPI < minCarton, maka set ke 0.
        // Ini adalah jaring pengaman terakhir untuk memastikan integritas minCarton pada output.
        // Ini harus tetap ada agar output bersih dari qty 'tanggung'.
        if ($minCarton > 0 && $final_qty > 0 && $final_qty < $minCarton) {
             $final_qty = 0;
        }

        $final_volume = $final_qty * $entry['volume_per_ctn'];

        if ($final_qty > 0) {
            $volume_ratio = ($shipsize > 0)
                ? round(($final_volume / $shipsize) * 100, 2)
                : 0;

            $truck_size = ($shipsize > 0)
                ? round($final_volume / $shipsize, 2)
                : 0;

            $csl_output = 0;
            if ($entry['final_cmo_for_csl'] > 0) {
                $csl_output = round(((($entry['row']['ACTUAL'] ?? 0) + ($entry['row']['DO PLANNING'] ?? 0)) / $entry['final_cmo_for_csl']) * 100, 2);
            }

            $final_allocated_data[] = [
                'MIXCUST' => $entry['mix_cust'],
                'CUST#' => $entry['cust'],
                'CUSTOMER - CITY/CHANNEL' => $entry['row']['CUSTOMER - CITY/CHANNEL'] ?? '',
                'CHANNEL CUST' => $mixcust_map[$entry['cust']]['channel_cust'] ?? '',
                'SKU' => $entry['sku'],
                'MATERIAL DESCRIPTION' => $entry['row']['MATERIAL DESCRIPTION'] ?? '',
                'CHANNEL' => $entry['channel'],
                'PULAU' => $mixcust_map[$entry['cust']]['pulau'] ?? '',
                'SHIPMENT TYPE' => $mixcust_map[$entry['cust']]['shipment_type'] ?? '',
                'REGION SCM' => $mixcust_map[$entry['cust']]['region_scm'] ?? '',
                'OTD CMO' => $entry['cmo'],
                'CMO FINAL' => $entry['final_cmo_for_csl'],
                'ACTUAL' => (int)($entry['row']['ACTUAL'] ?? 0),
                'DO PLANNING' => (int)($entry['row']['DO PLANNING'] ?? 0),
                'SISA QTY' => $entry['stock'],
                'Final Allocated QTY' => $final_qty,
                'Volume per CTN' => $entry['volume_per_ctn'],
                'Used Volume (m3)' => round($final_volume, 2),
                'SHIPMENT SIZE' => $shipsize,
                'Volume Ratio (%)' => $volume_ratio,
                'Truck Size' => $truck_size,
                'CSL (%)' => $csl_output
            ];
        }
    }
    error_log("--- Finished processing Mixcust: " . $mix_cust_id . " ---");
}

$_SESSION['allocation_result'] = $final_allocated_data;

// echo json_encode(['status' => 'success', 'data' => $final_allocated_data]);
?>