<?php
$host = 'localhost';
$user = 'root';
$password = 'Khusnan02';
$database = 'larkon';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data dari form
$nama = $_POST['nama_lengkap'];
$email = $_POST['email'];
$password = $_POST['password'];
$role = $_POST['role'];
$workplace = $_POST['workplace'];

// Hash password sebelum disimpan
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Menangani status, jika tidak ada status yang dipilih, set default ke "Inactive"
$status = isset($_POST['status']) ? $_POST['status'] : 'Inactive';

// Generate NIK acak (8 digit angka)
$nik = rand(10000000, 99999999);

// Query untuk menyimpan data ke database, sekarang termasuk kolom `nik`
$sql = "INSERT INTO users ( nama_lengkap, email, password, role, workplace, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssss", $nik, $nama, $email, $hashed_password, $role, $workplace, $status);

if ($stmt->execute()) {
    echo "Data berhasil disimpan.";
    header("Location: role-edit.php"); // Redirect setelah berhasil
    exit;
} else {
    echo "Gagal menyimpan data: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
