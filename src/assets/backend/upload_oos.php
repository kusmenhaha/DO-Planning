<?php
session_start();

// ====== START DEBUGGING TEMPORARY ======
// Aktifkan error reporting untuk melihat pesan kesalahan PHP langsung (hanya untuk development)
// Di lingkungan produksi, setting display_errors harus 0
ini_set('display_errors', 1); // Set ke 0 di produksi
ini_set('display_startup_errors', 1); // Set ke 0 di produksi
error_reporting(E_ALL);

// Pastikan error_log diaktifkan dan dapat diakses (misal: /var/log/apache2/error.log atau custom path)
ini_set('log_errors', 1);
// Tentukan lokasi file log error PHP. Pastikan folder ini dapat ditulis oleh server web.
ini_set('error_log', __DIR__ . '/php_error.log');

error_log("--------------------------------------------------");
error_log("upload_oos.php accessed. Time: " . date('Y-m-d H:i:s'));
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("FILES array: " . print_r($_FILES, true));
error_log("POST array: " . print_r($_POST, true));
// ====== END DEBUGGING TEMPORARY ======

// Sesuaikan path ini ke folder vendor Anda
require __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'Permintaan tidak valid.' // Pesan default yang lebih umum
];

// File ini sekarang HANYA menangani metode POST untuk upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['oos_file']) && $_FILES['oos_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['oos_file'];

        error_log("File uploaded: " . $file['name'] . " (Temp path: " . $file['tmp_name'] . ")");

        // Validate file type
        $allowedFileTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel', // .xls
        ];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedFileTypes)) {
            $response['message'] = 'Jenis file tidak valid. Hanya file XLSX dan XLS yang diizinkan.';
            error_log("Error: Invalid file type detected: " . $fileType);
            echo json_encode($response);
            exit;
        }

        try {
            error_log("Attempting to load spreadsheet: " . $file['tmp_name']);
            $spreadsheet = IOFactory::load($file['tmp_name']);
            error_log("Spreadsheet loaded successfully.");

            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            error_log("Sheet data extracted. Total rows: " . count($sheetData));

            // Define expected columns from your request
            $expectedColumns = [
                'Urutan', 'Batch', 'PULAU', 'Region SCM', 'CUST#', 'DEPO', 'SKU', 'PARENT SKU', 'ITEM', 'Original OOS', 'STATUS DOI', 'Req QTY Kirim'
            ];
            error_log("Expected columns defined: " . implode(', ', $expectedColumns));

            // Pastikan ada data dan baris pertama adalah header
            if (empty($sheetData) || count($sheetData) < 2) {
                $response['message'] = 'File Excel kosong atau tidak memiliki data yang cukup.';
                error_log("Error: Excel file is empty or has less than 2 rows (header + data).");
                echo json_encode($response);
                exit;
            }

            // Dapatkan header dari baris pertama.
            $headerRowRaw = $sheetData[1];
            $headerRowOriginal = array_values($headerRowRaw);
            error_log("Raw Excel Headers (from first row): " . print_r($headerRowRaw, true));
            error_log("Excel Headers (array_values): " . print_r($headerRowOriginal, true));

            // Bersihkan (trim) dan konversi setiap header ke lowercase.
            $headerRowLower = array_map(function($header) {
                return strtolower(trim((string)$header));
            }, $headerRowOriginal);
            error_log("Excel Headers (lowercase & trimmed): " . print_r($headerRowLower, true));

            // Map column names to expected headers based on the first row's values
            $columnMapping = [];
            foreach ($expectedColumns as $expectedCol) {
                $expectedColLower = strtolower(trim($expectedCol));
                error_log("Searching for expected column (lowercase): '" . $expectedColLower . "'");

                $foundKey = array_search($expectedColLower, $headerRowLower);

                if ($foundKey !== false) {
                    $excelColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($foundKey + 1);
                    $columnMapping[$expectedCol] = $excelColumnLetter;
                    error_log("Found original column '" . $expectedCol . "' mapped to Excel letter: " . $excelColumnLetter);
                } else {
                    $response['message'] = "Kolom yang dibutuhkan '$expectedCol' tidak ditemukan di file Excel.";
                    error_log("ERROR: Expected column '" . $expectedCol . "' (lowercase: '" . $expectedColLower . "') NOT found in Excel headers.");
                    echo json_encode($response);
                    exit;
                }
            }
            error_log("Final Column Mapping: " . print_r($columnMapping, true));

            $processedData = [];
            // Start from the second row to get actual data
            for ($i = 2; $i <= count($sheetData); $i++) {
                $rowData = $sheetData[$i];
                $tempRow = [];
                foreach ($expectedColumns as $colName) {
                    $excelColumnLetter = $columnMapping[$colName];
                    $tempRow[$colName] = isset($rowData[$excelColumnLetter]) ? (string)$rowData[$excelColumnLetter] : null;
                }
                $processedData[] = $tempRow;
            }
            error_log("Processed " . count($processedData) . " data rows successfully.");

            // Store data in session
            $_SESSION['oos_data'] = $processedData;
            error_log("Data stored in session. Session data size: " . count($_SESSION['oos_data']) . " rows.");

            $response['status'] = 'success';
            $response['message'] = 'File diunggah dan diproses berhasil!';
            $response['data'] = $processedData; // Kirimkan data yang diproses kembali ke frontend

        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $response['message'] = 'Error membaca spreadsheet: ' . $e->getMessage();
            error_log("Spreadsheet Reader Exception: " . $e->getMessage());
        } catch (Exception $e) {
            $response['message'] = 'Terjadi kesalahan tak terduga: ' . $e->getMessage();
            error_log("Unexpected Exception: " . $e->getMessage());
        }
    } else {
        $uploadError = isset($_FILES['oos_file']['error']) ? $_FILES['oos_file']['error'] : 'Unknown error.';
        $response['message'] = 'Tidak ada file yang diunggah atau ada kesalahan saat mengunggah: ' . $uploadError;
        error_log("File upload error: " . $uploadError);
    }
} else {
    // Jika ada metode request selain POST untuk upload, berikan pesan error
    $response['message'] = 'Metode permintaan tidak didukung untuk operasi ini. Harap gunakan POST.';
    error_log("Invalid request method. Only POST is supported for upload_oos.php.");
}

echo json_encode($response);
error_log("Response sent: " . json_encode($response));

?>