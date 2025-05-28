<?php
session_start();
require_once('../../services/database.php');
include 'update_cmo_data.php';

// Mengambil data yang diperlukan dari session.
// Menggunakan operator null coalescing (??) untuk inisialisasi dengan array kosong jika data belum ada.
$cmo_data = $_SESSION['cmo_temp_data_update'] ?? []; // Data Order To Delivery (OTD) CMO
$stock_data = $_SESSION['final_stock'] ?? [];         // Data stok akhir
$allocationsettings = $_SESSION['allocation_settings'] ?? []; // Pengaturan alokasi dari pengguna

// Memeriksa apakah semua data input yang diperlukan (CMO, Stok, Pengaturan) sudah tersedia.
// Jika salah satu kosong, proses tidak dapat dilanjutkan.
if (empty($cmo_data) || empty($stock_data) || empty($allocationsettings)) {
    echo json_encode(['status' => 'error', 'message' => 'Data CMO atau Stock kosong, atau pengaturan alokasi kosong. Mohon pastikan semua data telah dimuat.']);
    exit; // Hentikan eksekusi script.
}

// === Bagian 1: Konfigurasi dan Pemetaan Data Awal ===

// Memetakan pengaturan alokasi yang diambil dari session ke dalam variabel lokal yang mudah diakses.
$allocation_map = [
    'minCarton' => $allocationsettings['minCarton'] ?? 0, // Jumlah karton minimum yang harus dialokasikan per SKU jika dialokasikan.
    'shipmentSizePercent' => $allocationsettings['shipmentSizePercent'] ?? 0, // Persentase maksimum volume satu jenis SKU dalam satu truk (untuk menjaga variasi produk).
    'rangeFullCMO' => (int)($allocationsettings['rangeFullCMO'] ?? 0), // Batas atas CMO (dalam unit) di mana SKU akan dialokasikan "FULL CMO" tanpa pembatasan persentase awal.
    // SkuLimit: Batas jumlah SKU unik yang boleh dialokasikan per mix_cust.
    // Jika disetel 0, dianggap tidak ada batas (menggunakan PHP_INT_MAX).
    'SkuLimit' => ($allocationsettings['SkuLimit'] ?? 0) == 0 ? PHP_INT_MAX : (int)($allocationsettings['SkuLimit'] ?? PHP_INT_MAX)
];

// Setting: PERSENTASE MAKSIMAL DARI OTD CMO YANG BOLEH DIALOKASIKAN.
// Ini adalah batasan awal yang akan dilonggarkan/diabaikan di tahap `$endadjustment`.
$maxCMOPercentageAllocationForNonFullCMO = 0.20; // Contoh: disetel 20%.

// Memetakan data stok ke dalam array asosiatif untuk akses cepat berdasarkan kombinasi SKU dan CHANNEL.
// Memastikan nilai 'Sisa QTY' (stock) tidak pernah negatif.
$stock_map = [];
foreach ($stock_data as $stock) {
    $key = $stock['SKU'] . '_' . $stock['CHANNEL'];
    $stock_map[$key] = max((int)$stock['Sisa QTY'], 0);
}

// Mengambil data 'mixcust' dari database dan memetakannya.
// Data ini berisi informasi seperti 'shipsize' (kapasitas truk), channel, pulau, dll.
$mixcust_map = [];
$res_mix = mysqli_query($conn, "SELECT cust, mix_cust, shipsizemajalengka, channel_cust, pulau, shipment_type, region_scm FROM mixcust");
while ($row = mysqli_fetch_assoc($res_mix)) {
    $mixcust_map[$row['cust']] = [
        'mix_cust' => $row['mix_cust'],
        'shipsize' => (float)$row['shipsizemajalengka'], // Kapasitas truk untuk mix_cust ini (dalam m3).
        'channel_cust' => $row['channel_cust'],
        'pulau' => $row['pulau'],
        'shipment_type' => $row['shipment_type'],
        'region_scm' => $row['region_scm']
    ];
}

// Mengambil data volume per SKU dari tabel 'itembank' dan memetakannya.
// Ini diperlukan untuk menghitung total volume (m3) dari kuantitas karton yang dialokasikan.
$itembank_map = [];
$res_itembank = mysqli_query($conn, "SELECT sku, volume FROM itembank");
while ($row = mysqli_fetch_assoc($res_itembank)) {
    $itembank_map[$row['sku']] = (float)$row['volume']; // Volume per karton (m3).
}

// Array untuk menyimpan hasil alokasi awal sebelum penyesuaian akhir (perbaikan, pengurangan, penambahan).
$pre_allocations = [];

