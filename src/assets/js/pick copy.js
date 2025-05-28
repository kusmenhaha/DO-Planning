document.addEventListener("DOMContentLoaded", () => {
  let originalData = [];
  let mixcustStatusMap = {};

  // Tambah tombol Export Selected dan navigasi halaman
  const pickGrid = document.getElementById("pickGrid");
  if (pickGrid) {
    const exportBtn = document.createElement("button");
    exportBtn.textContent = "Export Selected";
    exportBtn.id = "btnExportSelected";
    exportBtn.style.marginBottom = "10px";

    const goAllocBtn = document.createElement("button");
    goAllocBtn.textContent = "Go to Allocation View";
    goAllocBtn.id = "btnGoAllocationView";
    goAllocBtn.style.marginLeft = "10px";
    goAllocBtn.style.marginBottom = "10px";

    const goResultfixBtn = document.createElement("button");
    goResultfixBtn.textContent = "Go to Result Fix";
    goResultfixBtn.id = "btnGoResultFix";
    goResultfixBtn.style.marginLeft = "10px";
    goResultfixBtn.style.marginBottom = "10px";

    pickGrid.parentNode.insertBefore(exportBtn, pickGrid);
    pickGrid.parentNode.insertBefore(goAllocBtn, pickGrid);
    pickGrid.parentNode.insertBefore(goResultfixBtn, pickGrid);

    goAllocBtn.addEventListener("click", () => {
      window.location.href = "allocation_view.php";
    });
    goResultfixBtn.addEventListener("click", () => {
      window.location.href = "resultfix.php";
    });
  }

  const calculateStatusByMixcust = (data) => {
    const mixcustGroups = {};
    data.forEach(row => {
      const mixcust = row.MIXCUST;
      const ratio = parseFloat(row["AVG_VOLUME_RATIO (%)"]) || 0;
      if (!mixcustGroups[mixcust]) mixcustGroups[mixcust] = [];
      mixcustGroups[mixcust].push(ratio);
    });

    mixcustStatusMap = {};
    for (const mixcust in mixcustGroups) {
      const ratios = mixcustGroups[mixcust];
      const avg = ratios.reduce((sum, r) => sum + r, 0) / ratios.length;
      mixcustStatusMap[mixcust] = avg >= 0.94 ? "Full" : "Kurang";
    }
  };

  // === fungsi simpan & load filter ke localStorage ===
  const filterIds = [
    "filterStatus",
    "filterCust",
    "filterCustomerCity",
    "filterChannelCust",
    "filterPulau",
    "filterRegionSCM",
    "filterShipmentType",
    "filterShortCSL" // filter baru Short CSL%
  ];

  function saveFiltersToStorage() {
    filterIds.forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        localStorage.setItem(id, el.value);
      }
    });
  }

  function loadFiltersFromStorage() {
    filterIds.forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        const val = localStorage.getItem(id);
        if (val !== null) el.value = val;
      }
    });
  }

  function clearFiltersStorage() {
    filterIds.forEach(id => {
      localStorage.removeItem(id);
    });
  }

  const filterAndRender = () => {
    const statusFilter = document.getElementById("filterStatus")?.value.toLowerCase() || "";
    const custFilter = document.getElementById("filterCust")?.value.toLowerCase() || "";
    const customerCityFilter = document.getElementById("filterCustomerCity")?.value.toLowerCase() || "";
    const channelCustFilter = document.getElementById("filterChannelCust")?.value.toLowerCase() || "";
    const pulauFilter = document.getElementById("filterPulau")?.value.toLowerCase() || "";
    const regionSCMFilter = document.getElementById("filterRegionSCM")?.value.toLowerCase() || "";
    const shipmentTypeFilter = document.getElementById("filterShipmentType")?.value.toLowerCase() || "";
    const shortCSLFilter = document.getElementById("filterShortCSL")?.value.toLowerCase() || ""; // filter baru

    saveFiltersToStorage(); // simpan filter setiap kali berubah

    const filteredData = originalData.filter(row => {
      const status = (mixcustStatusMap[row.MIXCUST] || "Kurang").toLowerCase();

      // nilai CSL dan AVG_VOLUME_RATIO dipastikan angka
      const cslVal = parseFloat(row["AVG_CSL (%)"]) || 0;
      const avgVolRatioVal = parseFloat(row["AVG_VOLUME_RATIO (%)"]) || 0;

      // filterShortCSL bisa "short" untuk nilai < threshold (misal < 94%)
      // Atau bisa kamu sesuaikan sesuai kebutuhan logika
      const shortCSLCheck = shortCSLFilter === "" || 
        (shortCSLFilter === "short" && cslVal < 94) || 
        (shortCSLFilter === "full" && cslVal >= 94);

      return (!statusFilter || status === statusFilter) &&
        (!custFilter || (row.CUST && row.CUST.toLowerCase().includes(custFilter))) &&
        (!customerCityFilter || (row["CUSTOMER - CITY/CHANNEL"] && row["CUSTOMER - CITY/CHANNEL"].toLowerCase().includes(customerCityFilter))) &&
        (!channelCustFilter || (row["CHANNEL CUST"] && row["CHANNEL CUST"].toLowerCase().includes(channelCustFilter))) &&
        (!pulauFilter || (row.PULAU && row.PULAU.toLowerCase().includes(pulauFilter))) &&
        (!regionSCMFilter || (row["REGION SCM"] && row["REGION SCM"].toLowerCase().includes(regionSCMFilter))) &&
        (!shipmentTypeFilter || (row["SHIPMENT TYPE"] && row["SHIPMENT TYPE"].toLowerCase().includes(shipmentTypeFilter))) &&
        shortCSLCheck
    });

    grid.updateConfig({
      data: filteredData.map(row => [
        null,
        row.MIXCUST,
        row.CUST,
        row["CUSTOMER - CITY/CHANNEL"],
        row["CHANNEL CUST"],
        row.PULAU,
        row["REGION SCM"],
        row["SHIPMENT TYPE"],
        row["SHIPMENT SIZE"],
        row.USED_VOLUME,
        row.FINAL_ALLOCATED_QTY,
        row["AVG_TRUCK_SIZE"],
        row["AVG_VOLUME_RATIO (%)"],
        row["AVG_CSL (%)"],
        mixcustStatusMap[row.MIXCUST] || "Kurang"
      ])
    }).forceRender();
  };

  const grid = new gridjs.Grid({
    columns: [
      {
        name: "Select",
        formatter: (_, row) => gridjs.html(
          `<input type="checkbox" class="cust-checkbox" data-mixcust="${row.cells[1].data}" value="${row.cells[2].data}" />`
        )
      },
      { name: "MIXCUST", id: "MIXCUST" },
      { name: "CUST#", id: "CUST" },
      "CUSTOMER - CITY/CHANNEL",
      "CHANNEL CUST",
      "PULAU",
      "REGION SCM",
      "SHIPMENT TYPE",
      "SHIPMENT SIZE",
      {
        name: "USED VOLUME (m3)",
        id: "USED_VOLUME",
        formatter: val => parseFloat(val).toFixed(2)
      },
      { name: "FINAL ALLOCATED QTY", id: "FINAL_ALLOCATED_QTY" },
      {
        name: "AVG TRUCK SIZE",
        id: "AVG_TRUCK_SIZE",
        formatter: val => parseFloat(val).toFixed(2)
      },
      {
        name: "AVG VOLUME RATIO (%)",
        id: "AVG_VOLUME_RATIO (%)",
        formatter: val => `${parseFloat(val).toFixed(2)}%`
      },
      {
        name: "AVG CSL (%)",
        id: "AVG_CSL (%)",
        formatter: val => `${parseFloat(val).toFixed(2)}%`
      },
      { name: "STATUS", id: "status" }
    ],
    sort: true,
    pagination: { enabled: true, limit: 15 },
    search: true,
    data: []
  }).render(document.getElementById("pickGrid"));

  const style = document.createElement("style");
  style.textContent = `
    .gridjs-table th,
    .gridjs-table td {
      white-space: nowrap;
      width: auto !important;
      max-width: none !important;
    }
  `;
  document.head.appendChild(style);

  fetch("assets/backend/pickallocation.php")
    .then(res => res.json())
    .then(json => {
      originalData = json;
      calculateStatusByMixcust(originalData);
      loadFiltersFromStorage(); // load filter dari localStorage sebelum render
      filterAndRender();
      populateFilterOptions(originalData);
    })
  
