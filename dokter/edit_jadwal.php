<?php
session_start();
include '../koneksi.php';

// Proteksi akses Dokter
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

// Ambil ID Dokter yang sedang login
$id_user = $_SESSION['id_user'];
$q_dokter = db_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
$id_dokter = db_fetch_assoc($q_dokter)['id_dokter'];

// Cek apakah ada parameter ID di URL
if (!isset($_GET['id'])) {
    header("Location: jadwal.php");
    exit;
}

$id_jadwal = db_real_escape_string($conn, $_GET['id']);

// Ambil data jadwal HANYA JIKA milik dokter yang sedang login
$query_data = "SELECT * FROM jadwal_dokter WHERE id_jadwal = '$id_jadwal' AND id_dokter = '$id_dokter'";
$result_data = db_query($conn, $query_data);
$data = db_fetch_assoc($result_data);

// Jika jadwal tidak ditemukan atau bukan milik dokter ini
if (!$data) {
    echo "<html><body><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon: 'error', title: 'Gagal', text: 'Data jadwal tidak ditemukan atau Anda tidak memiliki akses!'}).then(() => { window.location='jadwal.php'; });</script></body></html>";
    exit;
}

// Proses Update Data
if (isset($_POST['update'])) {
    $hari = db_real_escape_string($conn, $_POST['hari']);
    $mulai = db_real_escape_string($conn, $_POST['jam_mulai']);
    $selesai = db_real_escape_string($conn, $_POST['jam_selesai']);
    $kuota = (int) $_POST['kuota']; 

    $query_update = "UPDATE jadwal_dokter 
                     SET hari = '$hari', jam_mulai = '$mulai', jam_selesai = '$selesai', kuota = '$kuota' 
                     WHERE id_jadwal = '$id_jadwal' AND id_dokter = '$id_dokter'";
    
    if(db_query($conn, $query_update)) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'success', title: 'Berhasil', text: 'Jadwal Berhasil Diperbarui!'}).then(() => { window.location='jadwal.php'; }); });</script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal memperbarui jadwal: " . db_error($conn) . "'}); });</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Jadwal - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e0e0e0; transition: 0.3s; padding: 12px 15px;}
        .form-control:focus, .form-select:focus { border-color: #76c720; box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1); }
        .label-custom { font-size: 11px; font-weight: 800; color: #0f3d2e; letter-spacing: 1px; margin-bottom: 8px; }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="card border-0 shadow-sm mx-auto p-4 p-md-5" style="max-width: 550px; border-radius: 24px;">
            <div class="d-flex align-items-center mb-4">
                <a href="jadwal.php" class="btn btn-light rounded-circle me-3"><i class="fas fa-chevron-left"></i></a>
                <div>
                    <h4 class="fw-800 mb-0" style="color: #0f3d2e;">Edit Jadwal Praktik</h4>
                    <p class="text-muted small mb-0">Perbarui jam operasional atau batas kuota.</p>
                </div>
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="label-custom">HARI PRAKTEK</label>
                        <select name="hari" class="form-select" required>
                            <?php 
                            $hari_pilihan = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                            foreach ($hari_pilihan as $h) {
                                $selected = ($data['hari'] == $h) ? 'selected' : '';
                                echo "<option value='$h' $selected>$h</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="label-custom">BATAS KUOTA PASIEN</label>
                        <input type="number" name="kuota" class="form-control" value="<?php echo htmlspecialchars($data['kuota']); ?>" min="1" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 mb-4">
                        <label class="label-custom">JAM MULAI</label>
                        <input type="time" name="jam_mulai" class="form-control" value="<?php echo htmlspecialchars($data['jam_mulai']); ?>" required>
                    </div>
                    <div class="col-6 mb-4">
                        <label class="label-custom">JAM SELESAI</label>
                        <input type="time" name="jam_selesai" class="form-control" value="<?php echo htmlspecialchars($data['jam_selesai']); ?>" required>
                    </div>
                </div>

                <button type="submit" name="update" class="btn w-100 py-3 fw-bold rounded-3 shadow-sm text-white" style="background: #0f3d2e;">
                    SIMPAN PERUBAHAN
                </button>
            </form>
        </div>
    </div>
</body>
</html>