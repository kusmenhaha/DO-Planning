<?php
session_start();

// Aktifkan pelaporan error untuk debugging (HANYA UNTUK PENGEMBANGAN!)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Custom exception handler
set_exception_handler(function ($exception) {
    header('Content-Type: application/json');
    error_log("Uncaught Exception in get_stock_data.php: " . $exception->getMessage() . ' in ' . $exception->getFile() . ' on line ' . $exception->getLine());
    echo json_encode([
        'status' => 'error',
        'message' => 'An unexpected server error occurred.',
        'details' => $exception->getMessage()
    ]);
    exit();
});

// Custom error handler (untuk menangani notices, warnings, dll.)
set_error_handler(function ($severity, $message, $file, $line) {
    // Abaikan E_NOTICE dan E_WARNING agar tidak membuat ErrorException
    if (!($severity === E_NOTICE || $severity === E_WARNING)) {
        error_log("PHP Error (Severity: {$severity}): {$message} in {$file} on line {$line}");
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false; // Kembali ke error handler PHP standar untuk notice/warning
});

// Periksa keberadaan file koneksi database
if (!file_exists('../../services/database.php')) {
    header('Content-Type: application/json');
    error_log("Database connection file not found at ../../services/database.php");
    echo json_encode(['status' => 'error', 'message' => 'Database connection file not found!']);
    exit;
}
include '../../services/database.php'; // Masukkan file koneksi database

// Periksa koneksi database
if (!$conn) {
    header('Content-Type: application/json');
    error_log("Database connection failed: " . mysqli_connect_error());
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed! ' . mysqli_connect_error()]);
    exit;
}

header('Content-Type: application/json');

error_log("--- START get_stock_data.php ---"); // Debug: Penanda awal eksekusi script
error_log("Session ID: " . session_id());
error_log("Is 'stock_temp_data' set in session? " . (isset($_SESSION['stock_temp_data']) ? 'Yes' : 'No') . ", Count: " . (isset($_SESSION['stock_temp_data']) ? count($_SESSION['stock_temp_data']) : '0'));


// Ambil data stok dari sesi. Ini adalah sumber data ASLI yang lengkap.
// Gunakan referensi (&) agar perubahan pada $stockDataFromSession akan memengaruhi $_SESSION['stock_temp_data'] secara langsung.
$stockDataFromSession = &$_SESSION['stock_temp_data'];
// Pastikan $stockDataFromSession diinisialisasi sebagai array jika belum ada di sesi
if (!isset($_SESSION['stock_temp_data'])) {
    $_SESSION['stock_temp_data'] = [];
}

// Ambil data alokasi dari sesi (digunakan untuk menghitung 'Planning')
$alokasiData = $_SESSION['selected_data'] ?? [];

// Buat mapping SKU → total planning dari data alokasi
$skuPlanningMap = [];
foreach ($alokasiData as $row) {
    $sku = $row['SKU'] ?? '';
    $finalQty = floatval($row['Final Allocated QTY'] ?? 0);
    if (!empty($sku)) {
        if (!isset($skuPlanningMap[$sku])) {
            $skuPlanningMap[$sku] = 0;
        }
        $skuPlanningMap[$sku] += $finalQty;
    }
}

// Ambil deskripsi material dari database 'itembank'
$materialDescriptions = [];
if (!empty($stockDataFromSession)) {
    $skuList = [];
    foreach ($stockDataFromSession as $item) {
        if (!empty($item['SKU'])) {
            $skuList[] = mysqli_real_escape_string($conn, $item['SKU']);
        }
    }

    if (!empty($skuList)) {
        $uniqueSkuList = array_unique($skuList);
        $chunkSize = 900; // Ukuran chunk untuk query IN agar tidak terlalu panjang
        $chunkedSkuLists = array_chunk($uniqueSkuList, $chunkSize);
        foreach ($chunkedSkuLists as $chunk) {
            $skuIn = "'" . implode("','", $chunk) . "'";
            $sqlItembank = "SELECT sku, `desc` FROM itembank WHERE sku IN ($skuIn)";
            $resultItembank = mysqli_query($conn, $sqlItembank);
            if ($resultItembank) {
                while ($rowItembank = mysqli_fetch_assoc($resultItembank)) {
                    $materialDescriptions[$rowItembank['sku']] = $rowItembank['desc'];
                }
            } else {
                error_log("Error fetching from itembank: " . mysqli_error($conn));
            }
        }
    }
}

// Bangun struktur data final yang akan dikembalikan ke frontend
// Ini adalah data yang akan difilter
$finalData = [];
// Gunakan indeks untuk memodifikasi array $_SESSION['stock_temp_data'] secara langsung
foreach ($stockDataFromSession as $index => &$stockItem) { // Gunakan referensi (&) pada $stockItem
    $sku = $stockItem['SKU'] ?? '';
    $materialDescription = $materialDescriptions[$sku] ?? '';
    $qty = floatval($stockItem['QTY'] ?? 0);
    $planning = $skuPlanningMap[$sku] ?? 0;
    $sisaQty = $qty - $planning;

    // Modifikasi elemen langsung di dalam $_SESSION['stock_temp_data']
    $stockItem['Material Description'] = $materialDescription;
    $stockItem['Planning'] = $planning;
    $stockItem['Sisa QTY'] = $sisaQty; // Ini akan disimpan di sesi

    // Tambahkan item yang sudah dimodifikasi ke $finalData untuk respons JSON
    $finalData[] = $stockItem;
}
unset($stockItem); // Putuskan referensi setelah loop selesai

error_log("Initial data built for grid. Total rows: " . count($finalData));
error_log("Updated 'stock_temp_data' in session. Example item with Sisa QTY: " . json_encode($stockDataFromSession[0] ?? 'N/A')); // Debug: Cek item pertama di sesi


// --- START FILTER LOGIC ---
$filterParams = [];
// Array asosiatif ini memetakan nama kolom internal ($row key)
// ke nama parameter GET yang DITERIMA PHP (yang akan memiliki underscore jika ada spasi)
$columnMap = [
    'SKU' => 'SKU',
    'Material Description' => 'Material Description',
    'CHANNEL' => 'CHANNEL',
    'Sisa QTY' => 'Sisa_QTY' // <-- PERUBAHAN KRUSIAL DI SINI! (Spasi diubah jadi underscore oleh PHP)
];

error_log("GET parameters received by PHP: " . json_encode($_GET)); // Debug: Lihat semua GET params yang diterima PHP

foreach ($columnMap as $internalColName => $getParamName) {
    // Hanya tambahkan filter jika parameter GET ada DAN tidak kosong
    if (isset($_GET[$getParamName]) && $_GET[$getParamName] !== '') {
        $filterParams[$internalColName] = trim($_GET[$getParamName]);
        error_log("Filter parameter detected for internal column '{$internalColName}' (GET param '{$getParamName}'): '{$filterParams[$internalColName]}'");
    }
}

if (!empty($filterParams)) {
    error_log("Applying filters. Active filters in PHP: " . json_encode($filterParams));
    $filteredData = [];
    foreach ($finalData as $row) {
        $match = true; // Asumsi baris ini cocok pada awalnya
        foreach ($filterParams as $columnId => $filterValue) {
            // Jika kolom yang difilter tidak ada di baris data, anggap tidak match
            if (!isset($row[$columnId])) {
                $match = false;
                error_log("Column '{$columnId}' not found in current row. Skipping row.");
                break; // Keluar dari loop filterParams untuk baris ini
            }

            // Logika spesifik untuk filter 'Sisa QTY'
            if ($columnId === 'Sisa QTY') {
                $sisaVal = floatval($row['Sisa QTY']); // Pastikan nilai adalah float
                error_log("Checking 'Sisa QTY'. Row value: {$sisaVal}, Filter value: '{$filterValue}'");

                switch ($filterValue) {
                    case '<0':
                        if (!($sisaVal < 0)) {
                            $match = false;
                            error_log("Sisa QTY mismatch (expected < 0): Row value {$sisaVal}");
                        }
                        break;
                    case '>0':
                        if (!($sisaVal > 0)) {
                            $match = false;
                            error_log("Sisa QTY mismatch (expected > 0): Row value {$sisaVal}");
                        }
                        break;
                    case '=0':
                        // Gunakan PHP_FLOAT_EPSILON untuk perbandingan float yang aman dengan nol
                        if (abs($sisaVal) > PHP_FLOAT_EPSILON) {
                            $match = false;
                            error_log("Sisa QTY mismatch (expected = 0): Row value {$sisaVal}");
                        }
                        break;
                    default:
                        // Jika nilai filter Sisa QTY tidak dikenali (ini seharusnya tidak terjadi jika frontend benar)
                        // Maka anggap baris tidak match, karena ini adalah filter yang tidak valid.
                        error_log("WARNING: Unrecognized 'Sisa QTY' filter value: '{$filterValue}'. Setting match to false.");
                        $match = false;
                        break 2; // Keluar dari switch DAN loop filterParams
                }
            } else {
                // Logika umum untuk filter string (SKU, Material Description, CHANNEL)
                $columnValue = strtolower(trim((string)$row[$columnId])); // Konversi nilai kolom ke lowercase string
                $filterValueLower = strtolower($filterValue); // Konversi nilai filter ke lowercase

                // Periksa apakah nilai kolom mengandung nilai filter
                if (strpos($columnValue, $filterValueLower) === false) {
                    $match = false;
                    error_log("String mismatch for '{$columnId}'. Row value: '{$columnValue}', Filter value: '{$filterValueLower}'");
                    break; // Keluar dari loop filterParams untuk baris ini
                }
            }

            // Jika sudah tidak match dengan salah satu filter, tidak perlu cek filter lain
            if (!$match) {
                break;
            }
        }
        if ($match) {
            $filteredData[] = $row; // Tambahkan baris jika cocok dengan semua filter
        }
    }
    $finalData = $filteredData; // Perbarui $finalData dengan hasil filter
    error_log("Filters applied. Final data count: " . count($finalData));
} else {
    error_log("No active filters detected. Returning all initial data. Count: " . count($finalData));
}
// --- END FILTER LOGIC ---

echo json_encode(['status' => 'success', 'data' => $finalData]);
error_log("--- END get_stock_data.php ---"); // Debug: Penanda akhir eksekusi script
?>