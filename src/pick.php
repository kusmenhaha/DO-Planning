<?php
// Aktifkan ini jika menggunakan session
// session_start();
include 'partials/main.php';
?>

<head>
  <?php
  $subTitle = "Pilih Area Alokasi";
  include 'partials/title-meta.php';
  include 'partials/head-css.php';
  ?>
  <link rel="stylesheet" href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" />
  <link rel="stylesheet" href="assets/css/pick.css" />
  <style>
    /* ... (CSS sama persis seperti versi Anda, tidak diubah) ... */
  </style>
</head>

<body>
  <div class="wrapper">
    <?php include 'partials/topbar.php'; ?>
    <?php include 'partials/main-nav.php'; ?>

    <div class="page-content">
      <div class="container-fluid">

        <!-- Page Title -->
        <div class="row">
          <div class="col-12">
            <!-- <div class="page-title-box d-sm-flex align-items-center justify-content-between">
              <h4 class="mb-sm-0">Pilih Area Alokasi (MIXCUST)</h4>
            </div> -->
          </div>
        </div>

        <!-- Card untuk Data Grid -->
        <div class="row mt-4">
          <div class="col-lg-12">
            <div class="card">

              <!-- Header Card -->
              <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Data Alokasi per MIXCUST</h4>

                <div class="header-controls">
                  <label for="zoomRange" class="mb-0">Zoom Tabel:</label>
                  <input type="range" id="zoomRange" min="50" max="200" value="100" step="10" />
                  <span id="zoomValue">100%</span>
                  <button class="btn btn-secondary btn-sm" id="btnResetPilihan" type="button">Reset Pilihan</button>
                 
                </div>
              </div>

              <!-- Tombol Refresh Hidden -->
              <button id="hardRefreshBtn" style="display:none;"></button>

              <!-- Body Card -->
              <div class="card-body">

                <!-- Filter Tambahan -->
                <div class="filter-container">
                  <div class="filter-item">
                    <label for="filterStatus">Status TRUCK:</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                      <option value="">-- Filter --</option>
                      <option value="full">Full</option>
                      <option value="kurang">Kurang</option>
                    </select>
                  </div>

                  <div class="filter-item">
                    <label for="filterCust">Customer:</label>
                    <select id="filterCust" class="form-select form-select-sm">
                      <option value="">-- Filter Customer --</option>
                    </select>
                  </div>

                  <div class="filter-item">
                    <label for="filterCustomerCity">City/Channel:</label>
                    <select id="filterCustomerCity" class="form-select form-select-sm">
                      <option value="">-- Filter City/Channel --</option>
                    </select>
                  </div>

                  <div class="filter-item">
                    <label for="filterChannelCust">Channel Cust:</label>
                    <select id="filterChannelCust" class="form-select form-select-sm">
                      <option value="">-- Filter Channel Cust --</option>
                    </select>
                  </div>

                  <div class="filter-item">
                    <label for="filterPulau">Pulau:</label>
                    <select id="filterPulau" class="form-select form-select-sm">
                      <option value="">-- Filter Pulau --</option>
                    </select>
                  </div>

                  <div class="filter-item">
                    <label for="filterRegionSCM">Region SCM:</label>
                    <select id="filterRegionSCM" class="form-select form-select-sm">
                      <option value="">-- Filter Region SCM --</option>
                    </select>
                  </div>

                  <div class="filter-item">
                    <label for="filterShipmentType">Shipment Type:</label>
                    <select id="filterShipmentType" class="form-select form-select-sm">
                      <option value="">-- Filter Shipment Type --</option>
                    </select>
                  </div>

                  <!-- ✅ Tambahan Filter CSL% -->
                  <div class="filter-item">
                    <label for="filterShortCSL">CSL%:</label>
                    <select id="filterShortCSL" class="form-select form-select-sm">
                      <option value="">-- All CSL --</option>
                      <option value="asc">CSL Terkecil ke Terbesar</option>
                      <option value="desc">CSL Terbesar ke Terkecil</option>
                    </select>
                  </div>

                  <div class="filter-actions">
                    <button id="resetFilterBtn" type="button" class="btn btn-outline-secondary btn-sm">Reset Filter</button>
                  </div>
                  <div class="filter-actions">
                    <button  id="btnPilih" type="button" class="btn btn-outline-secondary btn-sm">Pilih Area</button>
                  </div>
                   <!-- <button class="btn btn-primary btn-sm" id="btnPilih" type="button">Pilih Area</button> -->
                </div>
                <!-- GridJS Table -->
                <div id="pickGrid"></div>
              </div>
            </div>
          </div>
        </div>

      </div>
      <?php include "partials/footer.php"; ?>
    </div>
  </div>

  <?php include 'partials/vendor-scripts.php'; ?>
  <script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
  <script src="cortab/colResizable-1.6.min.js"></script>
  <script src="assets/js/pick.js"></script>
</body>
</html>
