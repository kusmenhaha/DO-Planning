<?php
session_start(); // Pastikan ini baris pertama setelah <?php
include 'update_cmo_data.php';
// Opsional: Untuk debugging, matikan tampilan error di browser (jangan di produksi)
// ini_set('display_errors', 0);
// error_reporting(E_ALL & ~E_NOTICE);

include '../../services/database.php';
 // Pastikan path ini benar

header('Content-Type: application/json'); // PENTING: ini harus SETELAH session_start()

$cmoData = $_SESSION['cmo_temp_data_update'] ?? [];
$finalData = [];

// Ambil filter dari request GET.
// PHP akan otomatis mengubah parameter seperti `col_name[]` menjadi array di $_GET
$filterParams = [];
$filterColumns = [
    'MIXCUST',
    'CUST#',
    'CUSTOMER - CITY/CHANNEL',
    'REGION SCM',
    'CHANNEL CUST',
    'SKU',
    'MATERIAL DESCRIPTION',
    'CHANNEL',
    'OTD CMO'
];

foreach ($filterColumns as $col) {
    if (isset($_GET[$col])) {
        // Karena filter di frontend sekarang adalah single text input, kita bisa langsung ambil stringnya
        // Tidak perlu lagi mengonversi ke array, kecuali Anda memang ingin mendukung multiple value di masa depan
        $filterParams[$col] = $_GET[$col]; // Langsung ambil stringnya
    } else {
        $filterParams[$col] = ''; // Pastikan string kosong jika tidak ada filter
    }
}

if (!empty($cmoData)) {
    foreach ($cmoData as $cmoItem) {
        // Pastikan kolom 'CUST#' ada sebelum mengaksesnya
        // Pastikan nilai CUST# diambil dengan aman untuk query DB
        $custNo = isset($cmoItem['CUST#']) ? mysqli_real_escape_string($conn, $cmoItem['CUST#']) : '';

        // Query mixcust untuk mendapatkan info tambahan
        $mixCustInfo = [];
        if (!empty($custNo)) {
            $sql = "SELECT mix_cust, region_scm, channel_cust FROM mixcust WHERE cust = '$custNo'";
            $result = mysqli_query($conn, $sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $mixCustInfo = mysqli_fetch_assoc($result);
            }
        }

        // Siapkan baris data dengan informasi dari CMO dan mixcust
        $row = [
            'MIXCUST' => $mixCustInfo['mix_cust'] ?? '',
            'CUST#' => $cmoItem['CUST#'] ?? '',
            'CUSTOMER - CITY/CHANNEL' => $cmoItem['CUSTOMER - CITY/CHANNEL'] ?? '',
            'REGION SCM' => $mixCustInfo['region_scm'] ?? '',
            'CHANNEL CUST' => $mixCustInfo['channel_cust'] ?? '',
            'SKU' => $cmoItem['SKU'] ?? '',
            'MATERIAL DESCRIPTION' => $cmoItem['MATERIAL DESCRIPTION'] ?? '',
            'VOLUME' => $cmoItem['VOLUME'] ?? '',
            'CHANNEL' => $cmoItem['CHANNEL'] ?? '',
            'OTD CMO' => $cmoItem['OTD CMO'] ?? '',
            'CMO FINAL' => $cmoItem['CMO FINAL'] ?? '',
            'ACTUAL' => $cmoItem['ACTUAL'] ?? '',
            'DO PLANNING' => $cmoItem['DO PLANNING'] ?? '',
        ];

        // Terapkan filter
        $match = true;

        foreach ($filterColumns as $col) {
            $currentFilterValue = $filterParams[$col]; // Sekarang string tunggal

            // Jika filter value kosong, lewati kolom ini
            if (empty($currentFilterValue)) {
                continue;
            }

            // Normalisasi nilai kolom untuk perbandingan case-insensitive
            $columnValue = strtolower(trim($row[$col] ?? ''));
            $filterValueLower = strtolower(trim($currentFilterValue));

            // Logika filter spesifik untuk OTD CMO
            if ($col === 'OTD CMO') {
                // Pastikan penanganan koma dan titik sebagai desimal
                $otdCmoValue = (float)str_replace(',', '.', ($row['OTD CMO'] ?? '0')); // Gunakan 0 jika null/empty

                if ($filterValueLower === 'positive' && $otdCmoValue > 0) {
                    // Cocok
                } elseif ($filterValueLower === 'negative' && $otdCmoValue < 0) {
                    // Cocok
                } elseif ($filterValueLower === 'zero' && $otdCmoValue === 0.0) {
                    // Cocok
                } elseif (strpos($columnValue, $filterValueLower) === false) {
                    // Jika filter bukan 'positive', 'negative', 'zero' dan tidak cocok sebagian
                    $match = false;
                    break;
                }
            } else {
                // Untuk kolom teks lainnya (filter sebagian)
                if (strpos($columnValue, $filterValueLower) === false) {
                    $match = false; // Jika nilai kolom tidak mengandung filter value
                    break; // Hentikan pemeriksaan filter untuk baris ini
                }
            }
        }

        if ($match) {
            $finalData[] = $row;
        }
    }
}

echo json_encode(['data' => $finalData]);
?>