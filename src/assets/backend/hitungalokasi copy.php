<?php
session_start();
require_once('../../services/database.php');
include 'update_cmo_data.php';
// include 'get_stock_data.php';

// --- Validasi data sesi ---
if (empty($_SESSION['cmo_temp_data_update']) || empty($_SESSION['stock_temp_data']) || empty($_SESSION['allocation_settings'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data CMO, Stock, atau Pengaturan Alokasi tidak ditemukan di sesi. Mohon pastikan semua data telah dimuat sebelum menjalankan proses alokasi.']);
    exit;
}

$cmo_data = $_SESSION['cmo_temp_data_update'];
$stock_data = $_SESSION['stock_temp_data'];
$allocationsettings = $_SESSION['allocation_settings'];

// --- Inisialisasi variabel setting ---
$minCarton = $allocationsettings['minCarton'] ?? 0;
$rangeFullCMO = (int)($allocationsettings['rangeFullCMO'] ?? 0);
$skuLimit = ($allocationsettings['SkuLimit'] ?? 0) == 0 ? PHP_INT_MAX : (int)($allocationsettings['SkuLimit'] ?? PHP_INT_MAX);
$MaxCMO = ($allocationsettings['MaxCMO'] ?? 0);

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

// --- Menyimpan data alokasi sementara per mixcust ---
$skus_by_mixcust = [];

// --- Proses awal perhitungan alokasi ---
foreach ($cmo_data as $row) {
    $cust = $row['CUST#'] ?? '';
    $sku = $row['SKU'] ?? '';
    $channel = $row['CHANNEL'] ?? '';
    $cmo_otd = (int)($row['OTD CMO'] ?? 0);

    // Validasi data dan mapping
    if (empty($cust) || empty($sku) || !isset($mixcust_map[$cust]) || !isset($itembank_map[$sku])) {
        continue;
    }

    $mix_cust = $mixcust_map[$cust]['mix_cust'];
    $volume_per_ctn = $itembank_map[$sku];
    $stock = $stock_map[$sku . '_' . $channel] ?? 0;

    // Hitung CSL (Customer Service Level)
    $cmo_final_for_csl = max((int)($row['CMO FINAL'] ?? 1), 1);
    $actual_for_csl = (int)($row['ACTUAL'] ?? 0);
    $do_planning_for_csl = (int)($row['DO PLANNING'] ?? 0);
    $csl = round((($actual_for_csl + $do_planning_for_csl) / $cmo_final_for_csl) * 100, 2);

    // Hitung alokasi: ambil min antara OTD CMO dan Stock, tapi hanya jika keduanya >= minCarton
    $final_qty = 0;
    if ($cmo_otd >= $minCarton && $stock >= $minCarton) {
        $final_qty = min($cmo_otd, $stock);
    }
    // Hitung volume yang digunakan
    $final_volume = $final_qty * $volume_per_ctn;

    // Ambil shipsize dari mixcust
    $shipsize = $mixcust_map[$cust]['shipsize'] ?? 0;

    // Perhitungan volume ratio (jika shipsize > 0)
    $volume_ratio = $shipsize > 0 ? round(($final_volume / $shipsize) * 100, 2) : 0;

    // Hitung jumlah truk (truck size) berdasarkan asumsi kapasitas truk, misal satu truk muat 33 m3 (bisa disesuaikan)
    $truck_capacity_m3 = 33;
    $truck_size = ceil($final_volume / $truck_capacity_m3);

    // Data output disusun sesuai format JSON yang diinginkan
    $final_allocated_data[] = [
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
        'SISA QTY' => $stock,
        'Final Allocated QTY' => $final_qty,
        'Volume per CTN' => $volume_per_ctn,
        'Used Volume (m3)' => round($final_volume, 2),
        'SHIPMENT SIZE' => $shipsize,
        'Volume Ratio (%)' => $volume_ratio,
        'Truck Size' => $truck_size,
        'CSL (%)' => $csl
    ];
}

// --- Output hasil JSON ---
echo json_encode([
    'status' => 'success',
    'data' => $final_allocated_data
]);
