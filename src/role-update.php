<?php
include 'services/config.php'; // koneksi database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $nik = trim($_POST['nik']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $workplace = trim($_POST['workplace']);
    $status = trim($_POST['status']);

    // Validasi sederhana
    if (empty($nik) || empty($nama_lengkap) || empty($email) || empty($role) || empty($workplace) || empty($status)) {
        echo "Semua field harus diisi.";
        exit;
    }

    // Prepared statement update
    $sql = "UPDATE users SET nama_lengkap = ?, email = ?, role = ?, workplace = ?, status = ? WHERE nik = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "Prepare statement gagal: " . $conn->error;
        exit;
    }

    $stmt->bind_param("ssssss", $nama_lengkap, $email, $role, $workplace, $status, $nik);

    if ($stmt->execute()) {
        // Redirect atau tampil pesan sukses
        header("Location: role-edit.php?msg=update_success");
        exit;
    } else {
        echo "Update gagal: " . $stmt->error;
    }
} else {
    echo "Metode request tidak valid.";
}
?>
