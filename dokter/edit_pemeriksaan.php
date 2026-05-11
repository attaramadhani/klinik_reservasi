<?php
session_start();
include '../koneksi.php';

// Proteksi akses Dokter
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$q_dokter = mysqli_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
$id_dokter = mysqli_fetch_assoc($q_dokter)['id_dokter'];

// Redirect jika tidak ada ID Reservasi
if (!isset($_GET['id'])) {
    header("Location: riwayat_medis.php");
    exit;
}

$id_reservasi = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data hasil pemeriksaan yang lama beserta data pasiennya
$query_pemeriksaan = "SELECT h.*, r.no_antrian, p.nik, p.nama_lengkap, r.keluhan, j.id_dokter 
                      FROM hasil_pemeriksaan h
                      JOIN reservasi r ON h.id_reservasi = r.id_reservasi
                      JOIN pasien p ON r.nik = p.nik
                      JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal
                      WHERE h.id_reservasi = '$id_reservasi'";
$result_pemeriksaan = mysqli_query($conn, $query_pemeriksaan);
$data = mysqli_fetch_assoc($result_pemeriksaan);

if (!$data) {
    // Jika tidak ditemukan hasil pemeriksaannya
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>
            document.addEventListener('DOMContentLoaded', function(){ 
                Swal.fire({icon: 'error', title: 'Oops...', text: 'Data rekam medis tidak ditemukan!'}).then(() => { window.location='riwayat_medis.php'; }); 
            });
          </script></body></html>";
    exit;
}

// Pengecekan Hak Akses: Hanya dokter yang memeriksa (mengisi) yang boleh mengubah
if ($data['id_dokter'] != $id_dokter) {
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body><script>
            document.addEventListener('DOMContentLoaded', function(){ 
                Swal.fire({icon: 'warning', title: 'Akses Ditolak!', text: 'Anda hanya bisa mengubah data rekam medis pasien yang Anda periksa sendiri.'}).then(() => { window.location='riwayat_medis.php?nik={$data['nik']}'; }); 
            });
          </script></body></html>";
    exit;
}

// PROSES UPDATE HASIL PEMERIKSAAN
if (isset($_POST['update_pemeriksaan'])) {
    $tekanan_darah  = mysqli_real_escape_string($conn, $_POST['tekanan_darah']);
    $suhu_badan     = mysqli_real_escape_string($conn, $_POST['suhu_badan']);
    $berat_badan    = mysqli_real_escape_string($conn, $_POST['berat_badan']);
    $diagnosa       = mysqli_real_escape_string($conn, $_POST['diagnosa']);
    $alergi_obat    = mysqli_real_escape_string($conn, $_POST['alergi_obat']);
    $resep_obat     = mysqli_real_escape_string($conn, $_POST['resep_obat']);
    $tindakan       = mysqli_real_escape_string($conn, $_POST['tindakan']);
    $catatan_dokter = mysqli_real_escape_string($conn, $_POST['catatan_dokter']);

    // Update tabel hasil_pemeriksaan
    $q_update = "UPDATE hasil_pemeriksaan 
                 SET tekanan_darah = '$tekanan_darah',
                     suhu_badan = '$suhu_badan',
                     berat_badan = '$berat_badan',
                     diagnosa = '$diagnosa',
                     alergi_obat = '$alergi_obat',
                     resep_obat = '$resep_obat',
                     tindakan = '$tindakan',
                     catatan_dokter = '$catatan_dokter'
                 WHERE id_reservasi = '$id_reservasi'";
    
    if (mysqli_query($conn, $q_update)) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'success', title: 'Berhasil', text: 'Data Rekam Medis Berhasil Diubah!'}).then(() => { window.location='riwayat_medis.php?nik={$data['nik']}'; }); });</script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal mengubah: " . mysqli_error($conn) . "'}); });</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Rekam Medis - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #0f3d2e; --accent-green: #76c720; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .main-content { margin-left: 260px; padding: 30px; }
        
        .exam-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); max-width: 900px; margin: 0 auto;}
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e0e0e0; padding: 12px; }
        .form-control:focus { border-color: var(--accent-green); box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1); }
        .label-custom { font-size: 11px; font-weight: 800; color: var(--primary-green); letter-spacing: 1px; margin-bottom: 8px; text-transform: uppercase; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <div class="exam-card">
        <a href="riwayat_medis.php?nik=<?php echo $data['nik']; ?>" class="btn btn-outline-secondary btn-sm mb-4 rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Batal / Kembali</a>
        
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <h4 class="fw-800 mb-1" style="color: #e67e22;"><i class="fas fa-edit me-2"></i>Ubah Rekam Medis</h4>
                <span class="badge bg-secondary bg-opacity-10 text-dark">Pasien: <?php echo htmlspecialchars($data['nama_lengkap']); ?></span>
            </div>
            <div class="text-end">
                <small class="text-muted d-block">ID Reservasi</small>
                <h5 class="fw-bold mb-0 text-dark">#RES-<?php echo $data['id_reservasi']; ?></h5>
            </div>
        </div>

        <form method="POST">
            
            <div class="mb-4 p-3 rounded-3" style="background: #f8f9fa; border: 1px solid #e0e0e0;">
                <label class="label-custom"><i class="fas fa-comment-medical me-2"></i>Keluhan Utama (Dari Pasien)</label>
                <p class="mb-0 text-dark fw-medium"><?php echo htmlspecialchars($data['keluhan']); ?></p>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="label-custom">Tekanan Darah</label>
                    <input type="text" name="tekanan_darah" class="form-control" value="<?php echo htmlspecialchars($data['tekanan_darah']); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="label-custom">Suhu Badan</label>
                    <input type="text" name="suhu_badan" class="form-control" value="<?php echo htmlspecialchars($data['suhu_badan']); ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="label-custom">Berat Badan</label>
                    <input type="text" name="berat_badan" class="form-control" value="<?php echo htmlspecialchars($data['berat_badan']); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="label-custom">Diagnosa</label>
                    <input type="text" name="diagnosa" class="form-control" value="<?php echo htmlspecialchars($data['diagnosa']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="label-custom">Alergi Obat</label>
                    <input type="text" name="alergi_obat" class="form-control" value="<?php echo htmlspecialchars($data['alergi_obat']); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="label-custom">Tindakan Medis</label>
                <textarea name="tindakan" class="form-control" rows="2" required><?php echo htmlspecialchars($data['tindakan']); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="label-custom">Resep Obat</label>
                <textarea name="resep_obat" class="form-control" rows="3" required><?php echo htmlspecialchars($data['resep_obat']); ?></textarea>
            </div>

            <div class="mb-4">
                <label class="label-custom">Catatan Dokter / Tindak Lanjut</label>
                <textarea name="catatan_dokter" class="form-control" rows="2" required><?php echo htmlspecialchars($data['catatan_dokter']); ?></textarea>
            </div>

            <button type="submit" name="update_pemeriksaan" class="btn w-100 py-3 fw-bold rounded-3 shadow-sm text-white" style="background: #e67e22;">
                <i class="fas fa-save me-2"></i> SIMPAN PERUBAHAN
            </button>
        </form>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
