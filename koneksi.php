<?php
$host = "127.0.0.1";
$user = "root";
$pass = ""; // Kosongkan jika menggunakan XAMPP default
$db   = "klinik_reservasi"; // Pastikan nama ini SAMA PERSIS dengan database yang baru dibuat

// Melakukan koneksi ke database
$conn = mysqli_connect($host, $user, $pass, $db, 3307);

// Cek jika koneksi gagal
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// Memulai session di sini agar tidak perlu menulis session_start() di setiap halaman nanti
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>