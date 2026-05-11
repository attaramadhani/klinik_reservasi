<?php
session_start();
include '../koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: dokter.php");
    exit;
}

// Ambil ID User dari URL
$id_user = mysqli_real_escape_string($conn, $_GET['id']);

// 1. Ambil id_dokter berdasarkan id_user
$get_dr = mysqli_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
$dr = mysqli_fetch_assoc($get_dr);

if ($dr) {
    $id_dr = $dr['id_dokter'];

    // 2. HAPUS BERANTAI (Urutan dari yang terdalam ke induk)
    
    // A. Hapus Reservasi Pasien yang terkait dengan jadwal dokter ini
    // Kita harus menghapus ini karena reservasi nyambung ke jadwal_dokter
    mysqli_query($conn, "DELETE FROM reservasi WHERE id_jadwal IN (SELECT id_jadwal FROM jadwal_dokter WHERE id_dokter = '$id_dr')");
    
    // B. Hapus Jadwal Dokter
    mysqli_query($conn, "DELETE FROM jadwal_dokter WHERE id_dokter = '$id_dr'");
    
    // C. Hapus Profil Dokter
    mysqli_query($conn, "DELETE FROM dokter WHERE id_dokter = '$id_dr'");
}

// 3. Terakhir, hapus Akun di tabel Users (Induk Utama)
$hapus_user = mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_user'");

if ($hapus_user) {
    echo "<html><body><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon: 'success', title: 'Berhasil', text: 'Data Dokter, Jadwal, dan Riwayat Reservasi berhasil dihapus secara permanen!'}).then(() => { window.location='dokter.php'; });</script></body></html>";
} else {
    echo "Gagal menghapus akun: " . mysqli_error($conn);
}
?>