.catch(err => console.error("Error loading data:", err));

const populateFilterOptions = (data) => {
const getUniqueOptions = key => [...new Set(data.map(row => row[key]).filter(Boolean))];

pgsql
Copy
Edit
const filters = {
  filterCust: "CUST",
  filterCustomerCity: "CUSTOMER - CITY/CHANNEL",
  filterChannelCust: "CHANNEL CUST",
  filterPulau: "PULAU",
  filterRegionSCM: "REGION SCM",
  filterShipmentType: "SHIPMENT TYPE"
};

Object.entries(filters).forEach(([filterId, dataKey]) => {
  const select = document.getElementById(filterId);
  if (select && select.options.length <= 1) {
    getUniqueOptions(dataKey).forEach(option => {
      const opt = document.createElement("option");
      opt.value = option;
      opt.textContent = option;
      select.appendChild(opt);
    });
  }
});
};

const filterElements = [
"filterStatus", "filterCust", "filterCustomerCity", "filterChannelCust",
"filterPulau", "filterRegionSCM", "filterShipmentType", "filterShortCSL"
];

document.getElementById("btnPilih")?.addEventListener("click", async () => {
    const selected = Array.from(document.querySelectorAll(".cust-checkbox:checked"))
      .map(cb => ({ cust: cb.value, mixcust: cb.dataset.mixcust }));

    if (selected.length === 0) {
      alert("Pilih setidaknya satu customer.");
      return;
    }

    try {
      const res = await fetch("assets/backend/save_selected_mixcusts.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ selectedCusts: selected })
      });

      const result = await res.json();
      if (result.status === "success") {
        alert("Area berhasil dipilih.");
        location.reload();
      } else {
        alert("Gagal menyimpan pilihan.");
      }
    } catch (err) {
      alert("Terjadi kesalahan saat menyimpan pilihan.");
      console.error(err);
    }
  });

  document.getElementById("btnResetPilihan")?.addEventListener("click", async () => {
    try {
      const res = await fetch("assets/backend/reset_selected_cust.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action: "reset" })
      });

      const result = await res.json();
      if (result.status === "success") {
        alert("Pilihan berhasil direset.");
        location.reload();
      } else {
        alert("Gagal mereset pilihan.");
      }
    } catch (err) {
      console.error("Kesalahan saat reset pilihan:", err);
      alert("Terjadi kesalahan saat mereset pilihan.");
    }
  });
