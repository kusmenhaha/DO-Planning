<?php
session_start();
require_once('../../services/database.php');
include 'update_cmo_data.php';

$cmo_data = $_SESSION['cmo_temp_data_update'] ?? [];
$stock_data = $_SESSION['final_stock'] ?? [];
$allocationsettings = $_SESSION['allocation_settings'] ?? [];

if (empty($cmo_data) || empty($stock_data) || empty($allocationsettings)) {
    echo json_encode(['status' => 'error', 'message' => 'Data CMO atau Stock kosong, atau pengaturan alokasi kosong.']);
    exit;
}

//Mapping Allocation Settings
$allocation_map = [];
   $allocation_map = [
    'minCarton' => $allocationsettings['minCarton'] ?? 0,
    'shipmentSizePercent' => $allocationsettings['shipmentSizePercent'] ?? 0,
    'rangeFullCMO' => $allocationsettings['rangeFullCMO'] ?? ''
];


// Mapping stock data berdasarkan Sisa QTY (jangan kurang dari 0)
$stock_map = [];
foreach ($stock_data as $stock) {
    $key = $stock['SKU'] . '_' . $stock['CHANNEL'];
    $stock_map[$key] = max((int)$stock['Sisa QTY'], 0);
}

// Ambil data mixcust dari database
$mixcust_map = [];
$res_mix = mysqli_query($conn, "SELECT cust, mix_cust, shipsizemajalengka, channel_cust, pulau, shipment_type, region_scm FROM mixcust");
while ($row = mysqli_fetch_assoc($res_mix)) {
    $mixcust_map[$row['cust']] = [
        'mix_cust' => $row['mix_cust'],
        'shipsize' => (float)$row['shipsizemajalengka'],
        'channel_cust' => $row['channel_cust'],
        'pulau' => $row['pulau'],
        'shipment_type' => $row['shipment_type'],
        'region_scm' => $row['region_scm']
    ];
}

// Ambil data volume per SKU dari itembank
$itembank_map = [];
$res_itembank = mysqli_query($conn, "SELECT sku, volume FROM itembank");
while ($row = mysqli_fetch_assoc($res_itembank)) {
    $itembank_map[$row['sku']] = (float)$row['volume'];
}

$pre_allocations = [];

// Tahap awal alokasi berdasarkan CMO dan stok
foreach ($cmo_data as $row) {
    $cust = $row['CUST#'];
    $sku = $row['SKU'];
    $channel = $row['CHANNEL'];
    $cmo = (int)$row['OTD CMO'];
    $minCarton = $allocation_map['minCarton'];
    $shipmentSizePercent = $allocation_map['shipmentSizePercent'];
    $rangeFullCMO = $allocation_map['rangeFullCMO'];

    // Skip jika data cust atau sku tidak lengkap
    if (!isset($mixcust_map[$cust]) || !isset($itembank_map[$sku])) continue;

    $mix_cust = $mixcust_map[$cust]['mix_cust'];
    $volume_per_ctn = $itembank_map[$sku];
    $key = $sku . '_' . $channel;
    $stock = $stock_map[$key] ?? 0;

    // Skip jika volume per ctn <=0, stock <=0 atau CMO kurang dari 10
    if ($volume_per_ctn <= 0 || $stock <= $minCarton || $cmo < $minCarton) continue;

    // Alokasi qty minimal CTN jika kondisi terpenuhi
    $alloc_qty = min($cmo, $stock);
    if ($cmo >= $minCarton && $stock >= $minCarton) {
    if (
        ($cmo >= $minCarton && $cmo <= $rangeFullCMO) ||
        ($stock >= $minCarton && $stock <= $rangeFullCMO)
    ) {
        // Jika antara minCarton dan rangeFullCMO → kirim full OTD CMO (jika stock cukup)
        $alloc_qty = min($cmo, $stock);
    } else {
        // Alokasi normal → ambil yang lebih kecil
        $alloc_qty = min($cmo, $stock);

        // Jika hasil lebih kecil dari minCarton, tapi CMO & stok cukup → tetap pakai minCarton
        if ($alloc_qty < $minCarton && $stock >= $minCarton && $cmo >= $minCarton) {
            $alloc_qty = $minCarton;
        }
    }
}

    $used_volume = $alloc_qty * $volume_per_ctn;

    $pre_allocations[$mix_cust][] = [
        'row' => $row,
        'cust' => $cust,
        'sku' => $sku,
        'channel' => $channel,
        'mix_cust' => $mix_cust,
        'volume_per_ctn' => $volume_per_ctn,
        'alloc_qty' => $alloc_qty,
        'used_volume' => $used_volume,
        'cmo' => $cmo,
        'stock' => $stock
    ];
}

$final_result = [];

// Proses penyesuaian alokasi berdasarkan batasan shipment size
foreach ($pre_allocations as $mix_cust => $entries) {
    // Dapatkan shipsize dari mixcust
    $shipsize = 0;
    
    foreach ($mixcust_map as $data) {
        if ($data['mix_cust'] == $mix_cust) {
            $shipsize = $data['shipsize'];
            break;
        }
    }

    $total_volume = array_sum(array_column($entries, 'used_volume'));

    if ($total_volume > $shipsize) {
        
        // Jika total volume melebihi kapasitas, kurangi alokasi
        usort($entries, fn($a, $b) => $b['used_volume'] <=> $a['used_volume']);
        $excess = $total_volume - $shipsize;

        foreach ($entries as &$entry) {
            if ($excess <= 0) break;

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
        unset($entry);

    } elseif ($total_volume < $shipsize) {
        // Jika volume kurang dari shipsize, coba tambah alokasi sesuai stock dan CMO
        $deficit = $shipsize - $total_volume;
        $total_alloc_volume = array_sum(array_column($entries, 'used_volume'));

        foreach ($entries as &$entry) {
            if ($deficit <= 0) break;

            $entry_share = $entry['used_volume'] / $total_alloc_volume;
            $additional_volume = $entry_share * $deficit;
            $additional_qty = floor($additional_volume / $entry['volume_per_ctn']);

            $available_stock = $entry['stock'] - $entry['alloc_qty'];
            $available_cmo = $entry['cmo'] - $entry['alloc_qty'];
            $max_addable_qty = min($available_stock, $available_cmo);

            $add_qty = min($additional_qty, $max_addable_qty);

            if ($add_qty > 0) {
                $entry['alloc_qty'] += $add_qty;
                $entry['used_volume'] = $entry['alloc_qty'] * $entry['volume_per_ctn'];
                $deficit -= $add_qty * $entry['volume_per_ctn'];
            }
        }
        unset($entry);
    }

    // Prepare data akhir untuk output
    foreach ($entries as $entry) {
        $volume_ratio = ($shipsize > 0)
            ? round(($entry['used_volume'] / $shipsize) * 100, 2)
            : 0;

        $truck_size = ($shipsize > 0)
            ? round(($entry['alloc_qty'] * $entry['volume_per_ctn']) / $shipsize, 2)
            : 0;

        $cmo_final = max((int)$entry['row']['CMO FINAL'], 1); // Hindari pembagian dengan nol
        $actual = (int)$entry['row']['ACTUAL'];
        $do_planning = (int)$entry['row']['DO PLANNING'];
        $csl = round((($actual + $do_planning) / $cmo_final) * 100, 2);

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
            'DO PLANNING' => $do_planning,
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

// Simpan hasil ke session untuk digunakan selanjutnya
$_SESSION['allocation_result'] = $final_result;

// Kembalikan hasil dalam format JSON
// echo json_encode( $final_result);
