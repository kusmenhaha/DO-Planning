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
    <link rel="stylesheet" href="assets/css/allocation.css">
</head>

<body>
    <div class="wrapper">
        <?php include 'partials/topbar.php'; ?>
        <?php include 'partials/main-nav.php'; ?>

        <div class="page-content">
            <div class="container-fluid">

                <!-- Judul -->
                <div class="row">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0">Setting Perhitungan</h4>
                        </div>
                    </div>
                </div>

                <div style="max-width: 640px;">
                    <form id="allocationSettingsForm" class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="minCarton" class="form-label">Minimal Carton Kirim</label>
                            <input type="number" class="form-control" id="minCarton" name="minCarton" placeholder="Masukkan minimal carton" min="0">
                        </div>
                        <div class="col-md-6">
                            <label for="rangeFullCMO" class="form-label">Range QTY Full CMO</label>
                            <input type="text" class="form-control" id="rangeFullCMO" name="rangeFullCMO" placeholder="Contoh: 10 (Dari Minimal Carton Kirim) -30">
                        </div>
                        <div class="col-md-6">
                            <label for="shipmentSizePercent" class="form-label">% Shipment Size</label>
                            <input type="number" class="form-control" id="shipmentSizePercent" name="shipmentSizePercent" placeholder="Masukkan persentase" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label for="MaxCMO" class="form-label">Max % CMO (Untuk Full Fill Kubikasi)</label>
                            <input type="number" class="form-control" id="MaxCMO" name="MaxCMO" placeholder="Masukkan persentase" min="0" max="100" >
                        </div>
                        <div class="col-md-6">
                            <label for="SkuLimit" class="form-label">SKU Limit Per Mix Cust</label>
                            <input type="number" class="form-control" id="SkuLimit" name="SkuLimit" placeholder="Masukkan limit" >
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary w-50" onclick="applySettings()">Apply Settings</button>
                            <button type="button" class="btn btn-outline-danger w-50" onclick="resetSettings()">Reset Settings</button>
                        </div>
                    </form>
                </div>

                <!-- Grid Alokasi -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title mb-0">Data Hasil Perhitungan</h4>
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

    <!-- Loading -->
    <div id="loadingOverlay" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
        <div class="loading-message">Loading...</div>
    </div>

    <!-- Scripts -->
    <?php include 'partials/vendor-scripts.php' ?>
    <script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script src="assets/js/allocation.js"></script>
    <script src="assets/js/allocation-settings.js"></script> <!-- File baru -->
    
</body>
</html>
