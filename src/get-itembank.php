<?php
include __DIR__ . '/services/database.php';

$sql = "SELECT sku, `desc`, sls_org, channel, volume, `matl_group`, plant, parent, size_dimensions, country, gross_weight, bun, division, family_product FROM itembank";
$result = $conn->query($sql);

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = array_values($row); // Ubah ke indexed array untuk Grid.js
}

header('Content-Type: application/json');
echo json_encode($data);
?>
