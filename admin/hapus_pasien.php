<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

if(isset($_GET['nik'])) {
    $nik = mysqli_real_escape_string($conn, $_GET['nik']);
    
    // Ambil id_user dulu untuk dihapus (karena FK constraint / user account)
    $q = mysqli_query($conn, "SELECT id_user FROM pasien WHERE nik = '$nik'");
    if(mysqli_num_rows($q) > 0) {
        $r = mysqli_fetch_assoc($q);
        $id_user = $r['id_user'];
        
        mysqli_query($conn, "DELETE FROM pasien WHERE nik = '$nik'");
        mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_user'");
    }
}
echo "<script>alert('Pasien berhasil dihapus permanen bedeserta seluruhan data riwayatnya!'); window.location.href='pasien.php';</script>";
?>
