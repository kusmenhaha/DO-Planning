<?php
require 'vendor/autoload.php'; // Pastikan ini jalan, Composer sudah di-setup
use PhpOffice\PhpSpreadsheet\IOFactory;

// Konfigurasi database
$host = 'localhost';
$user = 'root';         // ganti jika bukan root
$password = 'Khusnan02';         // ganti jika ada password
$database = 'larkon';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die('<div class="alert alert-danger">Koneksi database gagal: ' . $conn->connect_error . '</div>');
}

if (isset($_POST['delete_all'])) {
    // echo "Delete button was clicked!"; // Tambahkan ini untuk memastikan form dikirim
    $sql = "DELETE FROM mixcust";
    if ($conn->query($sql) === TRUE) {
        echo '<div class="alert alert-success">Semua data berhasil dihapus.</div>';
    } else {
        echo '<div class="alert alert-danger">Terjadi kesalahan saat menghapus data: ' . $conn->error . '</div>';
    }
} else {
    echo "No POST data received"; // Debug untuk memastikan form tidak terkirim
}
?>
