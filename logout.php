<?php
// Memulai sesi untuk mengakses data sesi yang sedang aktif
session_start();

// Menghapus semua variabel sesi
$_SESSION = [];

// Menghapus sesi dari memori
session_unset();

// Menghancurkan sesi sepenuhnya
session_destroy();

// Mengarahkan pengguna kembali ke halaman login
header("Location: login.php");
exit;
?>