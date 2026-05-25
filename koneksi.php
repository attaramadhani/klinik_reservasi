<?php
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: "127.0.0.1";
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: "root";
$pass = getenv('DB_PASS') ?: getenv('MYSQLPASSWORD') ?: "";
$db   = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: "klinik_reservasi";
$port = (int) (getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: 3307);

// Melakukan koneksi ke database
$conn = mysqli_connect($host, $user, $pass, $db, $port);

// Cek jika koneksi gagal
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Memulai session di sini agar tidak perlu menulis session_start() di setiap halaman nanti
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
