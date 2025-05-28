document.addEventListener("DOMContentLoaded", function () {
  const overlay = document.getElementById("loadingOverlay");
  overlay.style.display = "block";

  let gridData = []; // Simpan data untuk eksport Excel

  fetch("assets/backend/get_selected_data.php")
    .then(res => res.json())
    .then(data => {
      overlay.style.display = "none";

      gridData = data; // Simpan untuk eksport nanti

      // Render Grid
      new gridjs.Grid({
        columns: Object.keys(data[0] || {}).map(key => ({
          name: key.toUpperCase().replace(/_/g, ' '),
          id: key
        })),
        data: data.map(row => Object.values(row)),
        pagination: { enabled: true, limit: 10 },
        search: true,
        sort: true,
        resizable: true,
        style: {
          table: { width: '100%' }
        }
      }).render(document.getElementById("allocationGrid"));

      const gridContainer = document.getElementById("allocationGrid");
      const parent = gridContainer.parentNode;

      // Tambahkan tombol kembali ke allocation_view.php
      const btnBackAllocation = document.createElement("button");
      btnBackAllocation.textContent = "Back to Planning Result";
      btnBackAllocation.className = "btn btn-secondary mb-3 me-2";
      btnBackAllocation.onclick = () => {
        window.location.href = "allocation_view.php";
      };

      // Tambahkan tombol kembali ke pick.php
      const btnBackPick = document.createElement("button");
      btnBackPick.textContent = "Back to Pick Customer";
      btnBackPick.className = "btn btn-secondary mb-3 me-2";
      btnBackPick.onclick = () => {
        window.location.href = "pick.php";
      };

      // Tambahkan tombol export ke Excel
      const exportBtn = document.createElement("button");
      exportBtn.textContent = "Export to Excel";
      exportBtn.className = "btn btn-success mb-3";
      
      exportBtn.onclick = () => {
        if (gridData.length === 0) {
          alert("Tidak ada data untuk diekspor.");
          return;
        }

        const worksheet = XLSX.utils.json_to_sheet(gridData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, "Data Alokasi");
        XLSX.writeFile(workbook, "selected_data.xlsx");
      };

      // Sisipkan tombol-tombol sebelum gridContainer
      parent.insertBefore(btnBackAllocation, gridContainer);
      parent.insertBefore(btnBackPick, gridContainer);
      parent.insertBefore(exportBtn, gridContainer);
    })
    .catch(err => {
      overlay.style.display = "none";
      console.error("Gagal mengambil data:", err);
      document.getElementById("allocationGrid").innerHTML = "<p class='text-danger'>Gagal menampilkan data.</p>";
    });
});
