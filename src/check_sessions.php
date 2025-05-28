<?php
session_start(); // Selalu mulai session untuk mengakses variabel session

// Tentukan apakah kita harus menampilkan detail array dari KUNCI spesifik
$showSpecificKey = isset($_GET['allocation_settings']) ? $_GET['show_key'] : null;

// Tentukan apakah kita harus menampilkan detail array dari SEMUA variabel session
// Ini adalah opsi yang sudah ada sebelumnya
$showAllArrayDetails = isset($_GET['show_array']) && $_GET['show_array'] === 'true';

// Atur header agar output terlihat sebagai teks biasa di browser
header('Content-Type: text/plain');

echo "Active Session Variables:\n";
echo "---------------------------\n";

if (isset($_SESSION) && !empty($_SESSION)) {
    if ($showSpecificKey) {
        // Jika ada kunci spesifik yang diminta
        if (isset($_SESSION[$showSpecificKey])) {
            echo "Key: " . $showSpecificKey . "\n";
            echo "Value: ";
            if (is_array($_SESSION[$showSpecificKey])) {
                echo print_r($_SESSION[$showSpecificKey], true);
            } else {
                echo $_SESSION[$showSpecificKey];
            }
            echo "\n---------------------------\n";
        } else {
            echo "Session key '" . $showSpecificKey . "' not found.\n";
        }
    } elseif ($showAllArrayDetails) {
        // Jika diminta menampilkan semua detail array (perilaku show_array=true sebelumnya)
        foreach ($_SESSION as $key => $value) {
            echo "Key: " . $key . "\n";
            echo "Value: ";
            if (is_array($value)) {
                echo print_r($value, true);
            } else {
                echo $value;
            }
            echo "\n---------------------------\n";
        }
    } else {
        // Default: Hanya menampilkan nama kunci jika tidak ada parameter khusus
        foreach ($_SESSION as $key => $value) {
            echo $key . "\n";
        }
    }
} else {
    echo "No active session variables found.\n";
}

?>