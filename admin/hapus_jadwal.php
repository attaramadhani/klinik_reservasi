<?php
session_start();
include '../koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_jadwal = mysqli_real_escape_string($conn, $_GET['id']);

    // 1. Hapus dulu data di tabel pembayaran yang terhubung ke reservasi pada jadwal ini
    // Ini penting karena tabel pembayaran merujuk ke id_reservasi
    mysqli_query($conn, "DELETE FROM pembayaran WHERE id_reservasi IN (SELECT id_reservasi FROM reservasi WHERE id_jadwal = '$id_jadwal')");

    // 2. Hapus data di tabel reservasi yang menggunakan jadwal ini
    mysqli_query($conn, "DELETE FROM reservasi WHERE id_jadwal = '$id_jadwal'");

    // 3. Terakhir, hapus jadwalnya
    $query = "DELETE FROM jadwal_dokter WHERE id_jadwal = '$id_jadwal'";
    
    if (mysqli_query($conn, $query)) {
        echo "<html><body><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon: 'success', title: 'Berhasil', text: 'Jadwal dan data reservasi terkait berhasil dihapus!'}).then(() => { window.location='jadwal.php'; });</script></body></html>";
    } else {
        // Tampilkan error jika masih gagal untuk debugging
        echo "Error: " . mysqli_error($conn);
    }
} else {
    header("Location: jadwal.php");
}
?>