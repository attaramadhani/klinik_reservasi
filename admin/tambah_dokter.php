<?php
session_start();
include '../koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['submit'])) {
    $nama = db_real_escape_string($conn, $_POST['nama']);
    $spesialisasi = db_real_escape_string($conn, $_POST['spesialisasi']);
    $email = db_real_escape_string($conn, $_POST['email']); // Menambah variabel email
    $username = db_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 

    // 1. Simpan ke tabel USERS (Termasuk Email)
    $q1 = db_query($conn, "INSERT INTO users (username, password, email, role) VALUES ('$username', '$password', '$email', 'dokter')");
    
    if ($q1) {
        $id_user = db_insert_id($conn);
        // 2. Simpan ke tabel DOKTER
        db_query($conn, "INSERT INTO dokter (id_user, nama_dokter, spesialisasi) VALUES ('$id_user', '$nama', '$spesialisasi')");
        
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'success', title: 'Berhasil', text: 'Dokter berhasil didaftarkan!'}).then(() => { window.location='dokter.php'; }); });</script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal mendaftarkan dokter: " . db_error($conn) . "'}); });</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Dokter - Cliniq Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; }
        .form-card { border: none; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); }
        .form-control { border-radius: 12px; padding: 12px 15px; border: 1px solid #e0e0e0; }
        .form-control:focus { border-color: #76c720; box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1); }
        .label-title { font-size: 11px; font-weight: 800; color: #0f3d2e; letter-spacing: 1px; margin-bottom: 8px; text-transform: uppercase; }
        .btn-save { background: #0f3d2e; color: white; border-radius: 15px; padding: 15px; font-weight: 700; border: none; transition: 0.3s; }
        .btn-save:hover { background: #1a5c43; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(15, 61, 46, 0.2); }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card form-card p-4 p-md-5">
                    <div class="d-flex align-items-center mb-4">
                        <a href="dokter.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-chevron-left"></i></a>
                        <div>
                            <h4 class="fw-800 mb-0">Dokter Baru</h4>
                            <p class="text-muted small mb-0">Daftarkan tenaga medis baru</p>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="label-title">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" placeholder="dr. Nama Lengkap, Sp.X" required>
                        </div>
                        <div class="mb-3">
                            <label class="label-title">Spesialisasi</label>
                            <input type="text" name="spesialisasi" class="form-control" placeholder="Contoh: Dokter Umum / Anak" required>
                        </div>
                        <div class="mb-3">
                            <label class="label-title">Email Aktif</label>
                            <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
                        </div>

                        <div class="p-3 rounded-4 mb-4" style="background: #f8f9fa; border: 1px dashed #ddd;">
                            <p class="small fw-bold mb-3 text-muted"><i class="fas fa-key me-2"></i>AKSES LOGIN</p>
                            <div class="mb-3">
                                <label class="label-title small">Username</label>
                                <input type="text" name="username" class="form-control bg-white" placeholder="username_dokter" required>
                            </div>
                            <div class="mb-0">
                                <label class="label-title small">Password</label>
                                <input type="password" name="password" class="form-control bg-white" placeholder="••••••••" required>
                            </div>
                        </div>

                        <button type="submit" name="submit" class="btn btn-save w-100 shadow-sm">
                            DAFTARKAN DOKTER
                        </button>
                        <a href="dokter.php" class="btn btn-link w-100 mt-3 text-decoration-none text-muted fw-bold small">Batalkan pendaftaran</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>