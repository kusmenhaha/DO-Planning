<?php
// services/database.php

$servername = "localhost";
$username = "root";
$password = "Khusnan02"; // Sesuaikan dengan password database Anda
$dbname = "larkon";

// Membuat koneksi
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Memeriksa koneksi (error ditangani di script yang meng-include ini)
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>