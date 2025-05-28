<?php
session_start(); // Mulai sesi

// Hapus semua variabel sesi
$_SESSION = array();

// Jika menggunakan cookie sesi, hapus juga cookie-nya.
// Perhatikan: Ini akan menghancurkan sesi, dan bukan hanya data sesi!
// Ini akan mengharuskan pengguna untuk login kembali jika sesi digunakan untuk autentikasi.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Terakhir, hancurkan sesi.
session_destroy();

// Kirim respons JSON
echo json_encode(['status' => 'success', 'message' => 'Semua data sesi telah dihapus.']);
exit;
?>