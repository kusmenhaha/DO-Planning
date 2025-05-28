<?php
session_start();
header('Content-Type: application/json');

// Ambil dari session, jika tidak ada default kosong
$settings = $_SESSION['allocation_settings'] ?? [
    'minCarton' => '',
    'shipmentSizePercent' => '',
    'rangeFullCMO' => '',
    'SkuLimit' => '',
    'MaxCMO' => '' // <<< Pastikan ini 'MaxCMO' (dengan O besar)
];

// Pastikan SkuLimit tetap ada meskipun belum pernah diset
if (!isset($settings['SkuLimit'])) {
    $settings['SkuLimit'] = '';
}

echo json_encode([
    'success' => true,
    'settings' => $settings
]);
?>