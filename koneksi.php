<?php
require_once __DIR__ . '/db.php';

// Melakukan koneksi ke database
$conn = db_connect_from_env();

// Cek jika koneksi gagal
if (!$conn) {
    die("Koneksi database gagal. Pastikan konfigurasi database sudah benar.");
}

// Memulai session di sini agar tidak perlu menulis session_start() di setiap halaman nanti
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
