<?php include 'partials/main.php'; ?>

<head>
    <?php
    $subTitle = "Form Upload CMO";
    include 'partials/title-meta.php'; ?>

    <?php include 'partials/head-css.php' ?>

    <link rel="stylesheet" href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" />
    <link rel="stylesheet" href="assets/css/cmo.css">
</head>

<body>
    <div class="wrapper">

        <?php
        $subTitle = "Form Upload CMO";
        include 'partials/topbar.php'; ?>
        <?php include 'partials/main-nav.php'; ?>

        <div class="page-content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Upload Data CMO</h4>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Unggah Data CMO</h4>
                            </div>
                            <div class="card-body">
                                <form id="cmoUploadForm" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="cmo_file" class="form-label">Pilih File CMO (CSV, XLSX, XLS)</label>
                                        <input class="form-control" type="file" id="cmo_file" name="cmo_file" accept=".csv, .xlsx, .xls">
                                        <small class="form-text text-muted">Unggah file CSV, XLSX, atau XLS.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Unggah</button>
                                    <button type="button" class="btn btn-warning" id="resetCMOData">Reset Data Sementara</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Pratinjau Data CMO</h4>
                            </div>
                            <div class="card-body">
                                <div id="cmoGrid"></div>
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
    <script src="assets/js/cmo.js"></script>
</body>

</html>