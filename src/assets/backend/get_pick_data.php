<?php
session_start();
header('Content-Type: application/json');

$allocationData = $_SESSION['allocation_result'] ?? [];
$selectedMixCusts = $_SESSION['selected_mixcusts'] ?? [];
if (empty($allocationData)) {
  http_response_code(400);
  echo json_encode(['error' => 'Belum ada data alokasi.']);
  exit;
}

$filtered = array_filter($allocationData, function($row) use ($selectedMixCusts) {
  return !in_array($row['MIXCUST'], $selectedMixCusts ?? []);
});

// summary per MIXCUST
$summary = [];
foreach ($filtered as $row) {
  $mixcust = $row['MIXCUST'];

  if (!isset($summary[$mixcust])) {
    $summary[$mixcust] = [
      'MIXCUST' => $mixcust,
      'CUSTS' => [],
      'CUSTOMER - CITY/CHANNEL' => $row['CUSTOMER - CITY/CHANNEL'],
      'SHIPEMENT SIZE' => $row['SHIPEMENT SIZE'] ?? 0,
      'USED_VOLUME' => 0,
      'FINAL_ALLOCATED_QTY' => 0,
      'VOLUME_RATIO' => 0,
    ];
  }

  $summary[$mixcust]['CUSTS'][] = $row['CUST#'];
  $summary[$mixcust]['USED_VOLUME'] += floatval($row['Used Volume (m3)'] ?? 0);
  $summary[$mixcust]['FINAL_ALLOCATED_QTY'] += floatval($row['Final Allocated QTY'] ?? 0);
  $summary[$mixcust]['VOLUME_RATIO'] += floatval($row['Volume Ratio (%)'] ?? 0);
}

// hitung rata-rata ratio
foreach ($summary as &$item) {
  $custCount = count($item['CUSTS']);
  $item['VOLUME_RATIO'] = $custCount > 0 ? round($item['VOLUME_RATIO'] / $custCount, 2) : 0;
  $item['CUSTS'] = implode(', ', array_unique($item['CUSTS']));
}

echo json_encode(array_values($summary));
