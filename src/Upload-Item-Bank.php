<?php include 'partials/main.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <?php
    $subTitle = "File Uploads";
    include 'partials/title-meta.php';
    include 'partials/head-css.php';
    ?>
</head>

<body>
    <div class="wrapper">
        <?php
        include 'partials/topbar.php';
        include 'partials/main-nav.php';
        ?>

        <div class="page-content">
            <div class="container">
                <h2>Unggah dan Baca File Excel</h2>

                <!-- ✅ ALERT JIKA ADA PESAN -->
                <?php
                if (isset($_SESSION['uploadMessage'])) {
                    echo $_SESSION['uploadMessage'];
                    unset($_SESSION['uploadMessage']);
                }
                ?>

                <!-- ✅ FORM UPLOAD -->
                <form method="POST" enctype="multipart/form-data" action="upload.php">
                    <div class="form-group">
                        <label for="file">Pilih file Excel (.xlsx):</label>
                        <input type="file" class="form-control-file" id="file" name="file" accept=".xlsx" required>
                        <small class="form-text text-muted">Pastikan file yang diunggah memiliki ekstensi .xlsx.</small>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Unggah dan Baca</button>
                </form>
            </div>

            <?php include 'partials/footer.php'; ?>
        </div>
    </div>

    <?php include 'partials/vendor-scripts.php'; ?>
    <script src="assets/js/components/form-fileupload.js"></script>
</body>
</html>
