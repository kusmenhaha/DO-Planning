<?php
// session_start();
header('Content-Type: application/json');

// Validasi session input
if (!isset($_SESSION['cmo_temp_data']) || !is_array($_SESSION['cmo_temp_data'])) {
    echo json_encode(['error' => 'Session cmo_temp_data tidak ditemukan atau format salah.']);
    exit;
}

if (!isset($_SESSION['selected_data']) || !is_array($_SESSION['selected_data'])) {
    $_SESSION['selected_data'] = []; // kosongkan saja kalau tidak ada, agar proses tetap jalan
}

$cmo_temp_data = $_SESSION['cmo_temp_data'];
$selected_data = $_SESSION['selected_data'];
$updated_data = [];

// Fungsi pencocokan berdasarkan CUST#, CUSTOMER, SKU, CHANNEL
function is_match($a, $b) {
    return $a['CUST#'] === $b['CUST#'] &&
           $a['CUSTOMER - CITY/CHANNEL'] === $b['CUSTOMER - CITY/CHANNEL'] &&
           $a['SKU'] === $b['SKU'] &&
           $a['CHANNEL'] === $b['CHANNEL'];
}

foreach ($cmo_temp_data as $cmo_item) {
    $matched_alloc_qty = 0;

    // Cari alokasi jika cocok
    foreach ($selected_data as $selected_item) {
        if (is_match($cmo_item, $selected_item)) {
            $matched_alloc_qty += floatval($selected_item['Final Allocated QTY']);
        }
    }

    // Hitung OTD CMO dan DO PLANNING baru
    $new_otd_cmo = floatval($cmo_item['OTD CMO']) - $matched_alloc_qty;
    $new_do_planning = floatval($cmo_item['DO PLANNING']) + $matched_alloc_qty;

    $updated_item = [
        'CUST#' => $cmo_item['CUST#'],
        'CUSTOMER - CITY/CHANNEL' => $cmo_item['CUSTOMER - CITY/CHANNEL'],
        'SKU' => $cmo_item['SKU'],
        'MATERIAL DESCRIPTION' => $cmo_item['MATERIAL DESCRIPTION'],
        'VOLUME' => $cmo_item['VOLUME'],
        'CHANNEL' => $cmo_item['CHANNEL'],
        'OTD CMO' => $new_otd_cmo,
        'CMO FINAL' => $cmo_item['CMO FINAL'],
        'ACTUAL' => $cmo_item['ACTUAL'],
        'DO PLANNING' => $new_do_planning
    ];

    $updated_data[] = $updated_item;
}

// Simpan session hasil
$_SESSION['cmo_temp_data_update'] = $updated_data;

// Kirim hasil
// echo json_encode([
//     'success' => true,
//     'updated_count' => count($updated_data),
//     'data' => $updated_data
// ]);
?>
