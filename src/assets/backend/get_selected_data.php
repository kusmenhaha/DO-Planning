<?php
session_start();
header('Content-Type: application/json');

// Ambil data dari session
$data = $_SESSION['selected_data'] ?? [];

// Pastikan data dalam format array

echo json_encode($data);
