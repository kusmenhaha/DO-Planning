<?php include 'partials/main.php'; ?>

<head>
    <?php
    $subTitle = "Form Upload Stock";
    include 'partials/title-meta.php'; ?>

    <?php include 'partials/head-css.php' ?>

    <link rel="stylesheet" href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" />
    <link rel="stylesheet" href="assets/css/stock.css">
</head>

<body>
    <div class="wrapper">

        <?php
        $subTitle = "Form Upload Stock";
        include 'partials/topbar.php'; ?>
        <?php include 'partials/main-nav.php'; ?>

        <div class="page-content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Upload Data Stock</h4>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Unggah Data Stock</h4>
                            </div>
                            <div class="card-body">
                                <form id="stockUploadForm" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="stock_file" class="form-label">Pilih File Stock (CSV, XLSX, XLS)</label>
                                        <input class="form-control" type="file" id="stock_file" name="stock_file" accept=".csv, .xlsx, .xls">
                                        <small class="form-text text-muted">Unggah file CSV, XLSX, atau XLS.</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Unggah</button>
                                    <button type="button" class="btn btn-warning" id="resetStockData">Reset Data Sementara</button>
                                    <button type="button" class="btn btn-success" id="saveStockChanges" style="display: none;">Simpan Perubahan QTY</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FILTERS -->
                <div class="row mt-2">
                    <div class="col-md-3">
                        <label for="channelFilter" class="form-label">Filter Channel</label>
                        <select id="channelFilter" class="form-select">
                            <option value="">Semua Channel</option>
                            <!-- Opsi akan diisi dinamis -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sisaQtyFilter" class="form-label">Filter Sisa Qty</label>
                        <select id="sisaQtyFilter" class="form-select">
                            <option value="">Semua</option>
                            <option value=">0">Sisa > 0</option>
                            <option value="=0">Sisa = 0</option>
                            <option value="<0">Sisa < 0</option>
                        </select>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Pratinjau Data Stock</h4>
                            </div>
                            <div class="card-body">
                                <div id="stockGrid"></div>
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
    <script src="assets/js/stock.js"></script>
</body>

</html>
