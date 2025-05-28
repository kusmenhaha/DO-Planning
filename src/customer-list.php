<?php include 'partials/main.php'; ?>

<head>
    <?php
    $subTitle = "Gridjs Table";
    include 'partials/title-meta.php'; 
    ?> 

    <!-- Gridjs Plugin css dari CDN -->
    <link href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
    <?php include 'partials/head-css.php'; ?>

    <style>
        /* Tombol tetap terlihat */
        #delete-form {
            position: relative;
            z-index: 10;
        }

        /* Beri ruang bawah agar tombol tidak tertutup */
        .card-body {
            padding-bottom: 80px;
        }

        /* Scroll horizontal jika kolom melebihi lebar */
        .gridjs-wrapper {
            overflow-x: auto;
            white-space: nowrap;
        }

        /* Responsif tambahan untuk mobile */
        @media (max-width: 768px) {
            .gridjs-container {
                font-size: 12px;
            }

            .gridjs-wrapper {
                overflow-x: scroll;
                -webkit-overflow-scrolling: touch;
            }

            .btn {
                width: 100%;
            }

            .card-title {
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php 
            $subTitle = "Gridjs Table";
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
                                <form method="POST" action="customer-delete.php" id="delete-form">
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

    <!-- Grid.js dari CDN -->
    <script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>

    <script>
        new gridjs.Grid({
            columns: [
                "CUST", "Mix Cust", "Customer Name", "Pulau", "REGION SCM", "ABM", 
                "CHANNEL CUST", "SHIPMENT TYPE", "PORT KONTAINER", 
                "SHIPMENT SIZE MAJALENGKA", "SHIPMENT SIZE RANCAEKEK", "SHIPMENT SIZE RCK"
            ],
            search: true,
            pagination: { enabled: true, limit: 5 },
            sort: true,
            server: {
                url: 'get-mixcust.php',
                then: data => data
            },
            style: {
                th: { 'background-color': '#f8f9fa', 'text-align': 'center' },
                td: { 'text-align': 'center' }
            }
        }).render(document.getElementById("table-search"));

        // Konfirmasi sebelum hapus
        document.getElementById('delete-all-data').addEventListener('click', function(event) {
            if (!confirm('Apakah Anda yakin ingin menghapus semua data?')) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
