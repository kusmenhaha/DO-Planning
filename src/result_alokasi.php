<?php
session_start();

// Contoh data dari session (pastikan diisi di proses sebelumnya)
$data = $_SESSION['allocation_result'] ?? [];

// Jika kamu mau test tanpa session, bisa pakai contoh data ini:
// $data = [
//     ['cust' => 'CUST001', 'sku' => 'SKU001', 'qty' => 15, 'volume' => 2, 'allocation' => 10],
//     ['cust' => 'CUST002', 'sku' => 'SKU002', 'qty' => 20, 'volume' => 1.5, 'allocation' => 18],
// ];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>Hasil Alokasi Pengiriman</title>
    <link rel="stylesheet" href="assets/css/alokasi-style.css" />
</head>
<body>
    <h1>Hasil Alokasi Pengiriman</h1>

    <?php if (!empty($data)) : ?>
        <button id="export-xlsx">Export ke XLSX</button>

        <table id="alokasiTable" border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>CUST</th>
                    <th>SKU</th>
                    <th>QTY</th>
                    <th>Volume</th>
                    <th>Allocation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['cust']) ?></td>
                    <td><?= htmlspecialchars($row['sku']) ?></td>
                    <td><?= (int)$row['qty'] ?></td>
                    <td><?= (float)$row['volume'] ?></td>
                    <td><?= (int)$row['allocation'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Tidak ada data alokasi ditemukan.</p>
    <?php endif; ?>

    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script src="assets/js/alokasi-xlsx.js"></script>
</body>
</html>
