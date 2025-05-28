<?php
include 'partials/main.php';
session_start();

// Tambahkan baris ini untuk melihat isi session selected_cust saat halaman resultfix.php dimuat
error_log("Session selected_cust di resultfix.php (saat load): " . print_r($_SESSION['selected_cust'], true));

// Simpan data yang dipilih ke dalam session JIKA data POST ada
if (isset($_POST['selected_cust'])) {
    $_SESSION['selected_cust'] = $_POST['selected_cust'];
    // Tambahkan baris ini untuk melihat isi session setelah menerima POST (meskipun ini seharusnya sudah ada dari pick.php)
    error_log("Session selected_cust di resultfix.php (setelah POST): " . print_r($_SESSION['selected_cust'], true));
}

// Proses reset pilihan customer
if (isset($_POST['reset_cust'])) {
    unset($_SESSION['selected_cust']);
    header('Location: pick.php');
    exit;
}

// Cek session selected_cust, jika kosong redirect ke pick.php
if (!isset($_SESSION['selected_cust']) || count($_SESSION['selected_cust']) === 0) {
    header('Location: pick.php');
    exit;
}

$selectedCust = $_SESSION['selected_cust'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    $subTitle = "Detail Shipment Customer Terpilih";
    include 'partials/title-meta.php';
    ?>

    <link href="https://unpkg.com/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
    <?php include 'partials/head-css.php'; ?>

    <style>
        #delete-form {
            position: relative;
            z-index: 10;
            margin-left: 10px;
        }
        .card-body {
            padding-bottom: 80px;
        }
        /* Responsive overflow for Grid.js */
        .gridjs-container {
            overflow-x: auto;
        }
        /* Center text in table headers and cells */
        th, td {
            text-align: center !important;
        }
        .button-group-bottom {
            margin-top: 15px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start; /* Mengatur agar tombol dimulai dari kiri */
        }

        .button-group-bottom form {
            margin-left: auto; /* Mendorong tombol reset ke kanan */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php
            include 'partials/topbar.php';
            include 'partials/main-nav.php';
        ?>

        <div class="page-content">
            <div class="container-fluid">
                <?php
                if (isset($_SESSION['upload_message'])) {
                    $alert_class = ($_SESSION['upload_status'] == 'success') ? 'alert-success' : 'alert-danger';
                    echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
                    echo $_SESSION['upload_message'];
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                    unset($_SESSION['upload_message']);
                    unset($_SESSION['upload_status']);
                }
                ?>
                <div class="row justify-content-center">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-3" id="search">Detail Shipment Customer Terpilih</h5>
                                <div class="table-responsive py-2">
                                    <div id="table-search"></div>
                                </div>

                                <div class="button-group-bottom">
                                    <a href="pick.php" class="btn btn-primary">Kembali ke Daftar Customer</a>
                                    <form method="POST">
                                        <button type="submit" class="btn btn-warning" name="reset_cust">Reset Pilihan Customer</button>
                                    </form>
                                    <form method="POST" action="upload_cust.php">
                                        <input type="hidden" name="selected_cust_upload" value='<?php echo json_encode($selectedCust); ?>'>
                                        <button type="submit" class="btn btn-success" name="upload_cust">Unggah Data Terpilih ke MySQL</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'partials/footer.php'; ?>
    </div>

    <?php include 'partials/vendor-scripts.php'; ?>

    <script src="https://unpkg.com/gridjs/dist/gridjs.umd.js"></script>

    <script>
        new gridjs.Grid({
            columns: [
                'CUST#',
                'CUSTOMERNAME',
                'SKU',
                'Material Description',
                'CHANNEL',
                'Stock Qty',
                'OTD CMO',
                'Volume per Ctn (m3)',
                'Total Volume OTD (m3)',
                'Shipment Size Max (m3)',
                'Final Allocated Volume (m3)',
                'Qty Kirim (ctn)',
                'Truck per SKU',
                'Total Truck per MIX_CUST',
                'CSL (%)'
            ],
            search: true,
            pagination: {
                enabled: true,
                limit: 5
            },
            sort: true,
            server: {
                url: 'calculate_shipment_volume.php',
                then: data => data
                    .filter(item => <?php echo json_encode($selectedCust); ?>.includes(item['CUST#']))
                    .map(item => [
                        item['CUST#'],
                        item['CUSTOMERNAME'],
                        item['SKU'],
                        item['Material Description'],
                        item['CHANNEL'],
                        item['Stock Qty'],
                        item['OTD CMO'],
                        item['Volume per Ctn (m3)'],
                        item['Total Volume OTD (m3)'],
                        item['Shipment Size Max (m3)'],
                        item['Final Allocated Volume (m3)'],
                        item['Qty Kirim (ctn)'],
                        item['Truck per SKU'],
                        item['Total Truck per MIX_CUST'],
                        item['CSL (%)']
                    ])
            },
            style: {
                th: {
                    'background-color': '#f8f9fa',
                    'text-align': 'center'
                },
                td: {
                    'text-align': 'center'
                }
            }
        }).render(document.getElementById("table-search"));
    </script>
</body>
</html>