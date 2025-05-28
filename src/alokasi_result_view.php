<?php
session_start();
$data = $_SESSION['alokasi_result'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Hasil Alokasi</title>
  <link href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 20px;
      background: #f9f9f9;
    }
    #allocationGrid {
      background: #fff;
      padding: 15px;
      border-radius: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    #exportExcel {
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

<h2>Hasil Alokasi Pengiriman</h2>
<button id="exportExcel">Export ke Excel</button>
<div id="allocationGrid"></div>

<script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
  const data = <?php echo json_encode($data); ?>;

  const grid = new gridjs.Grid({
    columns: [
      "CUST",
      "MIX_CUST",
      "SKU",
      "OTD_CMO",
      "STOCK",
      "MIN_CTN",
      "ALLOCATED_QTY",
      "VOLUME_PER_CTN",
      "ALLOCATED_VOLUME"
    ],
    data: data.map(row => [
      row.CUST,
      row.MIX_CUST,
      row.SKU,
      row.OTD_CMO,
      row.STOCK,
      row.MIN_CTN,
      row.ALLOCATED_QTY,
      row.VOLUME_PER_CTN,
      row.ALLOCATED_VOLUME
    ]),
    pagination: true,
    search: true,
    sort: true,
    style: {
      table: {
        fontSize: "14px"
      }
    }
  }).render(document.getElementById("allocationGrid"));

  document.getElementById("exportExcel").addEventListener("click", () => {
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Alokasi");
    XLSX.writeFile(wb, "Hasil_Alokasi.xlsx");
  });
</script>

</body>
</html>