// === Bagian 2: Tahap Awal Alokasi Berdasarkan CMO dan Stok ===
// Iterasi melalui setiap baris data CMO untuk menentukan alokasi awal.
foreach ($cmo_data as $row) {
    $cust = $row['CUST#'];
    $sku = $row['SKU'];
    $channel = $row['CHANNEL'];
    $cmo = (int)$row['OTD CMO']; // Order To Delivery (CMO) quantity
    $minCarton = $allocation_map['minCarton'];
    $rangeFullCMO = $allocation_map['rangeFullCMO'];

    // Lewati baris jika data mixcust atau itembank tidak tersedia untuk Customer/SKU ini.
    if (!isset($mixcust_map[$cust]) || !isset($itembank_map[$sku])) continue;

    $mix_cust = $mixcust_map[$cust]['mix_cust'];
    $volume_per_ctn = $itembank_map[$sku];
    $key = $sku . '_' . $channel;
    $stock = $stock_map[$key] ?? 0; // Stok yang tersedia untuk SKU/Channel ini

    // Lewati jika volume per karton tidak valid (nol atau negatif),
    // atau jika stok atau CMO lebih kecil dari minimum karton yang diizinkan untuk dialokasikan.
    if ($volume_per_ctn <= 0 || $stock < $minCarton || $cmo < $minCarton) continue;

    $alloc_qty = 0; // Kuantitas yang akan dialokasikan untuk SKU ini.

    // Menentukan apakah SKU ini termasuk kategori "rangeFullCMO".
    // SKU dalam kategori ini akan dialokasikan penuh (sesuai CMO atau stok) tanpa pembatasan persentase awal.
    $should_be_full_cmo = ($cmo >= $minCarton && $cmo <= $rangeFullCMO);

    if ($should_be_full_cmo) {
        // Jika SKU berada dalam rangeFullCMO, alokasikan sebanyak minimum dari CMO dan Stok.
        $alloc_qty = min($cmo, $stock);
    } else {
        // Jika OTD CMO DI LUAR rangeFullCMO (yakni, CMO lebih besar dari rangeFullCMO),
        // terapkan pembatasan persentase CMO awal ($maxCMOPercentageAllocationForNonFullCMO).
        $max_qty_based_on_cmo_percent = floor($cmo * $maxCMOPercentageAllocationForNonFullCMO);
        // Alokasikan jumlah terkecil dari: Stok, OTD CMO, atau batasan persentase CMO.
        $alloc_qty = min($stock, $cmo, $max_qty_based_on_cmo_percent);

        // Setelah alokasi awal, pastikan alokasi tidak kurang dari `minCarton` jika memungkinkan.
        // Jika `alloc_qty` hasil perhitungan di atas < `minCarton`, tapi stok dan CMO cukup untuk `minCarton`,
        // maka naikkan `alloc_qty` menjadi `minCarton`. Jika tidak, set ke 0.
        if ($alloc_qty < $minCarton) {
            if ($stock >= $minCarton && $cmo >= $minCarton) {
                 $alloc_qty = $minCarton;
            } else {
                $alloc_qty = 0; // Tidak bisa memenuhi minCarton, jadi tidak dialokasikan.
            }
        }
    }
    $alloc_qty = max(0, $alloc_qty); // Pastikan allocated quantity tidak negatif.

    $used_volume = $alloc_qty * $volume_per_ctn;

    // Simpan hasil alokasi awal ini ke dalam array $pre_allocations, dikelompokkan per mix_cust.
    $pre_allocations[$mix_cust][] = [
        'row' => $row, // Simpan data baris CMO asli untuk referensi
        'cust' => $cust,
        'sku' => $sku,
        'channel' => $channel,
        'mix_cust' => $mix_cust,
        'volume_per_ctn' => $volume_per_ctn,
        'alloc_qty' => $alloc_qty,
        'used_volume' => $used_volume,
        'cmo' => $cmo,
        'stock' => $stock,
        'should_be_full_cmo' => $should_be_full_cmo // Flag untuk mengidentifikasi SKU yang berada di rangeFullCMO
    ];
}

$final_result = []; // Array untuk menyimpan hasil akhir alokasi yang akan ditampilkan.

