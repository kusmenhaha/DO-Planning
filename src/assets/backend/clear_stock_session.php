<?php
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['stock_temp_data'])) {
    unset($_SESSION['stock_temp_data']);
    $response = ['status' => 'success', 'message' => 'Data stock sementara berhasil dihapus.'];
    error_log("Stock temporary data successfully cleared from session.");
} else {
    $response = ['status' => 'info', 'message' => 'Tidak ada data stock sementara untuk dihapus.'];
    error_log("No stock temporary data found in session to clear.");
}

echo json_encode($response);
exit;
?>