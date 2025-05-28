<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Koneksi ke database
$conn = mysqli_connect("localhost", "root", "Khusnan02", "larkon");

// Periksa koneksi
if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// Log data POST yang diterima
error_log("Data POST di upload_cust.php: " . print_r($_POST, true));

// Pastikan tombol upload ditekan dan data selected_cust_upload ada
if (isset($_POST['upload_cust']) && isset($_POST['selected_cust_upload'])) {
    // Decode data customer yang dipilih dari format JSON
    $selectedCustHashes = json_decode($_POST['selected_cust_upload'], true);
    error_log("Isi \$selectedCustHashes setelah decode: " . print_r($selectedCustHashes, true));

    if (!is_array($selectedCustHashes) || empty($selectedCustHashes)) {
        $_SESSION['upload_message'] = "Data customer terpilih tidak valid.";
        $_SESSION['upload_status'] = 'warning';
        error_log("Data selectedCustHashes tidak valid (bukan array atau kosong)");
    } else {
        // Ambil data shipment dari session
        $shipmentData = $_SESSION['calculate_shipment_result'] ?? $_SESSION['shipment_data'] ?? [];
        error_log("Isi \$shipmentData dari session: " . print_r($shipmentData, true));

        if (empty($shipmentData)) {
            $_SESSION['upload_message'] = "Data shipment tidak ditemukan di session.";
            $_SESSION['upload_status'] = 'warning';
            error_log("Data shipment tidak ditemukan di session");
        } else {
            $upload_success = true;
            $sql_insert = "INSERT INTO uploaded_customers (`CUST#`, CUSTOMERNAME, SKU, `Material Description`, CHANNEL, `Stock Qty`, `Final Stock`, `OTD CMO`, `Volume per Ctn (m3)`, `Total Volume OTD (m3)`, `Shipment Size Max (m3)`, `Allocated Volume (m3)`, `Final Allocated Volume (m3)`, `Qty Kirim (ctn)`, `Truck per SKU`, `DO Planning`, `ACTUAL`, `CMO Final`, `CSL (%)`, `MIX_CUST`, `Total Truck per MIX_CUST`, `Planning`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql_insert);

            if ($stmt) {
                foreach ($selectedCustHashes as $custHash) {
                    error_log("Memproses CUST#: " . $custHash);
                    $found = false;
                    foreach ($shipmentData as $row) {
                        if (trim($row['CUST#']) == trim($custHash)) {
                            $found = true;

                            $customerName = $row['CUSTOMERNAME'] ?? '-';
                            $sku = $row['SKU'] ?? '-';
                            $materialDescription = $row['Material Description'] ?? '-';
                            $channel = $row['CHANNEL'] ?? '-';
                            $stockQty = $row['Stock Qty'] ?? 0;
                            $finalStock = $row['Final Stock'] ?? 0;
                            $otdCmo = $row['OTD CMO'] ?? 0;
                            $volumePerCtn = $row['Volume per Ctn (m3)'] ?? 0;
                            $totalVolumeOtd = $row['Total Volume OTD (m3)'] ?? 0;
                            $shipmentSizeMax = $row['Shipment Size Max (m3)'] ?? 0;
                            $allocatedVolume = $row['Allocated Volume (m3)'] ?? 0;
                            $finalAllocatedVolume = $row['Final Allocated Volume (m3)'] ?? 0;
                            $qtyKirim = $row['Qty Kirim (ctn)'] ?? 0;
                            $truckPerSku = $row['Truck per SKU'] ?? 0;
                            $doPlanning = $row['DO Planning'] ?? 0;
                            $actual = $row['ACTUAL'] ?? 0;
                            $cmoFinal = $row['CMO Final'] ?? 0;
                            $cslPercent = $row['CSL (%)'] ?? 0;
                            $mixCust = $row['MIX_CUST'] ?? '-';
                            $totalTruckPerMixCust = $row['Total Truck per MIX_CUST'] ?? 0;
                            $planning = $row['Planning'] ?? 0;

                            mysqli_stmt_bind_param($stmt, "ssssssssssssssssssssss",
                                $row['CUST#'],
                                $customerName,
                                $sku,
                                $materialDescription,
                                $channel,
                                $stockQty,
                                $finalStock,
                                $otdCmo,
                                $volumePerCtn,
                                $totalVolumeOtd,
                                $shipmentSizeMax,
                                $allocatedVolume,
                                $finalAllocatedVolume,
                                $qtyKirim,
                                $truckPerSku,
                                $doPlanning,
                                $actual,
                                $cmoFinal,
                                $cslPercent,
                                $mixCust,
                                $totalTruckPerMixCust,
                                $planning
                            );

                            if (!mysqli_stmt_execute($stmt)) {
                                $_SESSION['upload_message'] = "Gagal mengunggah data untuk CUST# " . $custHash . ": " . mysqli_stmt_error($stmt);
                                $_SESSION['upload_status'] = 'danger';
                                $upload_success = false;
                                error_log("MySQL Error (Execute INSERT for CUST# " . $custHash . "): " . mysqli_stmt_error($stmt));
                                break; // Hanya keluar dari loop dalam jika ada kesalahan SQL untuk customer ini
                            }
                            break; // Lanjutkan ke customer berikutnya setelah ditemukan dan diunggah
                        }
                    }
                    if (!$found) {
                        $_SESSION['upload_message'] = "Data untuk CUST# " . $custHash . " tidak ditemukan di session.";
                        $_SESSION['upload_status'] = 'warning';
                        $upload_success = false;
                        error_log("Data untuk CUST# " . $custHash . " tidak ditemukan di shipmentData.");
                    }
                }
                mysqli_stmt_close($stmt);

                if ($upload_success) {
                    $_SESSION['upload_message'] = "Data customer terpilih berhasil diunggah ke MySQL!";
                    $_SESSION['upload_status'] = 'success';
                }
            } else {
                $_SESSION['upload_message'] = "Gagal menyiapkan statement untuk upload: " . mysqli_error($conn);
                $_SESSION['upload_status'] = 'danger';
                error_log("MySQL Error (Prepare INSERT): " . mysqli_error($conn));
            }
        }
    }

    // Menutup koneksi database
    mysqli_close($conn);
} else {
    $_SESSION['upload_message'] = "Akses tidak valid.";
    $_SESSION['upload_status'] = 'danger';
}

// Redirect kembali ke halaman detail customer atau halaman lain yang sesuai
header('Location: resultfix.php');
exit;
?>