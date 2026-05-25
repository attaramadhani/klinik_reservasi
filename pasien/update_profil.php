<?php
session_start();
include '../koneksi.php'; 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = $_SESSION['id_user'];
    
    // Pastikan variabel bersih dari karakter sampah (copy-paste error)
    $email = db_real_escape_string($conn, $_POST['email']);
    $no_hp = db_real_escape_string($conn, $_POST['no_hp']);
    $jenis_kelamin = db_real_escape_string($conn, $_POST['jenis_kelamin']);
    $tanggal_lahir = db_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat = db_real_escape_string($conn, $_POST['alamat']);

    // 1. Update tabel users (untuk email login)
    $q1 = db_query($conn, "UPDATE users SET email = '$email' WHERE id_user = '$id_user'");
    
    // 2. Update tabel pasien (untuk data profil)
    $q2 = db_query($conn, "UPDATE pasien SET email = '$email', no_hp = '$no_hp', jenis_kelamin = '$jenis_kelamin', tanggal_lahir = '$tanggal_lahir', alamat = '$alamat' WHERE id_user = '$id_user'");

    if ($q1 && $q2) {
        echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>
            document.addEventListener('DOMContentLoaded', function(){ 
                Swal.fire({icon: 'success', title: 'Berhasil', text: 'Profil berhasil diperbarui!', confirmButtonColor: '#76c720'}).then(() => { window.location='profil.php'; }); 
            });
        </script></body></html>";
    } else {
        // Tampilkan error database jika gagal agar mudah didebug
        echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>
            document.addEventListener('DOMContentLoaded', function(){ 
                Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal update database: " . db_error($conn) . "', confirmButtonColor: '#e74c3c'}).then(() => { window.location='profil.php'; }); 
            });
        </script></body></html>";
    }
} else {
    // Jika diakses tanpa POST, kembalikan ke profil
    header("Location: profil.php");
    exit;
}
?>