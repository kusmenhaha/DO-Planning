<?php
session_start(); // Pastikan session_start() ada di paling atas
header('Content-Type: application/json');

// Sertakan autoloader Composer untuk PhpSpreadsheet
// MENGGUNAKAN PATH YANG ANDA BERIKAN
require __DIR__ . '/../../../vendor/autoload.php'; // pastikan sudah composer install phpoffice/phpspreadsheet

// include '../../services/database.php'; // Dikomentari karena database tidak digunakan untuk menyimpan ke sesi di sini

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date; // Penting untuk mengonversi tanggal Excel

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['cmo_file'])) {
    $file = $_FILES['cmo_file']['tmp_name'];
    $fileName = $_FILES['cmo_file']['name'];
    $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

    $allowedFileTypes = ['csv', 'xlsx', 'xls'];
    if (!in_array(strtolower($fileType), $allowedFileTypes)) {
        echo json_encode(['status' => 'error', 'message' => 'Tipe file tidak valid. Hanya CSV, XLSX, XLS yang diizinkan.']);
        exit();
    }

    $cmoData = []; // Ini akan menampung data CMO yang akan disimpan ke sesi

    try {
        if (strtolower($fileType) === 'csv') {
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Lewati header
                fgetcsv($handle, 0, ',', '"', '\\');

                while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                    // Periksa apakah baris tidak kosong sepenuhnya
                    if (empty(array_filter($row))) {
                        continue; // Lewati baris kosong
                    }
                    if (count($row) >= 10) { // Asumsi ada 10 kolom data yang diharapkan (indeks 0-9)
                        $cmoData[] = [
                            'CUST#' => trim($row[0] ?? ''),
                            'CUSTOMER - CITY/CHANNEL' => trim($row[1] ?? ''),
                            'SKU' => trim($row[2] ?? ''),
                            'MATERIAL DESCRIPTION' => trim($row[3] ?? ''),
                            'VOLUME' => (float)str_replace(',', '.', trim($row[4] ?? '0')), // Konversi ke float, tangani koma
                            'CHANNEL' => trim($row[5] ?? ''),
                            'OTD CMO' => (float)str_replace(',', '.', trim($row[6] ?? '0')), // Konversi ke float, tangani koma
                            'CMO FINAL' => (float)str_replace(',', '.', trim($row[7] ?? '0')), // Konversi ke float, tangani koma
                            'ACTUAL' => (float)str_replace(',', '.', trim($row[8] ?? '0')), // Konversi ke float, tangani koma
                            'DO PLANNING' => (float)str_replace(',', '.', trim($row[9] ?? '0')), // Konversi ke float, tangani koma
                        ];
                    } else {
                        // Opsional: Log baris yang diabaikan karena jumlah kolom tidak sesuai
                        error_log("Baris CSV diabaikan karena jumlah kolom tidak sesuai: " . implode(',', $row));
                    }
                }
                fclose($handle);
            }
        } else { // Handle XLSX atau XLS
            $spreadsheet = IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $dataRows = $sheet->toArray(null, true, true, true); // Mendapatkan semua data sebagai array

            // Asumsi baris pertama adalah header, hilangkan dari dataRows
            array_shift($dataRows);

            foreach ($dataRows as $row) {
                // Periksa apakah baris tidak kosong sepenuhnya
                if (empty(array_filter($row))) {
                    continue; // Lewati baris kosong
                }
                // Pastikan jumlah kolom sesuai
                if (count($row) >= 10) { // Asumsi ada 10 kolom data yang diharapkan (A-J)
                    $cmoData[] = [
                        'CUST#' => trim($row['A'] ?? ''), // Sesuaikan indeks kolom Excel Anda jika berbeda
                        'CUSTOMER - CITY/CHANNEL' => trim($row['B'] ?? ''),
                        'SKU' => trim($row['C'] ?? ''),
                        'MATERIAL DESCRIPTION' => trim($row['D'] ?? ''),
                        'VOLUME' => (float)str_replace(',', '.', trim($row['E'] ?? '0')), // Konversi ke float, tangani koma
                        'CHANNEL' => trim($row['F'] ?? ''),
                        'OTD CMO' => (float)str_replace(',', '.', trim($row['G'] ?? '0')), // Konversi ke float, tangani koma
                        'CMO FINAL' => (float)str_replace(',', '.', trim($row['H'] ?? '0')), // Konversi ke float, tangani koma
                        'ACTUAL' => (float)str_replace(',', '.', trim($row['I'] ?? '0')), // Konversi ke float, tangani koma
                        'DO PLANNING' => (float)str_replace(',', '.', trim($row['J'] ?? '0')), // Konversi ke float, tangani koma
                    ];
                } else {
                    // Opsional: Log baris yang diabaikan karena jumlah kolom tidak sesuai
                    error_log("Baris Excel diabaikan karena jumlah kolom tidak sesuai: " . json_encode($row));
                }
            }
        }

        // Simpan seluruh array data ke sesi
        $_SESSION['cmo_temp_data'] = $cmoData;
        echo json_encode(['status' => 'success', 'message' => 'File berhasil diunggah. ' . count($cmoData) . ' baris data diproses.']);

    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        error_log('Kesalahan saat membaca file spreadsheet: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Kesalahan saat membaca file spreadsheet: ' . $e->getMessage()]);
    } catch (\Exception $e) {
        error_log('Terjadi kesalahan umum saat memproses file: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada file yang diunggah atau permintaan tidak valid.']);
}
?>