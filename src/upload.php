<?php
session_start(); // Tambahkan ini agar bisa simpan notifikasi

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

                    $sku = $row[0] ?? null;
                    $desc = $row[1] ?? null;
                    $sls_org = $row[2] ?? null;
                    $channel = $row[3] ?? null;
                    $volume = $row[4] ?? null;
                    $matl_group = $row[5] ?? null;
                    $plant = $row[6] ?? null;
                    $parent = $row[7] ?? null;
                    $size_dimensions = $row[8] ?? null;
                    $country = $row[9] ?? null;
                    $gross_weight = $row[10] ?? null;
                    $bun = $row[11] ?? null;
                    $division = $row[12] ?? null;
                    $family_product = $row[13] ?? null;

                    $sql = "INSERT INTO itembank (sku, `desc`, sls_org, channel, volume, `matl_group`, plant, parent, size_dimensions, country, gross_weight, bun, division, `family_product`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param("ssssssssssssss",
                            $sku,
                            $desc,
                            $sls_org,
                            $channel,
                            $volume,
                            $matl_group,
                            $plant,
                            $parent,
                            $size_dimensions,
                            $country,
                            $gross_weight,
                            $bun,
                            $division,
                            $family_product
                        );

                        if ($stmt->execute()) {
                            $insertCount++;
                        } else {
                            $uploadMessage .= '<div class="alert alert-warning">Gagal upload baris ke-' . ($i + 1) . ': ' . $stmt->error . '</div>';
                        }
                        $stmt->close();
                    } else {
                        $uploadMessage .= '<div class="alert alert-danger">Error prepare statement: ' . $conn->error . '</div>';
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
    header("Location: itembank.php"); // Ganti sesuai nama file form upload kamu
    exit();
}
?>
