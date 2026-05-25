<?php
session_start();
// PERBAIKAN: Keluar satu folder untuk mengambil koneksi database
include '../koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') { 
    header("Location: ../login.php"); 
    exit; 
}

$id_user = $_SESSION['id_user'];
// Query data user dengan JOIN untuk mendapatkan informasi lengkap
$u = db_fetch_assoc(db_query($conn, "SELECT u.*, p.* FROM users u JOIN pasien p ON u.id_user = p.id_user WHERE u.id_user = '$id_user'"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pasien - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #0f3d2e;
            --accent-green: #76c720;
            --bg-light: #f4f7f6;
        }

        body { 
            background: var(--bg-light); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: #2d3436;
        }

        /* Header Modern dengan Wave/Curve */
        .profile-header { 
            background: linear-gradient(135deg, var(--primary-green) 0%, #1a5c43 100%); 
            height: 220px; 
            position: relative; 
            border-radius: 0 0 50px 50px;
        }

        .btn-back { 
            position: absolute; 
            top: 30px; 
            left: 30px; 
            color: white; 
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            padding: 10px 20px;
            border-radius: 15px;
            text-decoration: none; 
            font-weight: 600; 
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-back:hover { 
            background: white;
            color: var(--primary-green);
        }

        .avatar-container {
            position: relative;
            margin-top: -80px;
            display: inline-block;
        }

        .avatar-box { 
            width: 140px; 
            height: 140px; 
            background: white; 
            border-radius: 40px; /* Squircle style */
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 60px; 
            color: var(--primary-green); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
            border: 6px solid white; 
            transform: rotate(-5deg);
            transition: 0.3s;
        }
        .avatar-container:hover .avatar-box {
            transform: rotate(0deg) scale(1.05);
        }

        /* Card Styling */
        .card-profile {
            border: none;
            border-radius: 30px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: 0.3s;
        }

        .form-label {
            font-weight: 700;
            font-size: 13px;
            color: var(--primary-green);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 15px;
            padding: 12px 20px;
            background: #f8f9fa;
            border: 1px solid #eee;
            font-size: 15px;
            transition: 0.3s;
        }

        .form-control:focus {
            background: white;
            border-color: var(--accent-green);
            box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1);
        }

        .btn-save {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 18px;
            font-weight: 800;
            letter-spacing: 0.5px;
            transition: 0.3s;
        }

        .btn-save:hover {
            background: var(--accent-green);
            color: var(--primary-green);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(118, 199, 32, 0.2);
        }

        .section-title {
            font-weight: 800;
            font-size: 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }

        .section-title i {
            width: 40px;
            height: 40px;
            background: #f0f9eb;
            color: var(--accent-green);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
    </style>
</head>
<body>

    <div class="profile-header">
        <a href="index.php" class="btn-back"><i class="fas fa-chevron-left me-2"></i> Kembali ke Dashboard</a>
    </div>

    <div class="container text-center mb-5">
        <div class="avatar-container">
            <div class="avatar-box"><i class="fas fa-user-ninja"></i></div>
        </div>
        <h2 class="fw-800 mt-4 mb-1"><?php echo htmlspecialchars($u['nama_lengkap']); ?></h2>
        <p class="text-muted fw-500">NIK: <span class="badge bg-light text-dark px-3 rounded-pill"><?php echo htmlspecialchars($u['nik']); ?></span></p>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <div class="card-profile p-5 mb-4">
                    <h5 class="section-title"><i class="fas fa-id-card"></i> Informasi Pribadi</h5>
                    <form action="update_profil.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Username Pribadi</label>
                                <div class="input-group">
                                    <span class="input-group-text border-0 bg-white ps-0"><i class="fas fa-at text-muted"></i></span>
                                    <input type="text" class="form-control border-0 ps-0 fw-bold" value="<?php echo htmlspecialchars($u['username']); ?>" readonly style="background: transparent;">
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Nomor WhatsApp Aktif</label>
                                <input type="text" name="no_hp" class="form-control" value="<?php echo htmlspecialchars($u['no_hp']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-control" required>
                                    <option value="L" <?php echo ($u['jenis_kelamin'] == 'L') ? 'selected' : ''; ?>>Laki-laki</option>
                                    <option value="P" <?php echo ($u['jenis_kelamin'] == 'P') ? 'selected' : ''; ?>>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label">Tanggal Lahir</label>
                                <input type="date" name="tanggal_lahir" class="form-control" value="<?php echo htmlspecialchars($u['tanggal_lahir']); ?>" required>
                            </div>
                            <div class="col-12 mb-4">
                                <label class="form-label">Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control" rows="2" required><?php echo htmlspecialchars($u['alamat']); ?></textarea>
                            </div>
                            <div class="col-12 mb-5">
                                <label class="form-label">Alamat Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($u['email']); ?>" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-save shadow-sm">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>

                <div class="card-profile p-5">
                    <h5 class="section-title" style="color: #e74c3c;"><i class="fas fa-shield-alt" style="background: #fdf2f2; color: #e74c3c;"></i>Keamanan Akun</h5>
                    <p class="text-muted small mb-4">Gunakan password yang kuat untuk menjaga privasi data medis Anda.</p>
                    
                    <form action="update_password.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label">Password Saat Ini</label>
                            <input type="password" name="old_pass" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_pass" class="form-control" placeholder="Buat password baru" required>
                        </div>
                        <button type="submit" class="btn btn-outline-danger px-5 py-3 rounded-4 fw-bold shadow-sm" style="border-width: 2px;">
                            <i class="fas fa-key me-2"></i>Update Password
                        </button>
                    </form>
                </div>

                <div class="text-center mt-5">
                    <p class="text-muted small">Terdaftar sebagai pasien Cliniq sejak <?php echo date('d M Y', strtotime($u['created_at'] ?? 'now')); ?></p>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>