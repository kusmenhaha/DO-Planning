<?php
require_once('../../services/database.php'); // sesuaikan pathnya
require __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['customer_file']) && $_FILES['customer_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['customer_file']['tmp_name'];

        // Matikan autocommit untuk transaksi manual
        $conn->autocommit(FALSE);
        $transactionStarted = true;

        try {
            $spreadsheet = IOFactory::load($fileTmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            $insertCount = 0;
            $errorCount = 0;

            $stmt = $conn->prepare("INSERT INTO mixcust 
                (cust, mix_cust, custname, pulau, region_scm, abm, channel_cust, shipment_type, port_kontainer, shipsizemajalengka) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if (!$stmt) {
                throw new Exception("Prepare statement gagal: " . $conn->error);
            }

            for ($i = 1; $i < count($data); $i++) {
                $row = $data[$i];

                if (empty($row[0]) && empty($row[1])) {
                    // Lewati baris kosong di kolom cust dan mix_cust
                    continue;
                }

                // Mapping kolom Excel ke variabel
                $cust = trim($row[0]);
                $mix_cust = trim($row[1]);
                $custname = trim($row[2]);
                $pulau = trim($row[3]);
                $region_scm = trim($row[4]);
                $abm = trim($row[5]);
                $channel_cust = trim($row[6]);
                $shipment_type = trim($row[7]);
                $port_kontainer = trim($row[8]);

                $shipsizemajalengka_raw = $row[9];
                $shipsizemajalengka = floatval(preg_replace('/[^0-9.\-]/', '', $shipsizemajalengka_raw));
                if ($shipsizemajalengka === '') {
                    $shipsizemajalengka = 0.0;
                }

                $stmt->bind_param(
                    "sssssssssd",
                    $cust, $mix_cust, $custname, $pulau, $region_scm,
                    $abm, $channel_cust, $shipment_type, $port_kontainer, $shipsizemajalengka
                );

                if ($stmt->execute()) {
                    $insertCount++;
                } else {
                    $errorCount++;
                }
            }

            // Commit transaksi
            $conn->commit();
            $transactionStarted = false;

            echo json_encode([
                'status' => 'success',
                'message' => "Data berhasil diupload dan disimpan. Insert: $insertCount baris, Gagal: $errorCount baris."
            ]);
        } catch (Exception $e) {
            // Rollback transaksi jika ada error
            if ($transactionStarted) {
                $conn->rollback();
                $transactionStarted = false;
            }
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses file: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File upload gagal']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid']);
}
