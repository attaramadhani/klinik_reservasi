<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_jadwal = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Ambil ID dokter yang sedang login
    $id_user = $_SESSION['id_user'];
    $q_dokter = mysqli_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
    $id_dokter = mysqli_fetch_assoc($q_dokter)['id_dokter'];

    // Hapus HANYA JIKA id_jadwal tersebut milik dokter ini
    $query = "DELETE FROM jadwal_dokter WHERE id_jadwal = '$id_jadwal' AND id_dokter = '$id_dokter'";
    
    if(mysqli_query($conn, $query)) {
        echo "<html><body><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon: 'success', title: 'Berhasil', text: 'Jadwal berhasil dihapus!'}).then(() => { window.location='jadwal.php'; });</script></body></html>";
    } else {
        echo "<html><body><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal menghapus jadwal!'}).then(() => { window.location='jadwal.php'; });</script></body></html>";
    }
} else {
    header("Location: jadwal.php");
}
?>