filterElements.forEach(id => {
const el = document.getElementById(id);
if (el) el.addEventListener("change", filterAndRender);
});

document.getElementById("resetFilterBtn")?.addEventListener("click", () => {
filterElements.forEach(id => {
const el = document.getElementById(id);
if (el) el.value = "";
});
clearFiltersStorage(); // hapus dari localStorage juga
filterAndRender();
});

document.getElementById("btnExportSelected")?.addEventListener("click", () => {
const selectedMixCusts = [...document.querySelectorAll(".cust-checkbox:checked")].map(cb => cb.dataset.mixcust);
if (selectedMixCusts.length === 0) return alert("No MIXCUST selected.");
fetch("assets/backend/pick/save_selected_mixcust.php", {
method: "POST",
headers: { "Content-Type": "application/json" },
body: JSON.stringify({ selected_mix_custs: selectedMixCusts })
})
.then(res => res.json())
.then(response => {
alert(response.message || "Selected MIXCUSTs saved.");
})
.catch(err => console.error("Error saving selected MIXCUSTs:", err));
});
});

const zoomRange = document.getElementById("zoomRange");
  const zoomValue = document.getElementById("zoomValue");

  if (pickGrid && zoomRange && zoomValue) {
    const baseFontSize = 14;
    const applyZoom = (percent) => {
      const newFontSize = (baseFontSize * percent) / 100;
      pickGrid.querySelectorAll("td, th").forEach(cell => {
        cell.style.fontSize = `${newFontSize}px`;
      });
      zoomValue.textContent = `${percent}%`;
    };

    zoomRange.addEventListener("input", (e) => {
      applyZoom(e.target.value);
    });

    // Inisialisasi zoom di awal
    applyZoom(100);
  }

