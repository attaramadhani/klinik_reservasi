<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

if(isset($_GET['nik'])) {
    $nik = db_real_escape_string($conn, $_GET['nik']);
    
    // Ambil id_user dulu untuk dihapus (karena FK constraint / user account)
    $q = db_query($conn, "SELECT id_user FROM pasien WHERE nik = '$nik'");
    if(db_num_rows($q) > 0) {
        $r = db_fetch_assoc($q);
        $id_user = $r['id_user'];
        
        db_query($conn, "DELETE FROM pasien WHERE nik = '$nik'");
        db_query($conn, "DELETE FROM users WHERE id_user = '$id_user'");
    }
}
echo "<html><body><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon: 'success', title: 'Berhasil', text: 'Pasien berhasil dihapus secara permanen beserta seluruh data riwayatnya!'}).then(() => { window.location.href='pasien.php'; });</script></body></html>";
?>
