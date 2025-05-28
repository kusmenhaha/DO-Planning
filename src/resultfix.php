<?php
include 'partials/main.php';
// session_start();
?>

<head>
    <?php
    $subTitle = "Hasil Alokasi Pengiriman";
    include 'partials/title-meta.php';
    include 'partials/head-css.php';
    ?>
    <link rel="stylesheet" href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" />
    <link rel="stylesheet" href="assets/css/resultfix.css">
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php include 'partials/topbar.php'; ?>
        <?php include 'partials/main-nav.php'; ?>

        <div class="page-content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Hasil Alokasi Pengiriman</h4>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Data Alokasi</h4>
                            </div>
                            <div class="card-body">
                                <div id="allocationGrid"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include "partials/footer.php" ?>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
        <div class="loading-message">Loading...</div>
    </div>

    <?php include 'partials/vendor-scripts.php' ?>
    <script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script src="assets/js/resultfix.js"></script>
</body>
</html>
