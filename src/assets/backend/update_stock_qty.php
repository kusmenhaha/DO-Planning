<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => ''];

// Ambil data JSON dari request body
$input = file_get_contents('php://input');
$updates = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON received: ' . json_last_error_msg();
    error_log("JSON Decode Error in update_stock_qty.php: " . json_last_error_msg() . " Raw input: " . $input);
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_array($updates) && !empty($updates)) {
    if (!isset($_SESSION['stock_temp_data']) || !is_array($_SESSION['stock_temp_data'])) {
        $response['message'] = 'No temporary stock data found in session.';
        error_log("No stock_temp_data found in session for updates. Session data: " . json_encode($_SESSION));
        echo json_encode($response);
        exit;
    }

    $stockData = $_SESSION['stock_temp_data'];
    $updatedCount = 0;
    $errors = [];

    foreach ($updates as $skuToUpdate => $columnsToUpdate) {
        if (isset($columnsToUpdate['QTY'])) {
            $newQty = floatval($columnsToUpdate['QTY']);

            // Validasi: QTY tidak boleh negatif
            if ($newQty < 0) {
                $errors[] = "QTY untuk SKU {$skuToUpdate} tidak boleh negatif. Nilai: {$newQty}";
                error_log("Attempt to update QTY to negative value for SKU {$skuToUpdate}: {$newQty}");
                continue; // Lanjut ke SKU berikutnya
            }

            $found = false;
            foreach ($stockData as $index => $item) {
                if (isset($item['SKU']) && $item['SKU'] === $skuToUpdate) {
                    // Update QTY
                    $stockData[$index]['QTY'] = $newQty;

                    // Hitung ulang 'Sisa QTY' jika 'Planning' ada
                    if (isset($stockData[$index]['Planning'])) {
                        $planning = floatval($stockData[$index]['Planning']);
                        $stockData[$index]['Sisa QTY'] = $newQty - $planning;
                    } else {
                        $stockData[$index]['Sisa QTY'] = $newQty;
                    }
                    $updatedCount++;
                    $found = true;
                    break; // Stop loop setelah update
                }
            }
            if (!$found) {
                $errors[] = "SKU {$skuToUpdate} tidak ditemukan di data sesi.";
                error_log("SKU {$skuToUpdate} not found in session data for update.");
            }
        } else {
            $errors[] = "QTY tidak ditemukan dalam payload update untuk SKU {$skuToUpdate}.";
            error_log("QTY key missing in update payload for SKU {$skuToUpdate}.");
        }
    }

    $_SESSION['stock_temp_data'] = $stockData; // Simpan ulang data ke session

    if (empty($errors)) {
        $response['status'] = 'success';
        $response['message'] = "Berhasil memperbarui {$updatedCount} QTY. Data di sesi telah diperbarui.";
    } else {
        $response['status'] = 'partial_success';
        $response['message'] = "Memperbarui {$updatedCount} QTY dengan beberapa peringatan.";
        $response['errors'] = $errors;
    }

} else {
    $response['message'] = 'Metode request tidak valid atau data update kosong.';
    error_log("Invalid request method or empty update data in update_stock_qty.php. Method: " . $_SERVER['REQUEST_METHOD']);
}

echo json_encode($response);
exit;