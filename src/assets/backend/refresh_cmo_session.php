<?php
session_start();

if (isset($_SESSION['cmo_temp_data_update'])) {
    unset($_SESSION['cmo_temp_data_update']); // Hapus session lama
}

echo json_encode(['status' => 'success', 'message' => 'Session diperbarui']);
