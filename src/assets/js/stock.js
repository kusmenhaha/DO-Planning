document.addEventListener('DOMContentLoaded', function () {
    const stockUploadForm = document.getElementById('stockUploadForm');
    const stockGridContainer = document.getElementById('stockGrid');
    const resetStockDataBtn = document.getElementById('resetStockData');
    const saveStockChangesBtn = document.getElementById('saveStockChanges');

    if (!stockUploadForm || !stockGridContainer || !resetStockDataBtn || !saveStockChangesBtn) {
        console.error("ERROR: Salah satu elemen HTML kunci tidak ditemukan. Pastikan ID di HTML Anda benar.");
        return;
    }

    let stockGrid = null;
    let currentGridData = [];
    let editedCells = {};
    let activeFilters = {}; // Objek untuk menyimpan filter yang aktif

    const showLoadingOverlay = (message = 'Memuat data...') => {
        let overlay = document.getElementById('loadingOverlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="spinner"></div>
                <div class="loading-message">${message}</div>
            `;
            document.body.appendChild(overlay);
        } else {
            overlay.querySelector('.loading-message').textContent = message;
        }
        overlay.style.display = 'flex';
    };

    const hideLoadingOverlay = () => {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) overlay.style.display = 'none';
    };

    // Fungsi untuk mendapatkan parameter filter dari objek activeFilters
    const getFilterParams = () => {
        const params = new URLSearchParams();
        for (const columnId in activeFilters) {
            const filterValue = activeFilters[columnId];
            // Hanya tambahkan parameter jika nilainya valid (tidak kosong atau "ALL")
            if (filterValue && filterValue.trim() !== '' && filterValue.trim().toUpperCase() !== 'ALL') {
                params.append(columnId, filterValue.trim());
            }
        }
        return params.toString();
    };

    // Fungsi untuk mengambil data stok dan merender grid
    const fetchStockDataAndRenderGrid = async () => {
        showLoadingOverlay('Memuat data...');
        editedCells = {}; // Reset editedCells setiap kali data baru dimuat
        saveStockChangesBtn.style.display = 'none';

        const filterParams = getFilterParams();
        const url = `assets/backend/get_stock_data.php?${filterParams}`;
        console.log("Fetching data from URL:", url); // Log URL untuk debugging

        try {
            const response = await fetch(url);
            if (!response.ok) {
                // Tangani respons HTTP error (misal 404, 500)
                const errorText = await response.text();
                throw new Error(`Gagal fetch data: ${response.status} ${response.statusText} - ${errorText}`);
            }
            const data = await response.json();
            // Pastikan data.data adalah array
            currentGridData = Array.isArray(data.data) ? data.data : [];
            initGrid(currentGridData);
            populateChannelDropdown(currentGridData); // Isi dropdown setelah data dimuat
            // Setelah data dimuat dan grid dirender, atur ulang nilai dropdown filter ke nilai aktif
            updateFilterDropdownsFromActiveFilters();

        } catch (error) {
            alert('Gagal memuat data: ' + error.message);
            console.error('Error fetching stock data:', error);
        } finally {
            hideLoadingOverlay();
        }
    };

    // Fungsi untuk mengisi dropdown channel
    const populateChannelDropdown = (data) => {
        const channelSet = new Set();
        data.forEach(item => {
            if (item['CHANNEL']) {
                channelSet.add(item['CHANNEL']);
            }
        });

        const channelFilter = document.getElementById('channelFilter');
        if (!channelFilter) return;

        channelFilter.innerHTML = '<option value="">Semua Channel</option>'; // Opsi default untuk "unfilter"
        [...channelSet].sort().forEach(ch => {
            const opt = document.createElement('option');
            opt.value = ch;
            opt.textContent = ch;
            channelFilter.appendChild(opt);
        });
        // Set nilai dropdown ke filter yang aktif setelah di-populate
        if (activeFilters['CHANNEL']) {
            channelFilter.value = activeFilters['CHANNEL'];
        }
    };

    // Fungsi untuk memperbarui nilai dropdown filter berdasarkan activeFilters
    const updateFilterDropdownsFromActiveFilters = () => {
        const channelFilter = document.getElementById('channelFilter');
        const sisaQtyFilter = document.getElementById('sisaQtyFilter');

        if (channelFilter && activeFilters['CHANNEL']) {
            channelFilter.value = activeFilters['CHANNEL'];
        } else if (channelFilter) {
            channelFilter.value = ""; // Reset ke "Semua Channel"
        }

        if (sisaQtyFilter && activeFilters['Sisa QTY']) {
            sisaQtyFilter.value = activeFilters['Sisa QTY'];
        } else if (sisaQtyFilter) {
            sisaQtyFilter.value = ""; // Reset ke default
        }
    };


    // --- Handle input QTY (Tidak Berubah) ---
    const handleQtyInputChange = (event) => {
        const input = event.target;
        const sku = input.dataset.sku;
        const originalValue = parseFloat(input.dataset.originalValue);
        const newValue = parseFloat(input.value);

        if (isNaN(newValue) || newValue < 0) {
            alert("QTY harus berupa angka positif atau nol.");
            input.value = originalValue;
            return;
        }

        if (newValue === originalValue) {
            if (editedCells[sku] && editedCells[sku]['QTY'] !== undefined) {
                delete editedCells[sku]['QTY'];
                if (Object.keys(editedCells[sku]).length === 0) {
                    delete editedCells[sku];
                }
            }
            if (Object.keys(editedCells).length === 0) {
                saveStockChangesBtn.style.display = 'none';
            }
            return;
        }

        if (!editedCells[sku]) {
            editedCells[sku] = {};
        }
        editedCells[sku]['QTY'] = Math.round(newValue); // Round the newValue;
        saveStockChangesBtn.style.display = 'inline-block';

        const rowIndex = currentGridData.findIndex(item => item.SKU === sku);
        if (rowIndex !== -1) {
            currentGridData[rowIndex]['QTY'] = Math.round(newValue);
            currentGridData[rowIndex]['Sisa QTY'] = newValue - (parseFloat(currentGridData[rowIndex]['Planning']) || 0);
            // Karena Sisa QTY berubah, kita perlu me-render ulang baris atau Grid untuk refleksi visual
            // Namun, Grid.js tidak menyediakan cara mudah untuk memperbarui sel individu
            // Cara termudah adalah dengan merender ulang seluruh Grid (ini mungkin berlebihan,
            // tetapi untuk perubahan tunggal, dampaknya kecil jika data tidak terlalu besar)
            // fetchStockDataAndRenderGrid(); // Ini akan menyebabkan refresh penuh
            // Atau jika hanya ingin memperbarui tampilan Sisa QTY tanpa fetch ulang:
            // Anda bisa memicu update Grid.js jika dibutuhkan, tapi untuk sekarang,
            // kita akan membiarkan perubahan terlihat saat grid di-refresh berikutnya
            // atau saat pengguna mengklik save.
        }
    };

    const handleQtyInputKeydown = (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            e.target.blur();
        } else if (e.key === 'Escape') {
            const input = e.target;
            const originalValue = parseFloat(input.dataset.originalValue);
            input.value = Math.round(originalValue); // Round the originalValue;
            input.blur();
        }
    };

    // --- Inisialisasi Grid.js (Tidak Berubah Signifikan) ---
    const initGrid = (data) => {
        if (stockGrid) {
            stockGrid.destroy();
            stockGridContainer.innerHTML = '';
        }

        stockGrid = new gridjs.Grid({
            columns: [
                "SKU",
                "Material Description",
                "CHANNEL",
                {
                    id: 'QTY',
                    name: 'QTY',
                    formatter: (cell, row) => {
                        const h = gridjs.h;
                        const sku = row.cells[0].data;
                        const qty = parseInt(row.cells[3].data);

                        return h('input', {
                            type: 'number',
                            className: 'qty-input',
                            value: qty,
                            min: 0,
                            'data-sku': sku,
                            'data-original-value': qty,
                            onblur: handleQtyInputChange,
                            onkeydown: handleQtyInputKeydown
                        });
                    }
                },
                "Planning",
                {
                    id: 'Sisa QTY',
                    name: 'Sisa QTY',
                    formatter: (cell) => {
                        const h = gridjs.h;
                        const value = parseInt(cell);
                        const className = value < 0 ? 'negative-qty' : '';
                        return h('span', { className }, value);
                    }
                }
            ],
            data: data.map(row => [
                row['SKU'],
                row['Material Description'],
                row['CHANNEL'],
                row['QTY'],
                row['Planning'],
                row['Sisa QTY']
            ]),
            pagination: { enabled: true, limit: 10, summary: true },
            sort: true,
            resizable: true,
            style: {
                table: { width: '100%' },
                th: { 'min-width': '80px', 'white-space': 'nowrap' }
            }
        });

        stockGrid.render(stockGridContainer);
    };

    // --- Event Listener untuk Filter Dropdown ---
    const channelFilterDropdown = document.getElementById('channelFilter');
    const sisaQtyFilterDropdown = document.getElementById('sisaQtyFilter');

    if (channelFilterDropdown) {
        channelFilterDropdown.addEventListener('change', function () {
            const val = this.value;
            if (val && val.trim() !== '' && val.trim().toUpperCase() !== 'ALL') {
                activeFilters['CHANNEL'] = val;
            } else {
                delete activeFilters['CHANNEL']; // Hapus filter jika "Semua Channel" dipilih
            }
            fetchStockDataAndRenderGrid(); // Panggil ulang untuk memuat data dengan filter baru
        });
    }

    if (sisaQtyFilterDropdown) {
        sisaQtyFilterDropdown.addEventListener('change', function () {
            const val = this.value;
            // Pastikan nilai valid sebelum menyimpan ke activeFilters
            if (val === '<0' || val === '>0' || val === '=0') {
                activeFilters['Sisa QTY'] = val;
            } else {
                delete activeFilters['Sisa QTY']; // Hapus filter jika opsi default dipilih
            }
            fetchStockDataAndRenderGrid(); // Panggil ulang untuk memuat data dengan filter baru
        });
    }

    // --- Event Listener untuk Upload, Reset, dan Save (Tidak Berubah Signifikan) ---
    stockUploadForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const fileInput = document.getElementById('stock_file');
        if (!fileInput.files.length) return alert('Pilih file terlebih dahulu.');

        showLoadingOverlay('Mengunggah file...');
        try {
            const res = await fetch('assets/backend/upload_stock.php', { method: 'POST', body: formData });
            const data = await res.json();
            alert(data.message);
            if (data.status === 'success') {
                activeFilters = {}; // Reset filter setelah upload baru
                fetchStockDataAndRenderGrid();
            }
        } catch (err) {
            alert('Gagal upload: ' + err.message);
        } finally {
            hideLoadingOverlay();
        }
    });

    resetStockDataBtn.addEventListener('click', async function () {
        if (!confirm('Yakin ingin mereset data? Ini akan menghapus semua data stok dari sesi.')) return; // Lebih jelas
        showLoadingOverlay('Mereset data...');
        try {
            const res = await fetch('assets/backend/clear_stock_session.php', { method: 'POST' });
            const data = await res.json();
            alert(data.message);
            if (data.status === 'success') {
                activeFilters = {}; // Penting: reset semua filter saat reset data
                fetchStockDataAndRenderGrid(); // Muat ulang data tanpa filter
            }
        } catch (err) {
            alert('Gagal reset: ' + err.message);
        } finally {
            hideLoadingOverlay();
        }
    });

    saveStockChangesBtn.addEventListener('click', async function () {
        if (Object.keys(editedCells).length === 0) {
            alert("Tidak ada perubahan QTY yang terdeteksi untuk disimpan.");
            return;
        }

        if (!confirm('Anda yakin ingin menyimpan semua perubahan QTY?')) {
            return;
        }

        showLoadingOverlay('Menyimpan perubahan...');
        try {
            const response = await fetch('assets/backend/update_stock_qty.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(editedCells)
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert(data.message);
                fetchStockDataAndRenderGrid(); // Refresh grid untuk melihat perubahan QTY dan Sisa QTY yang terupdate
            } else {
                alert('Gagal menyimpan perubahan: ' + data.message);
            }
        } catch (error) {
            alert('Terjadi kesalahan saat menyimpan perubahan. Silakan coba lagi. Detail: ' + error.message);
        } finally {
            hideLoadingOverlay();
        }
    });

    // Panggil saat halaman pertama kali dimuat
    fetchStockDataAndRenderGrid();
});