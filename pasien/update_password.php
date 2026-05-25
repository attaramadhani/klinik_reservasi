<?php
session_start();
// Tetap gunakan ../ karena koneksi.php ada di luar folder pasien
include '../koneksi.php';

if (isset($_POST['old_pass'])) {
    $id_user  = $_SESSION['id_user'];
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];

    // 1. Ambil password lama dari database
    $query = db_query($conn, "SELECT password FROM users WHERE id_user = '$id_user'");
    $data  = db_fetch_assoc($query);

    // 2. Verifikasi password lama
    if (password_verify($old_pass, $data['password'])) {
        // 3. Hash password baru dan update
        $hash_baru = password_hash($new_pass, PASSWORD_DEFAULT);
        $update = db_query($conn, "UPDATE users SET password = '$hash_baru' WHERE id_user = '$id_user'");

        if ($update) {
            echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>
                document.addEventListener('DOMContentLoaded', function(){ 
                    Swal.fire({icon: 'success', title: 'Berhasil', text: 'Password berhasil diganti!', confirmButtonColor: '#76c720'}).then(() => { window.location='profil.php'; }); 
                });
            </script></body></html>";
        } else {
            echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>
                document.addEventListener('DOMContentLoaded', function(){ 
                    Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal mengupdate password.', confirmButtonColor: '#e74c3c'}).then(() => { window.location='profil.php'; }); 
                });
            </script></body></html>";
        }
    } else {
        echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>
            document.addEventListener('DOMContentLoaded', function(){ 
                Swal.fire({icon: 'error', title: 'Gagal', text: 'Password lama salah!', confirmButtonColor: '#e74c3c'}).then(() => { window.location='profil.php'; }); 
            });
        </script></body></html>";
    }
}
?>