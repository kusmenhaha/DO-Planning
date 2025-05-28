<?php
session_start();
error_log("Data POST di delete_cust.php: " . print_r($_POST, true));
$selectedCustHashes = json_decode($_POST['selected_cust_delete'] ?? '[]', true);
error_log("Data selectedCustHashes di delete_cust.php (setelah decode): " . print_r($selectedCustHashes, true));

// ... kode koneksi database dan penghapusan ...
?>