document.addEventListener('DOMContentLoaded', function () {
    const cmoUploadForm = document.getElementById('cmoUploadForm');
    const cmoGridContainer = document.getElementById('cmoGrid');
    const resetCMODataBtn = document.getElementById('resetCMOData');

    if (!cmoUploadForm || !cmoGridContainer || !resetCMODataBtn) {
        console.error("ERROR: Salah satu elemen HTML kunci (cmoUploadForm, cmoGrid, resetCMOData) tidak ditemukan. Pastikan ID di HTML Anda benar.");
        return;
    }

    let cmoGrid = null;
    // Mengubah activeFilters menjadi objek sederhana { columnId: searchValue }
    let activeFilters = {};

    // Kolom-kolom yang akan memiliki filter search box
    // Sesuaikan ini dengan nama KOLOM EXACT dari data CMO Anda
    const filterableColumns = [
        'MIXCUST',
        'CUST#',
        'CUSTOMER - CITY/CHANNEL',
        'REGION SCM',
        'CHANNEL CUST',
        'SKU',
        'MATERIAL DESCRIPTION',
        'CHANNEL',
        'OTD CMO'
    ];

    // --- Loading Overlay Functions ---
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
        console.log("Loading overlay shown:", message);
    };

    const hideLoadingOverlay = () => {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
            console.log("Loading overlay hidden.");
        }
    };
    // --- End Loading Overlay Functions ---

    // Fungsi untuk mendapatkan parameter filter dari activeFilters
    const getFilterParams = () => {
        const params = new URLSearchParams();
        for (const columnId in activeFilters) {
            const filterValue = activeFilters[columnId];
            // Filter hanya jika nilai filter tidak kosong
            if (filterValue && filterValue.trim() !== '') {
                params.append(columnId, filterValue.trim());
            }
        }
        console.log("Filter parameters generated:", params.toString());
        return params.toString();
    };

    // Fungsi untuk mengambil data CMO dari backend DENGAN filter
    const fetchCMODataAndRenderGrid = async () => {
    showLoadingOverlay('Memuat data...'); // Menampilkan loading sebelum fetch
    const filterParams = getFilterParams();
    try {
        const response = await fetch(`assets/backend/get_cmo_data.php?${filterParams}`);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}. Response: ${errorText.substring(0, 200)}...`);
        }

        const data = await response.json();
        const gridData = Array.isArray(data.data) ? data.data : [];
        console.log("Data CMO fetched successfully:", gridData.length, "rows.");

        showLoadingOverlay('Memuat tampilan tabel...'); // 👈 Tambahkan ini sebelum render grid
        await new Promise(resolve => setTimeout(resolve, 50)); // Delay kecil untuk pastikan overlay muncul

        initGrid(gridData); // Render grid
        return gridData;
    } catch (error) {
        console.error('ERROR: Gagal mengambil data CMO:', error);
        alert('Terjadi kesalahan saat memuat data. Silakan coba lagi.\n\n' + error.message);
        return [];
    } finally {
        hideLoadingOverlay(); // Sembunyikan loading setelah grid selesai dirender
    }
};

    const initGrid = (data) => {
        console.log("Initializing Grid.js with data length:", data.length);

        if (cmoGrid) {
            console.log("Destroying existing Grid.js instance.");
            cmoGrid.destroy();
            cmoGridContainer.innerHTML = ''; // Pastikan kontainer dibersihkan
        }

        // ***** PENTING: SESUAIKAN NAMA KOLOM DENGAN DATA CMO ANDA *****
        // Gunakan nama kolom EXACT yang dikembalikan oleh get_cmo_data.php
        const columnsConfig = [
            { id: 'MIXCUST', name: 'MIXCUST', width: '150px' },
            { id: 'CUST#', name: 'CUST#', width: '100px' },
            { id: 'CUSTOMER - CITY/CHANNEL', name: 'CUSTOMER - CITY/CHANNEL', width: '250px' },
            { id: 'REGION SCM', name: 'REGION SCM', width: '120px' },
            { id: 'CHANNEL CUST', name: 'CHANNEL CUST', width: '120px' },
            { id: 'SKU', name: 'SKU', width: '100px' },
            { id: 'MATERIAL DESCRIPTION', name: 'MATERIAL DESCRIPTION', width: '200px' },
            { id: 'VOLUME', name: 'VOLUME', width: '100px' },
            { id: 'CHANNEL', name: 'CHANNEL', width: '100px' },
            { id: 'OTD CMO', name: 'OTD CMO', width: '100px' },
            { id: 'CMO FINAL', name: 'CMO FINAL', width: '120px' },
            { id: 'ACTUAL', name: 'ACTUAL', width: '100px' },
            { id: 'DO PLANNING', name: 'DO PLANNING', width: '120px' },
        ];

        const finalColumns = columnsConfig.map(col => {
            if (filterableColumns.includes(col.id)) {
                return {
                    ...col,
                    // Fungsi header sekarang akan membuat input search
                    header: (text) => {
                        const headerTextSpan = document.createElement('span');
                        headerTextSpan.textContent = text;

                        const searchInput = document.createElement('input');
                        searchInput.type = 'text';
                        searchInput.placeholder = `Cari ${text}...`;
                        searchInput.className = 'filter-input form-control-sm'; // Tambahkan kelas Bootstrap
                        // Set nilai input dari filter yang aktif saat ini
                        searchInput.value = activeFilters[col.id] || '';

                        // Event listener untuk saat input berubah
                        searchInput.addEventListener('input', (e) => {
                            // Update filter aktif
                            if (e.target.value.trim() !== '') {
                                activeFilters[col.id] = e.target.value.trim();
                            } else {
                                delete activeFilters[col.id]; // Hapus jika kosong
                            }
                            console.log(`Filter for ${col.id} updated to: "${activeFilters[col.id] || ''}"`);
                            // Muat ulang data setelah penundaan singkat untuk menghindari terlalu banyak request
                            clearTimeout(searchInput.dataset.timeoutId);
                            searchInput.dataset.timeoutId = setTimeout(() => {
                                fetchCMODataAndRenderGrid();
                            }, 500); // Tunggu 500ms setelah user berhenti mengetik
                        });

                        const container = document.createElement('div');
                        container.style.display = 'flex';
                        container.style.flexDirection = 'column';
                        container.style.alignItems = 'flex-start'; // Align left
                        container.style.width = '100%';
                        container.style.boxSizing = 'border-box'; // Penting agar padding tidak membuat lebar melebihi TH
                        container.appendChild(headerTextSpan);
                        container.appendChild(searchInput);
                        return container;
                    }
                };
            }
            return col;
        });

        cmoGrid = new gridjs.Grid({
            columns: finalColumns, // Gunakan finalColumns yang sudah dimodifikasi
            data: data,
            // Jika ada kolom unik yang selalu ada dan tidak kosong, Anda bisa pakai id: (row) => row['UNIQUE_COLUMN']
            // Karena data sudah difilter di backend dan tidak ada edit di frontend, 'id' tidak terlalu krusial di Grid.js
            // kecuali untuk internal Grid.js jika dibutuhkan
            // id: (row) => row['MIXCUST'] + '-' + row['SKU'], // Contoh jika MIXCUST dan SKU kombinasi unik
            pagination: {
                enabled: true,
                limit: 10,
                summary: true
            },
            sort: true,
            resizable: true,
            style: {
                table: {
                    width: '100%'
                },
                th: {
                    'min-width': '100px', // Sesuaikan lebar minimum kolom
                    'position': 'relative',
                    'white-space': 'nowrap'
                }
            },
            // Perlu ditambahkan opsi wrapper untuk filter agar bisa mengakses TH dengan benar
            // Ini biasanya dilakukan dengan renderGrid atau kustomisasi header manual
            // Karena kita menggunakan header: (text) => { ... } , ini sudah teratasi.
            onUpdated: () => {
                console.log("Grid.js updated/rendered.");
            }
        });

        cmoGrid.render(cmoGridContainer);
        console.log("Grid.js rendered into cmoGridContainer.");
    };

    // Event listener untuk form upload file CMO
    cmoUploadForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const fileInput = document.getElementById('cmo_file');

        if (fileInput.files.length === 0) {
            alert('Silakan pilih file untuk diunggah.');
            return;
        }

        showLoadingOverlay('Mengunggah file...');
        try {
            // **PENTING: Pastikan URL ini benar dan file PHP-nya ada di sana**
            const response = await fetch('assets/backend/upload_cmo.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.status === 'success') {
                alert(data.message);
                // location.reload();
                await fetch('assets/backend/refresh_cmo_session.php');
                fileInput.value = '';
                // Setelah upload, reset filter dan muat ulang grid
                activeFilters = {};
                fetchCMODataAndRenderGrid();
            } else {
                alert('Upload gagal: ' + data.message);
            }
        } catch (error) {
            console.error('ERROR: Terjadi kesalahan saat mengunggah file:', error);
            alert('Terjadi kesalahan saat mengunggah file. Silakan coba lagi.');
        } finally {
            hideLoadingOverlay();
        }
    });

    // Event listener untuk tombol reset data sementara CMO
    resetCMODataBtn.addEventListener('click', async function () {
        if (confirm('Ini akan menghapus semua data CMO yang sedang ditampilkan secara sementara. Lanjutkan?')) {
            showLoadingOverlay('Mereset data...');
            try {
                // **PENTING: Pastikan URL ini benar dan file PHP-nya ada di sana**
                const response = await fetch('assets/backend/clear_cmo_session.php', {
                    method: 'POST'
                });
                const data = await response.json();

                if (data.status === 'success') {
                    alert(data.message);
                    activeFilters = {}; // Reset filter aktif saat data direset
                    fetchCMODataAndRenderGrid(); // Muat ulang data setelah reset
                } else {
                    alert('Gagal mereset data: ' + data.message);
                }
            } catch (error) {
                console.error('ERROR: Terjadi kesalahan saat mereset data:', error);
                alert('Terjadi kesalahan saat mereset data. Silakan coba lagi.');
            } finally {
                hideLoadingOverlay();
            }
        }
    });

    // Initial load and render
    fetchCMODataAndRenderGrid();
});