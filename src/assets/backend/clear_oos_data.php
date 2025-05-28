<?php
session_start(); // <<< PENTING: Tambahkan ini!

// include '../../services/database.php'; // Ini mungkin tidak diperlukan hanya untuk menghapus sesi, kecuali Anda punya logika DB terkait reset

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'Permintaan tidak valid.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cukup satu kali unset
    if (isset($_SESSION['oos_data'])) {
        unset($_SESSION['oos_data']);
        $response['status'] = 'success';
        $response['message'] = 'Data OOS berhasil dihapus dari session.';
    } else {
        // Jika tidak ada data di sesi, tetap anggap sukses reset karena tujuannya tercapai (sesi kosong)
        $response['status'] = 'success';
        $response['message'] = 'Tidak ada data OOS di sesi untuk direset.';
    }
} else {
    $response['message'] = 'Metode permintaan tidak didukung untuk operasi ini. Harap gunakan POST.';
}

echo json_encode($response);

?>