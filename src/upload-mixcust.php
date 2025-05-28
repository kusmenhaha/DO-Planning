<?php
session_start();

require __DIR__ . '/../vendor/autoload.php';
include('services/database.php');

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === 0) {
        $fileTmpName = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileExtension !== 'xlsx') {
            $_SESSION['uploadMessage'] = '<div class="alert alert-danger mt-3">Hanya file .xlsx yang diperbolehkan.</div>';
        } else {
            try {
                $spreadsheet = IOFactory::load($fileTmpName);
                $sheet = $spreadsheet->getActiveSheet();
                $excelData = $sheet->toArray();

                $insertCount = 0;
                $uploadMessage = '';

                for ($i = 1; $i < count($excelData); $i++) {
                    $row = $excelData[$i];

                    $cust = $row[0] ?? null;
                    $mix_cust = $row[1] ?? null;
                    $custname = $row[2] ?? null;
                    $pulau = $row[3] ?? null;
                    $region_scm = $row[4] ?? null;
                    $abm = $row[5] ?? null;
                    $channel_cust = $row[6] ?? null;
                    $shipment_type = $row[7] ?? null;
                    $port_kontainer = $row[8] ?? null;
                    $shipsizemajalengka = $row[9] ?? null;
                    $shipsizerancaekek = $row[10] ?? null;
                    $shipsizerdc = $row[11] ?? null;

                    $sql = "INSERT INTO mixcust (cust, mix_cust, custname, pulau, region_scm, abm, channel_cust, shipment_type, port_kontainer, shipsizemajalengka, shipsizerancaekek, shipsizerdc) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param("ssssssssssss",
                            $cust,
                            $mix_cust,
                            $custname,
                            $pulau,
                            $region_scm,
                            $abm,
                            $channel_cust,
                            $shipment_type,
                            $port_kontainer,
                            $shipsizemajalengka,
                            $shipsizerancaekek,
                            $shipsizerdc
                        );

                        if ($stmt->execute()) {
                            $insertCount++;
                        } else {
                            $uploadMessage .= '<div class="alert alert-warning">Gagal upload baris ke-' . ($i + 1) . ': ' . $stmt->error . '</div>';
                        }

                        $stmt->close();
                    } else {
                        $uploadMessage .= '<div class="alert alert-danger">Prepare statement gagal: ' . $conn->error . '</div>';
                    }
                }

                if ($insertCount > 0) {
                    $uploadMessage .= '<div class="alert alert-success mt-3">' . $insertCount . ' baris berhasil diunggah ke database.</div>';
                } else {
                    $uploadMessage .= '<div class="alert alert-danger mt-3">Tidak ada data yang berhasil diunggah.</div>';
                }

                $_SESSION['uploadMessage'] = $uploadMessage;

            } catch (Exception $e) {
                $_SESSION['uploadMessage'] = '<div class="alert alert-danger mt-3">Gagal membaca file Excel: ' . $e->getMessage() . '</div>';
            }
        }
    } else {
        $_SESSION['uploadMessage'] = '<div class="alert alert-danger mt-3">Tidak ada file yang diupload.</div>';
    }

    // Redirect kembali ke halaman form upload
    header("Location: itembank.php"); // Ganti sesuai file form upload kamu
    exit();
}
?>
