let allocationData = []; // Data global untuk export



document.addEventListener("DOMContentLoaded", function () {
  
  const grid = new gridjs.Grid({
    columns: [
      { name: "MIXCUST", id: "MIXCUST" },
      { name: "CUST#", id: "CUST#" },
      { name: "CUSTOMER - CITY/CHANNEL", id: "CUSTOMER - CITY/CHANNEL" },
      { name: "SKU", id: "SKU" },
      { name: "MATERIAL DESCRIPTION", id: "MATERIAL DESCRIPTION" },
      { name: "CHANNEL", id: "CHANNEL" },
      { name: "CHANNEL CUST", id: "CHANNEL CUST" },
      { name: "PULAU", id: "PULAU" },
      { name: "SHIPMENT TYPE", id: "SHIPMENT TYPE" },
      { name: "VOLUME PER CTN", id: "VOLUME PER CTN" },
      { name: "USED_VOLUME", id: "USED_VOLUME" },
      { name: "OTD_CMO", id: "OTD_CMO" },
      { name: "CMO_FINAL", id: "CMO FINAL" },
      { name: "DO PLANNING", id: "DO PLANNING" },
      { name: "ACTUAL", id: "ACTUAL" },
      { name: "SISA_STOCK", id: "SISA_STOCK" },
      { name: "FINAL ALLOCATED QTY", id: "FINAL ALLOCATED QTY" },
      { name: "SHIPMENT SIZE", id: "SHIPMENT SIZE" },
      { name: "VOLUME_RATIO", id: "VOLUME_RATIO" },
      { name: "TRUCK SIZE", id: "TRUCK SIZE" },
      { name: "CSL (%)", id: "CSL (%)" },
      
    ],
    server: {
      url: "assets/backend/get_allocation_data.php",
      then: data => {
        allocationData = data;
        return data.map(row => ({
          "MIXCUST": row["MIXCUST"],
          "CUST#": row["CUST#"],
          "CUSTOMER - CITY/CHANNEL": row["CUSTOMER - CITY/CHANNEL"],
          "SKU": row["SKU"],
          "MATERIAL DESCRIPTION": row["MATERIAL DESCRIPTION"],
          "CHANNEL": row["CHANNEL"],
          "CHANNEL CUST": row["CHANNEL CUST"],
          "PULAU": row["PULAU"],
          "SHIPMENT TYPE": row["SHIPMENT TYPE"],
          "VOLUME PER CTN": row["VOLUME PER CTN"],
          "USED_VOLUME": row["USED_VOLUME"],
          "OTD_CMO": row["OTD_CMO"],
          "CMO FINAL": row["CMO FINAL"],
          "DO PLANNING": row["DO PLANNING"],
          "ACTUAL": row["ACTUAL"],
          "SISA_STOCK": row["SISA_STOCK"],
          "FINAL ALLOCATED QTY": row["FINAL ALLOCATED QTY"],
          "SHIPMENT SIZE": row["SHIPMENT SIZE"],
          "VOLUME_RATIO": row["VOLUME_RATIO"],
          "TRUCK SIZE": row["TRUCK SIZE"],
          "CSL (%)": row["CSL (%)"],
        }));
      }
    },
    search: {
      enabled: true,
      placeholder: 'Cari...'
    },
    pagination: {
      enabled: true,
      limit: 10,
      summary: true
    },
    sort: true,
    resizable: true,
    language: {
      search: {
        placeholder: 'Cari...'
      },
      pagination: {
        previous: 'Sebelumnya',
        next: 'Berikutnya',
        showing: 'Menampilkan',
        results: () => 'data'
      }
    }
  });

  grid.render(document.getElementById("allocationGrid"));

  // Ambil elemen target untuk menyisipkan tombol
  const gridElement = document.getElementById("allocationGrid");
  const cardBody = gridElement?.parentElement;
  
  // Tombol Export ke Excel
  const exportBtn = document.createElement("button");
  exportBtn.textContent = "Export to Excel";
  exportBtn.className = "btn btn-success mb-3 me-2";
  exportBtn.onclick = () => {
    if (allocationData.length === 0) return alert("Tidak ada data untuk diekspor");
    const worksheet = XLSX.utils.json_to_sheet(allocationData);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Allocation");
    XLSX.writeFile(workbook, "alokasi_pengiriman.xlsx");
  };

  // Tombol Hitung Ulang
  const rerunBtn = document.createElement("button");
  rerunBtn.textContent = "Reload Alokasi";
  rerunBtn.className = "btn btn-primary mb-3 me-2";
  rerunBtn.onclick = async () => {
    if (!confirm("Yakin ingin menghitung ulang alokasi?")) return;
    document.getElementById("loadingOverlay").style.display = "flex";
    try {
      const res = await fetch("assets/backend/hitungalokasi.php");
      const msg = await res.text();
      alert("Alokasi berhasil dihitung ulang.");
      location.reload(); // Reload halaman
    } catch (err) {z
      alert("Gagal menghitung ulang alokasi.");
    } finally {
      document.getElementById("loadingOverlay").style.display = "none";
    }
    document.getElementById("loadingOverlay")?.addEventListener("click", () => {
        fetch("assets/backend/refresh_all_backend.php")
          .then(res => res.text())
          .then(msg => console.log("Refreshed backend:", msg))
          .catch(err => console.error("Gagal refresh backend:", err));

      });
      
  };

  // Tombol Kembali ke pick.php
  const backBtn = document.createElement("button");
  backBtn.textContent = "Kembali ke Pick";
  backBtn.className = "btn btn-secondary mb-3 me-2";
  backBtn.onclick = () => {
    window.location.href = "pick.php";
  };

  // Sisipkan tombol ke atas grid
  if (cardBody) {
    cardBody.insertBefore(backBtn, gridElement);
    cardBody.insertBefore(exportBtn, gridElement);
    cardBody.insertBefore(rerunBtn, gridElement);
  }
});

