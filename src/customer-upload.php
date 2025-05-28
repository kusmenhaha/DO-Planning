<?php include 'partials/main.php'; ?>

<head>
    <?php
    $subTitle = "Form Upload Customer";
    include 'partials/title-meta.php'; ?>

    <?php include 'partials/head-css.php' ?>

    <link rel="stylesheet" href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" />
    <link rel="stylesheet" href="assets/css/cmo.css">
</head>

<body>
<div class="wrapper">
    <?php include 'partials/topbar.php'; ?>
    <?php include 'partials/main-nav.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <div class="row"><div class="col-12"><h4 class="mb-sm-0">Upload Data Customer</h4></div></div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header"><h4 class="card-title mb-0">Unggah Data Customer</h4></div>
                        <div class="card-body">
                            <form id="customerUploadForm" enctype="multipart/form-data" method="POST" action="assets/backend/upload_customer.php">
                                <div class="mb-3">
                                    <label for="customer_file" class="form-label">Pilih File Customer (CSV, XLSX, XLS)</label>
                                    <input class="form-control" type="file" id="customer_file" name="customer_file" accept=".csv, .xlsx, .xls" required>
                                    <small class="form-text text-muted">Unggah file CSV, XLSX, atau XLS.</small>
                                </div>
                                <button type="submit" class="btn btn-primary">Unggah</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tempat preview data jika ingin ditambahkan -->
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
</body>
</html>
