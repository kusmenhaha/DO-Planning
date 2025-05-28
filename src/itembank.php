<?php include 'partials/main.php'; ?>

<head>
    <?php
    // $subTitle = "Gridjs Table";
    include 'partials/title-meta.php'; 
    ?> 

    <!-- Gridjs Plugin css dari CDN -->
    <link href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
    <?php include 'partials/head-css.php'; ?>

    <style>
        /* Pastikan tombol ada di atas tabel dan bisa diklik */
        #delete-form {
            position: relative;   /* biar z-index berfungsi */
            z-index: 10;
        }

        /* Jika ada masalah tabel menimpa tombol, beri padding bawah card-body agar tombol tidak tertutup */
        .card-body {
            padding-bottom: 80px; /* beri ruang cukup untuk tombol */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php 
            $subTitle = "ITEM BANK";
            include 'partials/topbar.php';
            include 'partials/main-nav.php'; 
        ?>

        <div class="page-content">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3" id="search">ITEM BANK</h5>
                                <div class="table-responsive py-2">
                                    <div id="table-search"></div>
                                </div>

                                <!-- Tombol Hapus Semua Data -->
                                <form method="POST" action="item-bank-delete.php" id="delete-form">
                                    <button class="btn btn-danger mt-3" type="submit" name="delete_all" id="delete-all-data">Refresh Data</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/footer.php'; ?>
    </div>

    <?php include 'partials/vendor-scripts.php'; ?>

    <!-- Hanya satu kali load gridjs dari CDN -->
    <script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>

    <script>
        new gridjs.Grid({
            columns: [
                "SKU", "Material Description", "Sales Org", "Channel", "Volume", 
                "Matl Group", "Plant", "Parent", "Size x Dimension", "Country", 
                "Gross Weight", "BUN", "Division", "Family Product"
            ],
            search: true,
            pagination: { enabled: true, limit: 5 },
            sort: true,
            server: {
                url: 'get-itembank.php',
                then: data => data  // pastikan data adalah array, bukan objek
            },
            style: {
                th: { 'background-color': '#f8f9fa', 'text-align': 'center' },
                td: { 'text-align': 'center' }
            }
        }).render(document.getElementById("table-search"));

        document.getElementById('delete-all-data').addEventListener('click', function(event) {
            if (!confirm('Apakah Anda yakin ingin menghapus semua data?')) {
                event.preventDefault(); // Mencegah form submit jika batal konfirmasi
            }
        });
    </script>
</body>
</html>
