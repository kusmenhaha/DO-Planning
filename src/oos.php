<?php
// session_start(); // Start the session at the very beginning

// Pastikan jalur ke partials sudah benar sesuai struktur proyek Anda
include 'partials/main.php';
?>

<head>
    <?php
    $subTitle = "Form Upload OOS"; // Changed subTitle to OOS
    include 'partials/title-meta.php'; ?>

    <?php include 'partials/head-css.php' ?>

    <link rel="stylesheet" href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" />
    <link rel="stylesheet" href="assets/css/oos.css"> </head>

<body>
    <div class="wrapper">

        <?php
        $subTitle = "Form Upload OOS"; // Changed subTitle to OOS
        include 'partials/topbar.php'; ?>
        <?php include 'partials/main-nav.php'; ?>

        <div class="page-content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Upload Data OOS</h4> </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Unggah Data OOS</h4> </div>
                            <div class="card-body">
                                <form id="oosUploadForm" enctype="multipart/form-data"> <div class="mb-3">
                                        <label for="oos_file" class="form-label">Pilih File OOS (XLSX, XLS)</label> <input class="form-control" type="file" id="oos_file" name="oos_file" accept=".xlsx, .xls"> <small class="form-text text-muted">Unggah file XLSX atau XLS.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Unggah</button>
                                    <button type="button" class="btn btn-warning" id="resetOOSData">Reset Data Sementara</button> </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Pratinjau Data OOS</h4> </div>
                            <div class="card-body">
                                <div id="oosGrid"></div> </div>
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
    <script src="assets/js/oos.js"></script> </body>

</html>