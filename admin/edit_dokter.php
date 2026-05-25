<?php
session_start();
include '../koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 1. Ambil data lama berdasarkan ID di URL
if (!isset($_GET['id'])) { header("Location: dokter.php"); exit; }

$id_dokter = db_real_escape_string($conn, $_GET['id']);
$query = "SELECT d.*, u.username, u.email FROM dokter d 
          JOIN users u ON d.id_user = u.id_user 
          WHERE d.id_dokter = '$id_dokter'";
$result = db_query($conn, $query);
$data = db_fetch_assoc($result);

// Jika ID tidak ditemukan
if (!$data) { echo "Data tidak ditemukan!"; exit; }

// 2. Proses Update saat tombol ditekan
if (isset($_POST['update'])) {
    $nama = db_real_escape_string($conn, $_POST['nama']);
    $spesialisasi = db_real_escape_string($conn, $_POST['spesialisasi']);
    $username = db_real_escape_string($conn, $_POST['username']);
    $email = db_real_escape_string($conn, $_POST['email']);
    $id_user = $data['id_user'];

    // Update tabel Dokter
    $u1 = db_query($conn, "UPDATE dokter SET nama_dokter='$nama', spesialisasi='$spesialisasi' WHERE id_dokter='$id_dokter'");
    
    // Update tabel Users (Username & Email)
    $u2 = db_query($conn, "UPDATE users SET username='$username', email='$email' WHERE id_user='$id_user'");

    // Logika Password: Hanya ganti jika diisi
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        db_query($conn, "UPDATE users SET password='$password' WHERE id_user='$id_user'");
    }

    if ($u1 && $u2) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'success', title: 'Berhasil', text: 'Data Dokter Berhasil Diperbarui!'}).then(() => { window.location='dokter.php'; }); });</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dokter - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; }
        .edit-card { border: none; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); background: #fff; }
        .form-control { border-radius: 12px; border: 1px solid #e0e0e0; padding: 12px 15px; transition: 0.3s; }
        .form-control:focus { border-color: #76c720; box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1); }
        .label-custom { font-size: 11px; font-weight: 800; color: #0f3d2e; letter-spacing: 1px; margin-bottom: 8px; }
        .btn-update { background: #0f3d2e; color: white; border-radius: 15px; padding: 15px; font-weight: 700; border: none; transition: 0.3s; }
        .btn-update:hover { background: #1a5c43; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(15, 61, 46, 0.2); }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card edit-card p-4 p-md-5">
                    <div class="d-flex align-items-center mb-4">
                        <a href="dokter.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-chevron-left"></i></a>
                        <div>
                            <h4 class="fw-800 mb-0">Edit Profil</h4>
                            <p class="text-muted small mb-0">Perbarui data tenaga medis</p>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="label-custom uppercase d-block">NAMA LENGKAP</label>
                            <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($data['nama_dokter']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="label-custom uppercase d-block">SPESIALISASI</label>
                            <input type="text" name="spesialisasi" class="form-control" value="<?php echo htmlspecialchars($data['spesialisasi']); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="label-custom uppercase d-block">EMAIL AKTIF</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($data['email']); ?>" required>
                        </div>

                        <div class="p-3 rounded-4 mb-4" style="background: #f8f9fa; border: 1px dashed #ddd;">
                            <p class="small fw-bold mb-3 text-muted"><i class="fas fa-lock me-2"></i>KREDENSIAL LOGIN</p>
                            <div class="mb-3">
                                <label class="label-custom small">USERNAME</label>
                                <input type="text" name="username" class="form-control bg-white" value="<?php echo htmlspecialchars($data['username']); ?>" required>
                            </div>
                            <div class="mb-0">
                                <label class="label-custom small">PASSWORD BARU</label>
                                <input type="password" name="password" class="form-control bg-white" placeholder="••••••••">
                                <small class="text-muted" style="font-size: 10px;">*Kosongkan jika tidak ingin mengganti password</small>
                            </div>
                        </div>
                        
                        <button type="submit" name="update" class="btn btn-update w-100 shadow-sm">
                            KONFIRMASI PERUBAHAN
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>