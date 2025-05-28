<?php
// session_start(); // Pastikan ini TIDAK dikomentari
error_log("--- Starting refresh_all_backend.php ---"); // Tambahkan ini
error_log("Session ID in refresh_all_backend.php: " . session_id()); // Tambahkan ini

// require_once ("refresh_cmo_session.php");
// require_once ("get_stock_data.php");
// require_once ("update_cmo_data.php");
// require_once ("get_pick_data.php");
// require_once ("update_stock_qty.php");
// require_once ("hitungalokasi.php");
// require_once ("get_allocation_data.php");
require_once('../../cmo.php');
require_once('../../stock.php');
require_once('../../allocation_view.php');
require_once ("get_stock_data.php");
require_once ("hitungalokasi.php");
// require_once ("stock.php");
// require_once ("allocation-view.php");


error_log("--- Finished refresh_all_backend.php ---"); // Tambahkan ini
// Tambahkan output JSON agar frontend tahu sukses
header('Content-Type: application/json');
// echo json_encode(['status' => 'success', 'message' => 'Backend successfully refreshed']);
exit();
?>