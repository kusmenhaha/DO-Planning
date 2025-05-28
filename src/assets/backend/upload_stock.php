<?php
session_start();
require '../../../vendor/autoload.php'; // <--- PASTIKAN PATH INI SESUAI DENGAN STRUKTUR FOLDER ANDA

use PhpOffice\PhpSpreadsheet\IOFactory;

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Upload request received at " . date('Y-m-d H:i:s'));

    if (isset($_FILES['stock_file']) && $_FILES['stock_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['stock_file']['tmp_name'];
        $fileName = $_FILES['stock_file']['name'];
        $fileSize = $_FILES['stock_file']['size'];
        $fileType = $_FILES['stock_file']['type'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        error_log("File details: Name='{$fileName}', Size='{$fileSize}', Type='{$fileType}', Ext='{$fileExtension}'");

        $allowedfileExtensions = ['xls', 'xlsx', 'csv'];

        if (in_array($fileExtension, $allowedfileExtensions)) {
            try {
                $reader = null;
                if ($fileExtension == 'csv') {
                    $reader = IOFactory::createReader('Csv');
                    // $reader->setDelimiter(',');
                    // $reader->setEnclosure('"');
                    // $reader->setLineEnding("\n");
                } elseif ($fileExtension == 'xls') {
                    $reader = IOFactory::createReader('Xls');
                } elseif ($fileExtension == 'xlsx') {
                    $reader = IOFactory::createReader('Xlsx');
                }

                if ($reader) {
                    error_log("Reader created for extension: {$fileExtension}");
                    $spreadsheet = $reader->load($fileTmpPath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $header = [];
                    $stockData = [];

                    $isFirstRow = true;
                    foreach ($sheet->getRowIterator() as $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(FALSE);

                        $rowData = [];
                        foreach ($cellIterator as $cell) {
                            $rowData[] = $cell->getValue();
                        }

                        if ($isFirstRow) {
                            $header = array_map(function($h) {
                                return trim((string)str_replace("\xc2\xa0", ' ', $h));
                            }, $rowData);
                            error_log("Headers read: " . implode(', ', $header));

                            $requiredHeaders = ['SKU', 'CHANNEL', 'QTY'];
                            $missingHeaders = array_diff($requiredHeaders, $header);
                            if (!empty($missingHeaders)) {
                                $response['message'] = 'File tidak memiliki header yang lengkap. Hilang: ' . implode(', ', $missingHeaders);
                                error_log("Missing headers detected: " . $response['message']);
                                echo json_encode($response);
                                exit;
                            }
                            $isFirstRow = false;
                        } else {
                            $tempRow = [];
                            foreach ($header as $index => $hName) {
                                $tempRow[$hName] = $rowData[$index] ?? null;
                            }
                            $stockData[] = $tempRow;
                        }
                    }

                    $_SESSION['stock_temp_data'] = $stockData;
                    $response['status'] = 'success';
                    $response['message'] = 'File stock berhasil diunggah dan diproses! ' . count($stockData) . ' baris data ditemukan.';
                    error_log("Data successfully processed and saved to session. Rows: " . count($stockData));

                } else {
                    $response['message'] = 'Tipe file tidak didukung oleh PHPSpreadsheet.';
                    error_log("Unsupported file type by PHPSpreadsheet: {$fileExtension}");
                }
            } catch (Exception $e) {
                $response['message'] = 'Gagal memproses file: ' . $e->getMessage();
                error_log('PHPSpreadsheet processing error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            }
        } else {
            $response['message'] = 'Ekstensi file tidak valid. Hanya CSV, XLS, XLSX yang diizinkan.';
            error_log("Invalid file extension: {$fileExtension}");
        }
    } else {
        $response['message'] = 'Gagal mengunggah file. Kode error: ' . ($_FILES['stock_file']['error'] ?? 'No file selected/error');
        error_log("File upload failed. Error code: " . ($_FILES['stock_file']['error'] ?? 'N/A') . " FILE: " . json_encode($_FILES));
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
}

echo json_encode($response);
exit;