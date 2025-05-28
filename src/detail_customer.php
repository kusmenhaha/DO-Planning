<?php
include 'partials/main.php';
$subTitle = "Detail Customer";
include 'partials/title-meta.php';

// Pastikan parameter customer_id diterima
if (isset($_GET['customer_id'])) {
    $customerId = $_GET['customer_id'];

    // Di sini Anda perlu mengambil data detail customer berdasarkan $customerId
    // Anda mungkin perlu mengakses session shipment atau database Anda

    // Contoh (asumsi data shipment ada di dalam session):
    if (isset($_SESSION['shipment_data'])) {
        $foundCustomer = null;
        foreach ($_SESSION['shipment_data'] as $item) {
            if ($item['CUST#'] == $customerId) {
                $foundCustomer = $item;
                break;
            }
        }

        if ($foundCustomer) {
            // Data customer ditemukan, tampilkan detailnya
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <?php include 'partials/head-css.php'; ?>
            </head>
            <body>
                <div class="wrapper">
                    <?php include 'partials/topbar.php'; ?>
                    <?php include 'partials/main-nav.php'; ?>

                    <div class="page-content">
                        <div class="container-fluid">
                            <h3>Detail Customer</h3>

                            <div class="card">
                                <div class="card-body">
                                    <?php
                                    echo "<p><strong>CUST#:</strong> " . htmlspecialchars($foundCustomer['CUST#']) . "</p>";
                                    echo "<p><strong>CUSTOMERNAME:</strong> " . htmlspecialchars($foundCustomer['CUSTOMERNAME']) . "</p>";
                                    echo "<p><strong>SKU:</strong> " . htmlspecialchars($foundCustomer['SKU']) . "</p>";
                                    echo "<p><strong>CHANNEL:</strong> " . htmlspecialchars($foundCustomer['CHANNEL']) . "</p>";
                                    echo "<p><strong>Stock Qty:</strong> " . htmlspecialchars($foundCustomer['Stock Qty']) . "</p>";
                                    echo "<p><strong>OTD CMO:</strong> " . htmlspecialchars($foundCustomer['OTD CMO']) . "</p>";
                                    echo "<p><strong>Volume per Ctn (m3):</strong> " . htmlspecialchars(number_format($foundCustomer['Volume per Ctn (m3)'], 2)) . "</p>";
                                    echo "<p><strong>Total Volume OTD (m3):</strong> " . htmlspecialchars(number_format($foundCustomer['Total Volume OTD (m3)'], 2)) . "</p>";
                                    echo "<p><strong>Shipment Size Max (m3):</strong> " . htmlspecialchars(number_format($foundCustomer['Shipment Size Max (m3)'], 2)) . "</p>";
                                    echo "<p><strong>Final Allocated Volume (m3):</strong> " . htmlspecialchars(number_format($foundCustomer['Final Allocated Volume (m3)'], 2)) . "</p>";
                                    echo "<p><strong>Final Qty Kirim:</strong> " . htmlspecialchars($foundCustomer['Qty Kirim (ctn)']) . "</p>";
                                    echo "<p><strong>Truck per SKU:</strong> " . htmlspecialchars(number_format($foundCustomer['Truck per SKU'], 2)) . "</p>";
                                    echo "<p><strong>Total Truck per MIX_CUST:</strong> " . htmlspecialchars(number_format($foundCustomer['Total Truck per MIX_CUST'], 2)) . "</p>";
                                    echo "<p><strong>CSL (%):</strong> " . htmlspecialchars(number_format($foundCustomer['CSL (%)'], 2)) . "</p>";
                                    // Tambahkan detail informasi lain yang ingin Anda tampilkan
                                    ?>
                                </div>
                            </div>

                            <div class="mt-3">
                                <p><a href="pick.php">Kembali ke Pilih Customer</a></p>
                            </div>
                        </div>
                    </div>

                    <?php include 'partials/footer.php'; ?>
                </div>

                <?php include 'partials/vendor-scripts.php'; ?>
            </body>
            </html>
            <?php
        } else {
            // Customer tidak ditemukan
            echo "<p>Customer dengan CUST# " . htmlspecialchars($customerId) . " tidak ditemukan.</p>";
            echo "<p><a href='pick.php'>Kembali ke Pilih Customer</a></p>";
        }
    } else {
        echo "<p>Sesi data shipment tidak ditemukan. Pastikan data shipment sudah dihitung.</p>";
        echo "<p><a href='index.php'>Kembali ke Halaman Utama</a></p>";
    }
} else {
    // Jika parameter customer_id tidak diterima
    echo "<p>Parameter CUST# tidak valid.</p>";
    echo "<p><a href='pick.php'>Kembali ke Pilih Customer</a></p>";
}
?>