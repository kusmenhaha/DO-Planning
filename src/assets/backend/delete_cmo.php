<?php
include '../../services/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rowIndex'])) {
    $rowIndex = (int)$_POST['rowIndex'];

    if (isset($_SESSION['cmo_temp_data']) && is_array($_SESSION['cmo_temp_data'])) {
        if ($rowIndex >= 0 && $rowIndex < count($_SESSION['cmo_temp_data'])) {
            array_splice($_SESSION['cmo_temp_data'], $rowIndex, 1);
            echo json_encode(['status' => 'success', 'message' => 'Baris berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Indeks baris tidak valid.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Data CMO tidak ditemukan dalam sesi.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
}
?>