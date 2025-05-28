document.addEventListener("DOMContentLoaded", () => {
    let originalData = [];
    let mixcustStatusMap = {};

    // --- DOM Element creation and event listeners (unchanged for this request) ---
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

        // Append buttons correctly: only append once
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

    // --- Helper function to calculate status by MIXCUST (REVISI HANYA PADA BAGIAN INI) ---
    const calculateStatusByMixcust = (data) => {
        const mixcustAggregates = {}; // Akan menyimpan total USED_VOLUME dan SHIPMENT_SIZE referensi per MIXCUST

        data.forEach(row => {
            const mixcust = row.MIXCUST;
            const usedVolume = parseFloat(row.USED_VOLUME) || 0;
            const shipmentSize = parseFloat(row["SHIPMENT SIZE"]) || 0; 

            if (!mixcustAggregates[mixcust]) {
                mixcustAggregates[mixcust] = {
                    totalUsedVolume: 0,
                    // Ambil SHIPMENT SIZE dari baris pertama sebagai referensi kapasitas satu truk
                    referenceShipmentSize: shipmentSize 
                };
            }
            mixcustAggregates[mixcust].totalUsedVolume += usedVolume;
            // shipmentSize tidak dijumlahkan karena mewakili kapasitas satu truk, bukan akumulasi
        });

        mixcustStatusMap = {};
        for (const mixcust in mixcustAggregates) {
            const { totalUsedVolume, referenceShipmentSize } = mixcustAggregates[mixcust];
            let aggregatedRatio = 0;

            // Hitung rasio berdasarkan total USED_VOLUME dibagi referenceShipmentSize
            if (referenceShipmentSize > 0) {
                aggregatedRatio = (totalUsedVolume / referenceShipmentSize); 
            }
            
            // Pembulatan aggregatedRatio ke 2 desimal, lalu bandingkan dengan 0.94 (94%)
            // Jika 1.000133... dibulatkan ke 2 desimal, akan menjadi 1.00
            const finalRatioForComparison = Math.round(aggregatedRatio * 100) / 100;

            mixcustStatusMap[mixcust] = finalRatioForComparison >= 0.94 ? "Full" : "Kurang";
            // console.log(`MIXCUST: ${mixcust}, Total Used: ${totalUsedVolume}, Ref Size: ${referenceShipmentSize}, Raw Ratio: ${aggregatedRatio.toFixed(4)}, Rounded Ratio: ${finalRatioForComparison}, Status: ${mixcustStatusMap[mixcust]}`); // Baris ini bisa di-uncomment untuk debugging
        }
    };

    // --- Filter data based on dropdowns and render grid (unchanged) ---
    const filterAndRender = () => {
        // Ambil nilai filter dari elemen
        const statusFilter = (document.getElementById("filterStatus")?.value || "").toLowerCase();
        const custFilter = (document.getElementById("filterCust")?.value || "").toLowerCase();
        const customerCityFilter = (document.getElementById("filterCustomerCity")?.value || "").toLowerCase();
        const channelCustFilter = (document.getElementById("filterChannelCust")?.value || "").toLowerCase();
        const pulauFilter = (document.getElementById("filterPulau")?.value || "").toLowerCase();
        const regionSCMFilter = (document.getElementById("filterRegionSCM")?.value || "").toLowerCase();
        const shipmentTypeFilter = (document.getElementById("filterShipmentType")?.value || "").toLowerCase();
        const shortTruckFilter = (document.getElementById("filterShortTruck")?.value || "").toLowerCase();
        const shortCSLFilter = (document.getElementById("filterShortCSL")?.value || "").toLowerCase();

        let filteredData = originalData.filter(row => {
            const status = (mixcustStatusMap[row.MIXCUST] || "Kurang").toLowerCase();

            const avgTruckSize = parseFloat(row["AVG_TRUCK_SIZE"]) || 0;
            const avgCSL = parseFloat(row["AVG_CSL (%)"]) || 0;

            let shortTruckPass = true;
            if (shortTruckFilter === "short") {
                shortTruckPass = avgTruckSize < 1.0;
            }

            // The CSL filter now acts as a display filter, not a hard "short" filter.
            // Sorting will be applied after filtering.
            let cslFilterPass = true; 
            // Removed specific CSL filtering here as sorting will handle the "short" logic visually.

            return (!statusFilter || status === statusFilter) &&
                (!custFilter || (row.CUST && row.CUST.toLowerCase().includes(custFilter))) &&
                (!customerCityFilter || (row["CUSTOMER - CITY/CHANNEL"] && row["CUSTOMER - CITY/CHANNEL"].toLowerCase().includes(customerCityFilter))) &&
                (!channelCustFilter || (row["CHANNEL CUST"] && row["CHANNEL CUST"].toLowerCase().includes(channelCustFilter))) &&
                (!pulauFilter || (row.PULAU && row.PULAU.toLowerCase().includes(pulauFilter))) &&
                (!regionSCMFilter || (row["REGION SCM"] && row["REGION SCM"].toLowerCase().includes(regionSCMFilter))) &&
                (!shipmentTypeFilter || (row["SHIPMENT TYPE"] && row["SHIPMENT TYPE"].toLowerCase().includes(shipmentTypeFilter))) &&
                shortTruckPass &&
                cslFilterPass; // This remains true, actual sorting happens next
        });

        // Apply CSL sorting if the filter is set
        if (shortCSLFilter === "short") {
            // Sort in ascending order for "short" (lowest CSL first)
            filteredData.sort((a, b) => {
                const cslA = parseFloat(a["AVG_CSL (%)"]) || 0;
                const cslB = parseFloat(b["AVG_CSL (%)"]) || 0;
                return cslA - cslB;
            });
        } else if (shortCSLFilter === "full") {
            // Sort in descending order for "full" (highest CSL first)
            filteredData.sort((a, b) => {
                const cslA = parseFloat(a["AVG_CSL (%)"]) || 0;
                const cslB = parseFloat(b["AVG_CSL (%)"]) || 0;
                return cslB - cslA;
            });
        }


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
                mixcustStatusMap[row.MIXCUST] || "Kurang",
            ])
        }).forceRender();
    };

    // --- Inisialisasi Grid.js (unchanged) ---
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
            { name: "STATUS", id: "status" },
        ],
        sort: true,
        pagination: { enabled: true, limit: 15 },
        search: true, // Grid.js built-in search. This is separate from your custom filters.
        data: []
    }).render(document.getElementById("pickGrid"));

    // Style tambahan agar kolom tidak terpotong (unchanged)
    const style = document.createElement("style");
    style.textContent =
        `.gridjs-table th,
        .gridjs-table td {
            white-space: nowrap;
            width: auto !important;
            max-width: none !important;
        }`;
    document.head.appendChild(style);

    // --- Fetch data and initialize grid ---
    fetch("assets/backend/pickallocation.php")
        .then(res => res.json())
        .then(json => {
            originalData = json;
            calculateStatusByMixcust(originalData);
            populateFilterOptions(originalData); // Populate dropdowns first
            loadFilters(); // Load saved filters from localStorage
            filterAndRender(); // Render data with applied filters
        })
        .catch(err => {
            console.error("Gagal mengambil data:", err);
        });

    // --- Helper function to populate filter dropdowns (unchanged) ---
    function populateFilterOptions(data) {
        const uniqueValues = (key) => [...new Set(data.map(row => row[key]).filter(Boolean))].sort();

        const fillSelect = (id, values) => {
            const select = document.getElementById(id);
            if (!select) return;
            select.innerHTML = '<option value="">-- Filter --</option>';
            values.forEach(val => {
                const option = document.createElement("option");
                option.value = val;
                option.textContent = val;
                select.appendChild(option);
            });
        };

        fillSelect("filterCust", uniqueValues("CUST#")); // Corrected key to "CUST#"
        fillSelect("filterCustomerCity", uniqueValues("CUSTOMER - CITY/CHANNEL"));
        fillSelect("filterChannelCust", uniqueValues("CHANNEL CUST"));
        fillSelect("filterPulau", uniqueValues("PULAU"));
        fillSelect("filterRegionSCM", uniqueValues("REGION SCM"));
        fillSelect("filterShipmentType", uniqueValues("SHIPMENT TYPE"));

        // Manually add options for shortCSLFilter
        const shortCSLSelect = document.getElementById("filterShortCSL");
        if (shortCSLSelect) {
            shortCSLSelect.innerHTML = `
                <option value="">-- Filter --</option>
                <option value="short">Terencdah</option>
                <option value="full">Tertinggi</option>
            `;
        }
    }

    // --- NEW: Save filters to localStorage (unchanged) ---
    const saveFilters = () => {
        const filterIds = [
            "filterStatus", "filterCust", "filterCustomerCity", "filterChannelCust",
            "filterPulau", "filterRegionSCM", "filterShipmentType",
            "filterShortTruck", "filterShortCSL"
        ];
        filterIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                localStorage.setItem(id, el.value);
            }
        });
    };

    // --- NEW: Load filters from localStorage (unchanged) ---
    const loadFilters = () => {
        const filterIds = [
            "filterStatus", "filterCust", "filterCustomerCity", "filterChannelCust",
            "filterPulau", "filterRegionSCM", "filterShipmentType",
            "filterShortTruck", "filterShortCSL"
        ];
        filterIds.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                const savedValue = localStorage.getItem(id);
                if (savedValue !== null) {
                    el.value = savedValue;
                }
            }
        });
    };


    // --- Attach event listeners to all filter dropdowns (unchanged) ---
    const allFilterIds = [
        "filterStatus", "filterCust", "filterCustomerCity", "filterChannelCust",
        "filterPulau", "filterRegionSCM", "filterShipmentType",
        "filterShortTruck", "filterShortCSL" 
    ];

    allFilterIds.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener("change", () => {
                saveFilters();      
                filterAndRender();  
            });
        }
    });

    // --- Reset filters button (unchanged) ---
    document.getElementById("resetFilterBtn")?.addEventListener("click", () => {
        const filterIdsToReset = [
            "filterStatus", "filterCust", "filterCustomerCity", "filterChannelCust",
            "filterPulau", "filterRegionSCM", "filterShipmentType",
            "filterShortTruck", "filterShortCSL"
        ];
        filterIdsToReset.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.value = "";
                localStorage.removeItem(id); 
            }
        });
        filterAndRender();
    });

    // --- Button event listeners for selection and backend interaction (unchanged, but cleanup 'fetchAndRenderFromBackend' call) ---
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
                try {
                    const refreshRes = await fetch("assets/backend/refresh_all_backend.php");
                    const refreshMsg = await refreshRes.text();
                    console.log("Refreshed backend:", refreshMsg);
                } catch (refreshErr) {
                    console.error("Gagal refresh backend:", refreshErr);
                }
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
                try {
                    const refreshRes = await fetch("assets/backend/refresh_all_backend.php");
                    const refreshMsg = await refreshRes.text();
                    console.log("Refreshed backend:", refreshMsg);
                } catch (refreshErr) {
                    console.error("Gagal refresh backend:", refreshErr);
                }
                
                location.reload();
            } else {
                alert("Gagal mereset pilihan.");
            }
        } catch (err) {
            console.error("Kesalahan saat reset pilihan:", err);
            alert("Terjadi kesalahan saat mereset pilihan.");
        }
    });

    document.getElementById("btnExportSelected")?.addEventListener("click", () => {
        const selectedRows = Array.from(document.querySelectorAll(".cust-checkbox:checked"))
            .map(cb => cb.value);
        if (selectedRows.length === 0) {
            alert("Pilih setidaknya satu baris untuk export.");
            return;
        }
        alert("Fungsi Export belum diimplementasikan.");
    });

    // --- Zoom functionality (unchanged) ---
    

    const zoomRange = document.getElementById("zoomRange");
    const zoomValue = document.getElementById("zoomValue");

    if (pickGrid && zoomRange && zoomValue) {
        const baseFontSize = 14; 

        const applyZoom = (percent) => {
            const newFontSize = (baseFontSize * percent) / 100;
            pickGrid.querySelectorAll("td, th").forEach(cell => {
                cell.style.fontSize = `${newFontSize}px`;
                cell.style.padding = "5px 8px"; 
            });
            zoomValue.textContent = `${percent}%`;
            zoomRange.value = percent;
        };

        const savedZoom = localStorage.getItem("pickGridZoom");
        if (savedZoom) {
            applyZoom(parseInt(savedZoom));
        } else {
            applyZoom(parseInt(zoomRange.value));
        }

        zoomRange.addEventListener("input", () => {
            const val = parseInt(zoomRange.value);
            applyZoom(val);
            localStorage.setItem("pickGridZoom", val);
        });
    }
});