// === Bagian 3: Penyesuaian Alokasi Per Mixcust (Shipment Size, SKU Limit, Defisit/Excess) ===
// Iterasi melalui setiap kelompok mix_cust untuk melakukan penyesuaian.
foreach ($pre_allocations as $mix_cust => $entries) {
    $shipsize = 0;
    // Cari ukuran truk (shipsize) untuk mix_cust saat ini.
    foreach ($mixcust_map as $data) {
        if ($data['mix_cust'] == $mix_cust) {
            $shipsize = $data['shipsize'];
            break;
        }
    }
    $max_sku_limit = $allocation_map['SkuLimit'];
    $shipmentSizePercent = $allocation_map['shipmentSizePercent'];
    $minCarton = $allocation_map['minCarton'];

    // Urutkan entries berdasarkan CSL (Customer Service Level) terkecil.
    // Pengurutan ini penting untuk prioritas saat menambah atau mengurangi alokasi.
    // SKU dengan CSL terkecil (paling "butuh") akan diutamakan saat mengisi defisit,
    // dan dikurangi terakhir saat ada kelebihan.
    usort($entries, function($a, $b) {
        $cmo_final_a = max((int)$a['row']['CMO FINAL'], 1);
        $actual_a = (int)$a['row']['ACTUAL'];
        $do_planning_a = (int)$a['row']['DO PLANNING'];
        $csl_a = round((($actual_a + $do_planning_a) / $cmo_final_a) * 100, 2);

        $cmo_final_b = max((int)$b['row']['CMO FINAL'], 1);
        $actual_b = (int)$b['row']['ACTUAL'];
        $do_planning_b = (int)$b['row']['DO PLANNING'];
        $csl_b = round((($actual_b + $do_planning_b) / $cmo_final_b) * 100, 2);

        return $csl_a <=> $csl_b; // Urutkan menaik (CSL terkecil di depan)
    });

    // --- Logika Batasan Jumlah SKU per Mixcust (SKU Limit) ---
    // Memastikan tidak lebih dari 'SkuLimit' SKU yang dialokasikan per mix_cust.
    // SKU yang melebihi batas ini akan dialokasikan 0 kuantitas.
    $current_sku_count = 0;
    $selected_skus_for_mixcust = [];
    $temp_entries_after_sku_limit = []; // Array sementara untuk menyimpan hasil setelah filter SKU limit.

    foreach ($entries as $entry) {
        if (!in_array($entry['sku'], $selected_skus_for_mixcust)) {
            // Jika SKU ini belum termasuk dalam daftar SKU yang terpilih untuk mix_cust ini.
            if ($current_sku_count < $max_sku_limit) {
                // Jika jumlah SKU yang sudah terpilih masih di bawah batas, tambahkan SKU ini.
                $selected_skus_for_mixcust[] = $entry['sku'];
                $current_sku_count++;
                $temp_entries_after_sku_limit[] = $entry;
            } else {
                // Jika jumlah SKU sudah mencapai batas, set alokasi untuk SKU ini menjadi 0.
                $entry['alloc_qty'] = 0;
                $entry['used_volume'] = 0;
                $temp_entries_after_sku_limit[] = $entry;
            }
        } else {
            // Jika SKU ini sudah termasuk dalam daftar SKU yang terpilih (misal ada multiple entry untuk SKU yang sama),
            // masukkan saja entry-nya tanpa perubahan alokasi (karena sudah diproses di tahap awal).
            $temp_entries_after_sku_limit[] = $entry;
        }
    }
    $entries = $temp_entries_after_sku_limit; // Perbarui $entries dengan hasil filter SKU limit.
    // --- Akhir Logika Batasan SKU per Mixcust ---

    $total_volume = array_sum(array_column($entries, 'used_volume'));

    if ($total_volume > $shipsize) {
        // KASUS 1: Kelebihan Volume (`total_volume > shipsize`).
        // Jika total volume yang dialokasikan melebihi kapasitas truk, lakukan pengurangan.
        // Prioritaskan pengurangan dari SKU dengan CSL TERBESAR (paling "tidak butuh").
        usort($entries, function($a, $b) {
            $cmo_final_a = max((int)$a['row']['CMO FINAL'], 1);
            $actual_a = (int)$a['row']['ACTUAL'];
            $do_planning_a = (int)$a['row']['DO PLANNING'];
            $csl_a = round((($actual_a + $do_planning_a) / $cmo_final_a) * 100, 2);

            $cmo_final_b = max((int)$b['row']['CMO FINAL'], 1);
            $actual_b = (int)$b['row']['ACTUAL'];
            $do_planning_b = (int)$b['row']['DO PLANNING'];
            $csl_b = round((($actual_b + $do_planning_b) / $cmo_final_b) * 100, 2);

            return $csl_b <=> $csl_a; // Urutkan menurun (CSL terbesar di depan)
        });

        $excess = $total_volume - $shipsize; // Hitung berapa kelebihan volume.

        // Iterasi pertama pengurangan: Kurangi dari SKU yang TIDAK 'should_be_full_cmo'.
        // Ini untuk mencoba mempertahankan alokasi penuh pada SKU prioritas (rangeFullCMO).
        foreach ($entries as &$entry) {
            if ($excess <= 0) break; // Jika kelebihan sudah teratasi, berhenti.
            // Lewati jika alokasi sudah 0, atau jika SKU ini seharusnya dialokasikan full CMO.
            if ($entry['alloc_qty'] == 0 || $entry['should_be_full_cmo']) continue;

            // Kuantitas maksimal yang bisa dikurangi dari SKU ini, tidak boleh kurang dari `minCarton`.
            $max_reducible_qty = max(0, $entry['alloc_qty'] - $minCarton);
            $max_reducible_volume = $max_reducible_qty * $entry['volume_per_ctn'];

            if ($max_reducible_volume >= $excess) {
                // Jika SKU ini saja cukup untuk menutupi semua kelebihan.
                $reduce_qty = floor($excess / $entry['volume_per_ctn']);
                $entry['alloc_qty'] -= $reduce_qty;
                $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                $excess = 0; // Kelebihan sudah teratasi.
            } else {
                // Jika SKU ini tidak cukup untuk menutupi semua kelebihan, kurangi semaksimal mungkin (sampai minCarton).
                $entry['alloc_qty'] -= $max_reducible_qty;
                $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                $excess -= $max_reducible_volume;
            }
        }
        unset($entry); // Lepaskan referensi untuk mencegah efek samping pada iterasi berikutnya.

        // Iterasi kedua pengurangan: Jika masih ada kelebihan, baru kurangi dari SKU yang 'should_be_full_cmo'.
        // Tetap perhatikan `minCarton`.
        if ($excess > 0) {
            foreach ($entries as &$entry) {
                if ($excess <= 0) break; // Jika kelebihan sudah teratasi, berhenti.
                if ($entry['alloc_qty'] == 0) continue; // Lewati jika alokasi sudah 0.

                $max_reducible_qty = max(0, $entry['alloc_qty'] - $minCarton);
                $max_reducible_volume = $max_reducible_qty * $entry['volume_per_ctn'];

                if ($max_reducible_volume >= $excess) {
                    $reduce_qty = floor($excess / $entry['volume_per_ctn']);
                    $entry['alloc_qty'] -= $reduce_qty;
                    $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                    $excess = 0;
                } else {
                    $entry['alloc_qty'] -= $max_reducible_qty;
                    $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                    $excess -= $max_reducible_volume;
                }
            }
            unset($entry); // Lepaskan referensi.
        }

    } elseif ($total_volume < $shipsize) {
        // KASUS 2: Defisit Volume (total_volume < shipsize).
        // Ini adalah logika untuk mengisi defisit hingga `shipsize`.

        // Urutkan kembali berdasarkan CSL terkecil (prioritas untuk penambahan).
        usort($entries, function($a, $b) {
            $cmo_final_a = max((int)$a['row']['CMO FINAL'], 1);
            $actual_a = (int)$a['row']['ACTUAL'];
            $do_planning_a = (int)$a['row']['DO PLANNING'];
            $csl_a = round((($actual_a + $do_planning_a) / $cmo_final_a) * 100, 2);

            $cmo_final_b = max((int)$b['row']['CMO FINAL'], 1);
            $actual_b = (int)$b['row']['ACTUAL'];
            $do_planning_b = (int)$b['row']['DO PLANNING'];
            $csl_b = round((($actual_b + $do_planning_b) / $cmo_final_b) * 100, 2);

            return $csl_a <=> $csl_b; // CSL terkecil di depan
        });

        $deficit = $shipsize - $total_volume; // Hitung defisit

        $max_filling_iterations = 50; 
        $current_filling_iteration = 0;

        // Loop utama untuk terus mengisi defisit sampai tercapai atau tidak ada lagi yang bisa diisi.
        while ($deficit > 0 && $current_filling_iteration < $max_filling_iterations) {
            $initial_deficit_in_loop = $deficit; // Simpan defisit awal untuk deteksi stagnasi.
            $current_filling_iteration++;

            // --- FASE 2.1: Prioritaskan Pengisian Hingga minCarton untuk SKU yang Belum Mencapai ---
            // Upaya untuk memastikan semua SKU yang eligible minimal memenuhi `minCarton` mereka.
            $mincarton_addition_made = false;
            foreach ($entries as $index => &$entry) {
                if ($deficit <= 0) break; // Jika defisit sudah terisi, berhenti.

                // Kriteria: Bukan full CMO, belum mencapai minCarton, stok dan CMO cukup untuk minCarton.
                if (!$entry['should_be_full_cmo'] && 
                    $entry['alloc_qty'] < $minCarton && 
                    $entry['stock'] >= $minCarton &&    
                    $entry['cmo'] >= $minCarton         
                ) {
                    $qty_to_reach_min_carton = $minCarton - $entry['alloc_qty'];
                    
                    // Batasan dari shipmentSizePercent (persentase maksimal satu SKU dalam truk).
                    $max_qty_based_on_shipsize_percent = ($shipsize > 0)
                                                        ? floor(($shipsize * $shipmentSizePercent / 100) / $entry['volume_per_ctn'])
                                                        : PHP_INT_MAX;
                    $allowed_by_shipsize_percent = $max_qty_based_on_shipsize_percent - $entry['alloc_qty'];

                    // Jumlah aktual yang bisa ditambahkan adalah yang terkecil dari semua batasan.
                    $actual_add_qty = min(
                        $qty_to_reach_min_carton,
                        $entry['stock'] - $entry['alloc_qty'],
                        $entry['cmo'] - $entry['alloc_qty'], // Tetap batasi oleh full OTD CMO di sini.
                        $allowed_by_shipsize_percent, // Ini tetap berlaku di fase awal ini.
                        floor($deficit / $entry['volume_per_ctn']) // Tidak boleh melebihi sisa defisit.
                    );

                    if ($actual_add_qty > 0) {
                        $entry['alloc_qty'] += $actual_add_qty;
                        $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                        $deficit -= ($actual_add_qty * $entry['volume_per_ctn']);
                        $mincarton_addition_made = true;
                    }
                }
            }
            unset($entry); // Lepaskan referensi.

            if ($mincarton_addition_made) {
                 // Jika ada penambahan di fase minCarton, hitung ulang total volume dan defisit, lalu ulangi loop.
                 $total_volume = array_sum(array_column($entries, 'used_volume'));
                 $deficit = $shipsize - $total_volume;
                 continue; // Lanjutkan ke iterasi berikutnya untuk mengevaluasi kembali.
            }

            // --- FASE 2.2: Pengisian Proporsional untuk Sisa Defisit (dengan pelonggaran CMO% awal) ---
            // Pada fase ini, $maxCMOPercentageAllocationForNonFullCMO DILONGGARKAN.
            // Yaitu, SKU non-rangeFullCMO bisa diisi hingga full OTD CMO mereka, dibatasi oleh stock dan shipsizePercent.
            $current_eligible_entries_prop = [];
            $total_current_volume_eligible_prop = 0;

            foreach ($entries as $index => $entry) {
                // Kriteria: Bukan full CMO, masih ada stok, belum mencapai full OTD CMO, belum mencapai shipsizePercent.
                if (!$entry['should_be_full_cmo'] && 
                    $entry['stock'] > $entry['alloc_qty'] &&
                    $entry['cmo'] > $entry['alloc_qty'] 
                ) {
                    $max_qty_for_sku_based_on_shipsize_percent = ($shipsize > 0)
                                                                ? floor(($shipsize * $shipmentSizePercent / 100) / $entry['volume_per_ctn'])
                                                                : PHP_INT_MAX;

                    if ($entry['alloc_qty'] < $max_qty_for_sku_based_on_shipsize_percent) {
                        $current_eligible_entries_prop[$index] = $entry;
                        $total_current_volume_eligible_prop += $entry['used_volume'];
                    }
                }
            }

            $count_eligible_prop = count($current_eligible_entries_prop);
            // Jangan break dulu, karena mungkin masih ada kesempatan di end adjustment
            
            $proportional_addition_made = false;
            if ($count_eligible_prop > 0) { // Hanya jalankan jika ada yang eligible.
                foreach ($entries as $index => &$entry) {
                    if ($deficit <= 0) break; // Jika defisit sudah terisi, berhenti.
                    if (!isset($current_eligible_entries_prop[$index])) continue; // Hanya proses yang eligible.

                    $available_stock = $entry['stock'] - $entry['alloc_qty'];
                    $can_add_up_to_cmo = $entry['cmo'] - $entry['alloc_qty']; 

                    $max_qty_for_sku_based_on_shipsize_percent = ($shipsize > 0)
                                                                ? floor(($shipsize * $shipmentSizePercent / 100) / $entry['volume_per_ctn'])
                                                                : PHP_INT_MAX;
                    $can_add_up_to_shipsize_percent = $max_qty_for_sku_based_on_shipsize_percent - $entry['alloc_qty'];

                    $proportional_share_of_deficit_qty = 0;
                    if ($total_current_volume_eligible_prop > 0) {
                        $proportional_share_volume = ($entry['used_volume'] / $total_current_volume_eligible_prop) * $deficit;
                        $proportional_share_of_deficit_qty = floor($proportional_share_volume / $entry['volume_per_ctn']);
                    } else {
                        // Jika tidak ada volume awal untuk proporsionalitas, bagi rata sisa defisit.
                        $proportional_share_of_deficit_qty = floor(($deficit / $count_eligible_prop) / $entry['volume_per_ctn']);
                    }
                    
                    $add_qty_this_iteration = min(
                        $proportional_share_of_deficit_qty,
                        $available_stock,
                        $can_add_up_to_cmo, // Batasi hingga full OTD CMO
                        $can_add_up_to_shipsize_percent, // Ini tetap berlaku di fase ini.
                        floor($deficit / $entry['volume_per_ctn']) // Batasi oleh sisa defisit
                    );

                    $add_qty_this_iteration = max(0, $add_qty_this_iteration);
                    
                    if ($add_qty_this_iteration > 0) {
                        $entry['alloc_qty'] += $add_qty_this_iteration;
                        $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                        $deficit -= $add_qty_this_iteration * $entry['volume_per_ctn'];
                        $proportional_addition_made = true;
                    }
                }
                unset($entry); // Lepaskan referensi.
            }

            // --- FASE 2.3: END ADJUSTMENT (Paling Agresif - mengabaikan shipmentSizePercent) ---
            // Jika defisit masih ada setelah Fase 2.1 dan 2.2, masuk ke End Adjustment.
            // Pada fase ini, $maxCMOPercentageAllocationForNonFullCMO DAN shipmentSizePercent DIABAIKAN
            // untuk SKU non-rangeFullCMO. Tujuannya adalah untuk mengisi truk sedekat mungkin ke shipsize,
            // selama masih ada stock dan CMO.
            if ($deficit > 0 && ($deficit === $initial_deficit_in_loop || (!$mincarton_addition_made && !$proportional_addition_made))) {
                $endadjustment_deficit_before_phase = $deficit; // Simpan defisit awal untuk deteksi stagnasi di fase ini.

                $eligible_for_end_adjustment = [];
                $total_volume_for_end_adjustment_base = 0; // Volume dasar untuk proporsionalitas di fase ini.

                foreach ($entries as $index => $entry) {
                    // Kriteria eligibility untuk End Adjustment (melonggarkan shipmentSizePercent):
                    // 1. Bukan SKU yang seharusnya `full_cmo`.
                    // 2. Masih ada sisa `stock` (`stock > alloc_qty`).
                    // 3. Belum mencapai `full OTD CMO` (`cmo > alloc_qty`).
                    // Catatan: batasan `shipmentSizePercent` DIABAIKAN di sini.
                    if (!$entry['should_be_full_cmo'] &&
                        $entry['stock'] > $entry['alloc_qty'] &&
                        $entry['cmo'] > $entry['alloc_qty'] 
                    ) {
                        $eligible_for_end_adjustment[$index] = $entry;
                        // Gunakan allocated volume saat ini sebagai basis proporsional,
                        // atau 1 jika belum ada alokasi agar semua memiliki kesempatan.
                        $total_volume_for_end_adjustment_base += ($entry['used_volume'] > 0 ? $entry['used_volume'] : $entry['volume_per_ctn']); 
                    }
                }

                $count_eligible_end_adjustment = count($eligible_for_end_adjustment);
                $end_adjustment_made = false;

                if ($count_eligible_end_adjustment > 0) {
                    // Coba alokasikan sisa defisit secara proporsional.
                    // Loop ini bisa berjalan beberapa kali untuk mengisi defisit secara bertahap.
                    $end_adj_sub_iterations = 5; // Batasan iterasi untuk end adjustment proporsional.
                    for ($e_iter = 0; $e_iter < $end_adj_sub_iterations; $e_iter++) {
                        if ($deficit <= 0) break;
                        $sub_iter_deficit_before = $deficit;

                        foreach ($entries as $index => &$entry) {
                            if ($deficit <= 0) break;
                            if (!isset($eligible_for_end_adjustment[$index])) continue;

                            $available_stock = $entry['stock'] - $entry['alloc_qty'];
                            $can_add_up_to_cmo = $entry['cmo'] - $entry['alloc_qty']; 
                            // $max_qty_for_sku_based_on_shipsize_percent TIDAK DIGUNAKAN DI FASE INI

                            // Hitung jatah proporsional dari sisa defisit untuk SKU ini.
                            $proportional_share_of_end_deficit_qty = 0;
                            if ($total_volume_for_end_adjustment_base > 0) {
                                // Hitung proporsional berdasarkan volume yang sudah terpakai (atau volume_per_ctn jika 0)
                                $base_volume_for_sku = ($entry['used_volume'] > 0 ? $entry['used_volume'] : $entry['volume_per_ctn']);
                                $proportional_share_volume = ($base_volume_for_sku / $total_volume_for_end_adjustment_base) * $deficit;
                                $proportional_share_of_end_deficit_qty = floor($proportional_share_volume / $entry['volume_per_ctn']);
                            } else {
                                // Jika tidak ada volume dasar untuk proporsionalitas, bagi rata sisa defisit.
                                $proportional_share_of_end_deficit_qty = floor(($deficit / $count_eligible_end_adjustment) / $entry['volume_per_ctn']);
                            }
                            
                            $add_qty_this_end_iteration = min(
                                $proportional_share_of_end_deficit_qty,
                                $available_stock,
                                $can_add_up_to_cmo, 
                                floor($deficit / $entry['volume_per_ctn']) // Tidak boleh melebihi sisa defisit
                            );

                            $add_qty_this_end_iteration = max(0, $add_qty_this_end_iteration);

                            // Pastikan mencapai minCarton jika belum dan memungkinkan di fase ini juga.
                            if (($entry['alloc_qty'] + $add_qty_this_end_iteration) < $minCarton && $entry['alloc_qty'] < $minCarton) {
                                $required_for_min_carton = $minCarton - $entry['alloc_qty'];
                                if ($available_stock >= $required_for_min_carton &&
                                    $can_add_up_to_cmo >= $required_for_min_carton
                                ) {
                                    $add_qty_this_end_iteration = max($add_qty_this_end_iteration, $required_for_min_carton);
                                }
                            }

                            if ($add_qty_this_end_iteration > 0) {
                                $entry['alloc_qty'] += $add_qty_this_end_iteration;
                                $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                                $deficit -= ($add_qty_this_end_iteration * $entry['volume_per_ctn']);
                                $end_adjustment_made = true;
                            }
                        }
                        unset($entry);

                        // Jika defisit tidak berkurang di sub-iterasi ini, keluar dari loop sub-iterasi.
                        if ($deficit === $sub_iter_deficit_before) break;
                    } // End of end_adj_sub_iterations loop.

                    // --- FASE 2.3.1: Last Resort - Tambah 1 Karton Demi 1 Karton ---
                    // Jika masih ada defisit setelah alokasi proporsional di end adjustment,
                    // coba tambahkan 1 karton ke SKU yang paling butuh (CSL terendah), selama masih eligible.
                    // Ini untuk mengatasi pembulatan `floor()` yang mungkin meninggalkan defisit kecil.
                    if ($deficit > 0) {
                        // Urutkan ulang berdasarkan CSL terkecil untuk prioritas.
                        usort($eligible_for_end_adjustment, function($a, $b) {
                            $cmo_final_a = max((int)$a['row']['CMO FINAL'], 1);
                            $actual_a = (int)$a['row']['ACTUAL'];
                            $do_planning_a = (int)$a['row']['DO PLANNING'];
                            $csl_a = round((($actual_a + $do_planning_a) / $cmo_final_a) * 100, 2);
                    
                            $cmo_final_b = max((int)$b['row']['CMO FINAL'], 1);
                            $actual_b = (int)$b['row']['ACTUAL'];
                            $do_planning_b = (int)$b['row']['DO PLANNING'];
                            $csl_b = round((($actual_b + $do_planning_b) / $cmo_final_b) * 100, 2);
                    
                            return $csl_a <=> $csl_b; // CSL terkecil di depan
                        });

                        // Loop sampai defisit 0 atau tidak ada lagi yang bisa ditambahkan.
                        while ($deficit > 0) {
                            $added_one_carton_in_loop = false;
                            foreach ($eligible_for_end_adjustment as $index => $entry_ref) { 
                                if ($deficit <= 0) break; // Berhenti jika defisit sudah 0.

                                // Temukan entri asli di array $entries untuk modifikasi.
                                $entry_key = array_search($entry_ref['sku'], array_column($entries, 'sku'));
                                if ($entry_key === false) continue; // Should not happen.
                                $current_entry = &$entries[$entry_key];

                                // Jika SKU ini tidak lagi eligible (mungkin stok/CMO-nya sudah habis di iterasi lain)
                                if (!($current_entry['stock'] > $current_entry['alloc_qty'] && $current_entry['cmo'] > $current_entry['alloc_qty'])) continue;

                                $add_qty = 1; // Coba tambahkan 1 karton.

                                $can_add = true;
                                if (($add_qty * $current_entry['volume_per_ctn']) > $deficit) $can_add = false; // Tidak bisa melebihi sisa defisit.
                                if ($current_entry['stock'] - $current_entry['alloc_qty'] < $add_qty) $can_add = false;
                                if ($current_entry['cmo'] - $current_entry['alloc_qty'] < $add_qty) $can_add = false; // Batas full OTD CMO
                                // shipmentSizePercent DIABAIKAN DI SINI!

                                if ($can_add) {
                                    $current_entry['alloc_qty'] += $add_qty;
                                    $current_entry['used_volume'] = $current_entry['alloc_qty'] * $current_entry['volume_per_ctn'];
                                    $deficit -= ($add_qty * $current_entry['volume_per_ctn']);
                                    $end_adjustment_made = true;
                                    $added_one_carton_in_loop = true;
                                }
                            }
                            unset($current_entry); // Lepaskan referensi.
                            // Jika tidak ada satu karton pun yang bisa ditambahkan dalam satu putaran, berarti tidak ada lagi yang bisa diisi.
                            if (!$added_one_carton_in_loop) break; 
                        }
                    }
                }

                // Setelah upaya End Adjustment, update deficit global.
                // Jika defisit tidak berkurang dalam fase end adjustment ini,
                // berarti tidak ada lagi yang bisa ditambahkan. Hentikan loop utama.
                if ($deficit === $endadjustment_deficit_before_phase) {
                    break; 
                }
            } 
            // Jika defisit tidak berkurang dalam putaran 'while' ini (setelah semua fase), hentikan loop.
            if ($deficit === $initial_deficit_in_loop) {
                break;
            }
            // Update total_volume dan defisit untuk iterasi berikutnya (jika ada).
            $total_volume = array_sum(array_column($entries, 'used_volume'));
            $deficit = $shipsize - $total_volume;
        }
    }

    // === Bagian 4: Finalisasi dan Pembentukan Hasil Akhir Output ===
    // Iterasi terakhir untuk memformat data dan menerapkan aturan final.
    foreach ($entries as &$entry) {
        $should_be_full_cmo_final_check = $entry['should_be_full_cmo'];
        $current_final_mixcust_volume = array_sum(array_column($entries, 'used_volume'));

        // Aturan: Pembatasan `$maxCMOPercentageAllocationForNonFullCMO` (misal 20%)
        // HANYA diterapkan kembali untuk SKU yang BUKAN `rangeFullCMO`
        // DAN JIKA `total_volume` untuk mix_cust ini SUDAH MENCAPAI atau MELEBIHI `shipsize`.
        // PENTING: Aturan ini TIDAK AKAN MENYEBABKAN PENGURANGAN JIKA DI FASE $endadjustment
        // alokasi sudah naik melebihi 20% dan defisit sudah terisi.
        // Tujuannya adalah agar alokasi yang sudah mencapai shipsize tidak dikurangi lagi
        // karena batasan 20% yang sebelumnya sengaja dilonggarkan.
        if (!$should_be_full_cmo_final_check && $current_final_mixcust_volume >= $shipsize) {
            $target_max_cmo_qty_for_final = floor($entry['cmo'] * $maxCMOPercentageAllocationForNonFullCMO);

            $new_alloc_qty = max($target_max_cmo_qty_for_final, $minCarton);
            $new_alloc_qty = min($new_alloc_qty, $entry['cmo']); 

            // Hanya lakukan penyesuaian (pengurangan) jika alokasi saat ini memang lebih tinggi dari batas akhir 20%
            // DAN truk sudah penuh atau over. Ini mencegah pengurangan yang tidak perlu.
            // PERHATIAN: Di sini, kita tidak perlu peduli lagi dengan shipmentSizePercent.
            if ($entry['alloc_qty'] > $new_alloc_qty) {
                 $entry['alloc_qty'] = $new_alloc_qty;
                 $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
            }
        }

        // Hanya masukkan entry ke hasil akhir jika allocated quantity > 0.
        if ($entry['alloc_qty'] > 0) {
            // Hitung Volume Ratio (%)
            $volume_ratio = ($shipsize > 0)
                ? round(($entry['used_volume'] / $shipsize) * 100, 2)
                : 0;

            // Hitung Truck Size (persentase kontribusi SKU terhadap total shipsize)
            // Ini akan menunjukkan kontribusi SKU tanpa dibatasi shipmentSizePercent.
            $truck_size = ($shipsize > 0)
                ? round(($entry['alloc_qty'] * $entry['volume_per_ctn']) / $shipsize, 2)
                : 0;

            // Hitung CSL (%) kembali untuk output final.
            $cmo_final = max((int)$entry['row']['CMO FINAL'], 1);
            $actual = (int)$entry['row']['ACTUAL'];
            $do_planning = (int)$entry['row']['DO PLANNING'];
            $csl = round((($actual + $do_planning) / $cmo_final) * 100, 2);

            // Tambahkan data SKU yang sudah difinalisasi ke array hasil akhir.
            $final_result[] = [
                'MIXCUST' => $entry['mix_cust'],
                'CUST#' => $entry['cust'],
                'CUSTOMER - CITY/CHANNEL' => $entry['row']['CUSTOMER - CITY/CHANNEL'],
                'CHANNEL CUST' => $mixcust_map[$entry['cust']]['channel_cust'] ?? '',
                'SKU' => $entry['sku'],
                'MATERIAL DESCRIPTION' => $entry['row']['MATERIAL DESCRIPTION'],
                'CHANNEL' => $entry['channel'],
                'PULAU' => $mixcust_map[$entry['cust']]['pulau'] ?? '',
                'SHIPMENT TYPE' => $mixcust_map[$entry['cust']]['shipment_type'] ?? '',
                'REGION SCM' => $mixcust_map[$entry['cust']]['region_scm'] ?? '',
                'OTD CMO' => $entry['cmo'],
                'CMO FINAL' => $cmo_final,
                'ACTUAL' => $actual,
                'DO PLANNING' => (int)$entry['row']['DO PLANNING'],
                'SISA QTY' => $entry['stock'],
                'Final Allocated QTY' => $entry['alloc_qty'],
                'Volume per CTN' => $entry['volume_per_ctn'],
                'Used Volume (m3)' => round($entry['used_volume'], 2),
                'SHIPMENT SIZE' => $shipsize,
                'Volume Ratio (%)' => $volume_ratio,
                'Truck Size' => $truck_size,
                'CSL (%)' => $csl
            ];
        }
    }
    unset($entry); // Penting: Lepaskan referensi pada elemen array.
}

// Menyimpan hasil alokasi akhir ke session. Ini memungkinkan hasil digunakan di halaman lain atau untuk laporan.
$_SESSION['allocation_result'] = $final_result;

// Mengembalikan hasil dalam format JSON.
// Ini biasanya digunakan untuk respons AJAX ke frontend.
// echo json_encode($final_result);