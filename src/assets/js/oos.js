document.addEventListener('DOMContentLoaded', function() {
    const oosUploadForm = document.getElementById('oosUploadForm');
    const oosGridContainer = document.getElementById('oosGrid');
    const resetOOSDataButton = document.getElementById('resetOOSData');
    const loadingOverlay = document.getElementById('loadingOverlay');

    let oosGrid = null; // To store the Grid.js instance

    // Function to show loading overlay
    function showLoading(message = 'Loading...') {
        if (loadingOverlay) {
            loadingOverlay.querySelector('.loading-message').textContent = message;
            loadingOverlay.style.display = 'flex';
        }
    }

    // Function to hide loading overlay
    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
    }

    // Function to render the Grid.js table
    function renderOOSGrid(data) {
        if (!oosGridContainer) {
            console.error("Error: Grid container element with ID 'oosGrid' not found in the DOM.");
            return;
        }

        // --- Penting: Hancurkan instance Grid.js yang ada jika sudah ada ---
        if (oosGrid) {
            try {
                oosGrid.destroy();
                console.log("Existing Grid.js instance destroyed.");
            } catch (e) {
                console.error("Error destroying previous Grid.js instance:", e);
                // Jika destroy gagal, kita mungkin perlu mengosongkan kontainer secara paksa
                oosGridContainer.innerHTML = '';
            }
            oosGrid = null; // Setel ke null setelah dihancurkan
        }

        // --- Penting: Kosongkan kontainer sebelum merender Grid.js yang baru ---
        oosGridContainer.innerHTML = '';

        // --- MODIFIKASI DI SINI: Tambahkan properti 'width' ke setiap kolom ---
        const columns = [
            { id: 'Urutan', name: 'Urutan', width: '80px' }, // Contoh lebar
            { id: 'Batch', name: 'Batch', width: '120px' },
            { id: 'PULAU', name: 'PULAU', width: '100px' },
            { id: 'Region SCM', name: 'Region SCM', width: '150px' },
            { id: 'CUST#', name: 'CUST#', width: '100px' },
            { id: 'DEPO', name: 'DEPO', width: '100px' },
            { id: 'SKU', name: 'SKU', width: '150px' },
            { id: 'PARENT SKU', name: 'PARENT SKU', width: '150px' },
            { id: 'ITEM', name: 'ITEM', width: '250px' }, // Mungkin butuh lebih lebar
            { id: 'Original OOS', name: 'Original OOS', width: '120px' },
            { id: 'STATUS DOI', name: 'STATUS DOI', width: '120px' },
            { id: 'Req QTY Kirim', name: 'Req QTY Kirim', width: '120px' },
        ];

        if (!data || data.length === 0) {
            oosGridContainer.innerHTML = '<div class="alert alert-info" role="alert">Tidak ada data OOS untuk ditampilkan.</div>';
            console.log("No data provided to renderOOSGrid.");
            return;
        }

        oosGrid = new gridjs.Grid({
            columns: columns,
            data: data,
            search: true,
            sort: true,
            pagination: {
                enabled: true,
                limit: 10,
                summary: true
            },
            style: {
                table: {
                    border: '3px solid #ccc',
                    // Anda bisa coba tambahkan ini untuk membiarkan tabel menyesuaikan
                    // tableLayout: 'auto' // Defaultnya auto, tapi bisa eksplisit
                },
                th: {
                    backgroundColor: 'rgba(0, 0, 0, 0.1)',
                    color: '#000',
                    border: '3px solid #ccc',
                    whiteSpace: 'nowrap' // Mencegah teks heading pecah baris
                },
                td: {
                    border: '1px solid #ccc',
                    whiteSpace: 'nowrap', // Mencegah teks data pecah baris
                    overflow: 'hidden',    // Sembunyikan jika overflow
                    textOverflow: 'ellipsis' // Tambahkan ellipsis jika terpotong
                }
            },
            language: {
                'search': {
                    'placeholder': 'Cari...'
                },
                'pagination': {
                    'previous': 'Sebelumnya',
                    'next': 'Selanjutnya',
                    'showing': 'Menampilkan',
                    'results': 'hasil',
                    'of': 'dari',
                    'to': 'sampai'
                },
                'loading': 'Memuat...',
                'noRecordsFound': 'Tidak ada data ditemukan.',
                'error': 'Terjadi kesalahan saat memuat data.'
            }
        }).render(oosGridContainer);
        console.log("Grid.js rendered successfully.");
    }

    // Function to fetch data from session on page load
    function fetchOOSData() {
        showLoading('Mengambil data...');
        fetch('assets/backend/get_oos_data.php') // Pastikan path ini benar
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP error! status: ${response.status}. Response: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                hideLoading();
                if (data.status === 'success' && data.data && data.data.length > 0) {
                    renderOOSGrid(data.data);
                } else {
                    oosGridContainer.innerHTML = '<div class="alert alert-info" role="alert">' + (data.message || 'Tidak ada data OOS untuk ditampilkan.') + '</div>';
                    console.log("No OOS data fetched from session or empty.");
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error fetching OOS data:', error);
                oosGridContainer.innerHTML = '<div class="alert alert-danger" role="alert">Gagal mengambil data: ' + error.message + '</div>';
            });
    }

    // Handle form submission
    if (oosUploadForm) {
        oosUploadForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const fileInput = document.getElementById('oos_file');
            if (!fileInput || fileInput.files.length === 0) {
                alert('Pilih file Excel terlebih dahulu.');
                return;
            }

            showLoading('Mengunggah dan memproses file...');

            const formData = new FormData();
            formData.append('oos_file', fileInput.files[0]);

            fetch('assets/backend/upload_oos.php', { // Pastikan path ini benar
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => {
                        throw new Error(`HTTP error! status: ${response.status}. Response: ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                hideLoading();
                if (data.status === 'success') {
                    alert(data.message);
                    renderOOSGrid(data.data);
                    fileInput.value = '';
                } else {
                    alert('Error: ' + data.message);
                    oosGridContainer.innerHTML = '<div class="alert alert-danger" role="alert">Gagal mengunggah: ' + data.message + '</div>';
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Upload Error:', error);
                alert('Terjadi kesalahan saat mengunggah file. Detail: ' + error.message);
                oosGridContainer.innerHTML = '<div class="alert alert-danger" role="alert">Gagal mengunggah: ' + error.message + '</div>';
            });
        });
    }

    // Handle reset button click
    if (resetOOSDataButton) {
        resetOOSDataButton.addEventListener('click', function() {
            if (confirm('Apakah Anda yakin ingin mereset data OOS sementara? Ini akan menghapus data dari pratinjau.')) {
                showLoading('Mereset data...');
                fetch('assets/backend/clear_oos_data.php', { // Pastikan path ini benar
                    method: 'POST',
                })
                .then(response => {
                    if (!response.ok) {
                        return response.text().then(text => {
                            throw new Error(`HTTP error! status: ${response.status}. Response: ${text}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    hideLoading();
                    if (data.status === 'success') {
                        alert(data.message);
                        if (oosGrid) {
                            try {
                                oosGrid.destroy();
                                oosGrid = null;
                            } catch (e) {
                                console.error("Error destroying grid during reset:", e);
                            }
                        }
                        oosGridContainer.innerHTML = '<div class="alert alert-info" role="alert">Data OOS telah direset. Unggah file baru untuk melihat pratinjau.</div>';
                        document.getElementById('oos_file').value = '';
                    } else {
                        alert('Error mereset data: ' + data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    console.error('Reset Error:', error);
                    alert('Terjadi kesalahan saat mereset data. Detail: ' + error.message);
                    oosGridContainer.innerHTML = '<div class="alert alert-danger" role="alert">Gagal mereset: ' + error.message + '</div>';
                });
            }
        });
    }

    // Initial load of data when the page loads
    fetchOOSData();
});