<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['allocation_settings'])) {
    unset($_SESSION['allocation_settings']);
}

echo json_encode(["success" => true]);
