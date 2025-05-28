<?php
session_start();
header('Content-Type: application/json');

$allocation = $_SESSION['allocation_result'] ?? [];
$existingCusts = $_SESSION['selected_custs'] ?? [];

$summary = [];

foreach ($allocation as $row) {
  if (in_array($row['CUST#'], $existingCusts)) continue;

  $key = $row['MIXCUST'] . '|' . $row['CUST#'];

  if (!isset($summary[$key])) {
    $summary[$key] = [
      "MIXCUST" => $row['MIXCUST'],
      "CUST" => $row['CUST#'],
      "CUSTOMER - CITY/CHANNEL" => $row['CUSTOMER - CITY/CHANNEL'],
      "CHANNEL CUST" => $row['CHANNEL CUST'] ?? '',
      "PULAU" => $row['PULAU'] ?? '',
      "REGION SCM" => $row["REGION SCM"] ?? '',
      "SHIPMENT TYPE" => $row['SHIPMENT TYPE'] ?? '',
      "SHIPMENT SIZE" => floatval($row['SHIPMENT SIZE'] ?? 0),
      "USED_VOLUME" => 0,
      "FINAL_ALLOCATED_QTY" => 0,
      "TRUCK_SIZE_TOTAL" => 0,
      "CSL_TOTAL" => 0,
      "count" => 0
    ];
  }

  $summary[$key]['USED_VOLUME'] += floatval($row['Used Volume (m3)']);
  $summary[$key]['FINAL_ALLOCATED_QTY'] += floatval($row['Final Allocated QTY']);
  $summary[$key]['TRUCK_SIZE_TOTAL'] += floatval($row['Truck Size']);
  $summary[$key]['CSL_TOTAL'] += floatval($row['CSL (%)']);
  $summary[$key]['count']++;
}

// Hitung rata-rata
foreach ($summary as &$row) {
  $count = max($row['count'], 1);

  $row['AVG_TRUCK_SIZE'] = round($row['TRUCK_SIZE_TOTAL'] / $count, 2);
  $row['AVG_CSL (%)'] = round($row['CSL_TOTAL'] / $count, 2);
  $shipmentSize = $row['SHIPMENT SIZE'];
  $usedVolume = $row['USED_VOLUME'];
  $row['AVG_VOLUME_RATIO (%)'] = ($shipmentSize > 0) ? round(($usedVolume / $shipmentSize), 2) : 0;

  unset($row['TRUCK_SIZE_TOTAL'], $row['CSL_TOTAL'], $row['count']);
}

echo json_encode(array_values($summary));
