<?php
// Panggil ulang proses alokasi (update $_SESSION['allocation_result'])
require_once __DIR__ . '/hitungalokasi.php';

// session_start();

// Ambil data alokasi terbaru dari session
$alokasi = $_SESSION['allocation_result'] ?? [];

// Format ulang untuk Grid.js
$formatted = [];

foreach ($alokasi as $row) {
    $formatted[] = [
        "MIXCUST" => $row["MIXCUST"] ?? '',
        "CUST#" => $row["CUST#"] ?? '',
        "CUSTOMER - CITY/CHANNEL" => $row["CUSTOMER - CITY/CHANNEL"] ?? '',
        "CHANNEL CUST" => $row["CHANNEL CUST"] ?? '',
        "PULAU" => $row["PULAU"] ?? '',
        "REGION SCM" => $row["REGION SCM"] ?? '',
        "SHIPMENT TYPE" => $row["SHIPMENT TYPE"] ?? '',
        "SKU" => $row["SKU"] ?? '',
        "MATERIAL DESCRIPTION" => $row["MATERIAL DESCRIPTION"] ?? '',
        "CHANNEL" => $row["CHANNEL"] ?? '',
        "OTD_CMO" => $row["OTD CMO"] ?? 0,
        "CMO FINAL" => $row["CMO FINAL"] ?? 0,
        "ACTUAL" => $row["ACTUAL"] ?? 0,
        "DO PLANNING" => $row["DO PLANNING"] ?? 0,
        "VOLUME PER CTN" => $row["Volume per CTN"] ?? 0,
        "USED_VOLUME" => $row["Used Volume (m3)"] ?? 0,
        "SISA_STOCK" => $row["SISA QTY"] ?? 0,
        "FINAL ALLOCATED QTY" => $row["Final Allocated QTY"] ?? 0,
        "VOLUME_RATIO" => $row["Volume Ratio (%)"] ?? 0,
        "SHIPMENT SIZE" => $row["SHIPEMENT SIZE"] ?? 0,
        "TRUCK SIZE" => $row["Truck Size"] ?? 0,
        "CSL (%)" => $row["CSL (%)"] ?? 0,
    ];
}

header('Content-Type: application/json');
echo json_encode($formatted);