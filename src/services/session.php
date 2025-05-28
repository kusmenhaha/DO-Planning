<?php
session_start(); // ⬅️ WAJIB DIPANGGIL sebelum pakai $_SESSION

require_once realpath(__DIR__ . '/../services/database.php');

// Cek apakah form sudah disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $storedHash = $data['password'];
            $role = $data['role'];
            $status = $data['status'];

            if (password_verify($password, $storedHash)) {
                if ($status == 'Active') {
                    $_SESSION['email'] = $email;
                    $_SESSION['role'] = $role;

                    if ($role == "Admin") {
                        header("Location: index-admin.php");
                    } else if ($role == "stafops") {
                        header("Location: index.php");
                    } else {
                        header("Location: login.php");
                    }
                    exit();
                } else {
                    $_SESSION['error'] = "Akun Anda tidak aktif.";
                }
            } else {
                $_SESSION['error'] = "Email atau password salah.";
            }
        } else {
            $_SESSION['error'] = "Email atau password salah.";
        }

        header("Location: auth-signin.php");
        exit();
    } 
}