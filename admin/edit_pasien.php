<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}

$nik = isset($_GET['nik']) ? mysqli_real_escape_string($conn, $_GET['nik']) : '';

if(isset($_POST['update_pasien'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $hp = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $jk = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $tgl_lahir = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    
    // Update data pasien (tanpa NIK, karena NIK permanen)
    mysqli_query($conn, "UPDATE pasien SET nama_lengkap='$nama', email='$email', no_hp='$hp', jenis_kelamin='$jk', tanggal_lahir='$tgl_lahir', alamat='$alamat' WHERE nik='$nik'");
    
    // Update data user terkait
    $q_usr = mysqli_query($conn, "SELECT id_user FROM pasien WHERE nik='$nik'");
    if(mysqli_num_rows($q_usr) > 0) {
        $id_u = mysqli_fetch_assoc($q_usr)['id_user'];
        mysqli_query($conn, "UPDATE users SET email='$email' WHERE id_user='$id_u'");
    }
    
    echo "<script>alert('Data pasien berhasil diperbarui!'); window.location.href='pasien.php';</script>";
    exit;
}

$q_pas = mysqli_query($conn, "SELECT * FROM pasien WHERE nik = '$nik'");
if(mysqli_num_rows($q_pas) == 0) {
    echo "<script>alert('Pasien tidak ditemukan!'); window.location.href='pasien.php';</script>";
    exit;
}
$pas = mysqli_fetch_assoc($q_pas);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Pasien - Cliniq Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0f3d2e; --accent: #76c720; --bg-light: #f8f9fa; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }
        .main-content { margin-left: 260px; padding: 40px; }
        .custom-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .form-control, .form-select { border-radius: 12px; padding: 12px 15px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex align-items-center mb-4">
        <a href="pasien.php" class="btn btn-light rounded-circle shadow-sm me-3" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-arrow-left text-dark"></i>
        </a>
        <div>
            <h3 class="fw-bold mb-0">Edit Data Pasien</h3>
            <p class="text-muted mb-0 small">Perbarui data informasi personal pasien terpilih</p>
        </div>
    </div>
    
    <div class="card custom-card p-4">
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small text-muted mb-1 text-uppercase">NIK / No. Identitas</label>
                    <input type="text" class="form-control fw-bold bg-light text-muted" value="<?php echo htmlspecialchars($pas['nik']); ?>" readonly>
                    <div class="small fw-semibold mt-1 opacity-75">
                        <span class="text-danger"><i class="fas fa-lock me-1"></i>NIK tidak dapat diubah setelah terdaftar.</span>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small text-muted mb-1 text-uppercase">Nama Lengkap Pasien</label>
                    <input type="text" name="nama" value="<?php echo htmlspecialchars($pas['nama_lengkap']); ?>" class="form-control" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small text-muted mb-1 text-uppercase">Alamat Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($pas['email']); ?>" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small text-muted mb-1 text-uppercase">Nomor Ponsel (WA)</label>
                    <input type="text" name="no_hp" value="<?php echo htmlspecialchars($pas['no_hp']); ?>" class="form-control" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small text-muted mb-1 text-uppercase">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select" required>
                        <option value="L" <?php echo $pas['jenis_kelamin']=='L'?'selected':''; ?>>Laki-laki</option>
                        <option value="P" <?php echo $pas['jenis_kelamin']=='P'?'selected':''; ?>>Perempuan</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small text-muted mb-1 text-uppercase">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" value="<?php echo $pas['tanggal_lahir']; ?>" class="form-control" required>
                </div>
                
                <div class="col-md-12 mb-4">
                    <label class="fw-bold small text-muted mb-1 text-uppercase">Alamat Lengkap</label>
                    <textarea name="alamat" class="form-control" rows="3" required><?php echo htmlspecialchars($pas['alamat']); ?></textarea>
                </div>
            </div>
            
            <hr class="opacity-25 pb-3">
            
            <div class="d-flex justify-content-end gap-2">
                <a href="pasien.php" class="btn btn-light px-4 fw-bold shadow-sm rounded-pill">Batal</a>
                <button type="submit" name="update_pasien" class="btn text-white px-5 fw-bold shadow-sm rounded-pill" style="background: var(--sidebar-bg);">
                    <i class="fas fa-save me-2"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
