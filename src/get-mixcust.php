<?php
include __DIR__ . '/services/database.php';

$sql = "SELECT cust,mix_cust,`custname`,pulau,region_scm,abm,channel_cust,shipment_type,port_kontainer,shipsizemajalengka,shipsizerancaekek,shipsizerdc FROM mixcust";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = array_values($row); // Ubah ke indexed array untuk Grid.js
}

header('Content-Type: application/json');
echo json_encode($data);
?>
