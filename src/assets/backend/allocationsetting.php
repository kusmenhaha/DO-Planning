<?php
session_start();
header('Content-Type: application/json');

// Ambil data dari request
$data = json_decode(file_get_contents("php://input"), true);

// Validasi input
if (!isset($data['minCarton']) || !isset($data['shipmentSizePercent']) || !isset($data['rangeFullCMO']) || !isset($data['SkuLimit']) ||!isset($data['MaxCMO'])) {
    echo json_encode(["success" => false, "message" => "Data tidak lengkap"]);
    exit;
}

// Simpan ke session
$_SESSION['allocation_settings'] = [
    'minCarton' => (int)$data['minCarton'],
    'shipmentSizePercent' => (float)$data['shipmentSizePercent'],
    'rangeFullCMO' => $data['rangeFullCMO'],
    'SkuLimit' => (int)$data['SkuLimit'],
    'MaxCMO' => (float)$data['MaxCMO']  // Konversi ke integer
];

echo json_encode(["success" => true]);