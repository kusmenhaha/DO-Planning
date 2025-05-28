<?php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$selectedCusts = $input['selectedCusts'] ?? [];

if (!isset($_SESSION['allocation_result'])) {
  echo json_encode(['status' => 'error', 'message' => 'No allocation result found.']);
  exit;
}

$allData = $_SESSION['allocation_result'];
$newSelected = [];

foreach ($selectedCusts as $item) {
  $mixcust = $item['mixcust'];
  foreach ($allData as $row) {
    if ($row['MIXCUST'] === $mixcust) {
      $newSelected[] = $row;
    }
  }
}

$_SESSION['selected_data'] = array_merge($_SESSION['selected_data'] ?? [], $newSelected);
$_SESSION['selected_custs'] = array_merge($_SESSION['selected_custs'] ?? [], array_column($newSelected, 'CUST#'));

$_SESSION['allocation_result'] = array_filter($allData, function ($row) {
  return !in_array($row['CUST#'], $_SESSION['selected_custs']);
});

echo json_encode(['status' => 'success']);
