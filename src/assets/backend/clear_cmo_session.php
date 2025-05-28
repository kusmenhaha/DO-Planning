<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['cmo_temp_data'])) {
    unset($_SESSION['cmo_temp_data']);
    echo json_encode(['status' => 'success', 'message' => 'Data CMO sementara berhasil dihapus.']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'Tidak ada data CMO sementara untuk dihapus.']);
}
?>