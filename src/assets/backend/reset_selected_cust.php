<?php
session_start();
header('Content-Type: application/json');

unset($_SESSION['selected_data']);
unset($_SESSION['selected_custs']);

if (isset($_SESSION['allocation_result_backup'])) {
  $_SESSION['allocation_result'] = $_SESSION['allocation_result_backup'];
}

echo json_encode(['status' => 'success']);
