document.addEventListener("DOMContentLoaded", function () {
  fetch('backend/hitungalokasi.php')
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        const rows = data.data.map(item => [
          item["CUST#"],
          item["SKU"],
          item["CHANNEL"],
          item["OTD_CMO"],
          item["SISA_STOCK"],
          item["ALLOCATED_QTY"],
          item["USED_VOLUME"],
          item["MIXCUST"]
        ]);

        new gridjs.Grid({
          columns: [
            "CUST#",
            "SKU",
            "CHANNEL",
            "OTD CMO",
            "SISA STOCK",
            "ALLOCATED QTY",
            "USED VOLUME",
            "MIX CUST"
          ],
          data: rows,
          pagination: {
            enabled: true,
            limit: 10
          },
          sort: true,
          search: true
        }).render(document.getElementById("table-wrapper"));
      } else {
        document.getElementById("table-wrapper").innerText = data.message;
      }
    })
    .catch(error => {
      console.error('Error:', error);
      document.getElementById("table-wrapper").innerText = "Gagal memuat data.";
    });
});
