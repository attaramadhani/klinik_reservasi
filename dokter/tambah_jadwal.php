<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$q_dokter = db_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
$id_dokter = db_fetch_assoc($q_dokter)['id_dokter'];

if (isset($_POST['simpan'])) {
    $hari = db_real_escape_string($conn, $_POST['hari']);
    $mulai = db_real_escape_string($conn, $_POST['jam_mulai']);
    $selesai = db_real_escape_string($conn, $_POST['jam_selesai']);
    $kuota = (int) $_POST['kuota']; 

    // Simpan jadwal langsung menggunakan ID Dokter yang sedang login
    $query = "INSERT INTO jadwal_dokter (id_dokter, hari, jam_mulai, jam_selesai, kuota) 
              VALUES ('$id_dokter', '$hari', '$mulai', '$selesai', '$kuota')";
    
    if(db_query($conn, $query)) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'success', title: 'Berhasil', text: 'Jadwal Berhasil Ditambahkan!'}).then(() => { window.location='jadwal.php'; }); });</script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal menyimpan: " . db_error($conn) . "'}); });</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Jadwal - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Plus Jakarta Sans', sans-serif; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e0e0e0; transition: 0.3s; padding: 12px 15px;}
        .form-control:focus, .form-select:focus { border-color: #76c720; box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1); }
        .label-custom { font-size: 11px; font-weight: 800; color: #0f3d2e; letter-spacing: 1px; margin-bottom: 8px; }
    </style>
</head>
<body class="py-5">
    <div class="container">
        <div class="card border-0 shadow-sm mx-auto p-4 p-md-5" style="max-width: 550px; border-radius: 24px;">
            <div class="text-center mb-4">
                <h4 class="fw-800" style="color: #0f3d2e;">Atur Jadwal Baru</h4>
                <p class="text-muted small">Tentukan jam praktik dan batas maksimal pasien Anda.</p>
            </div>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="label-custom">HARI PRAKTEK</label>
                        <select name="hari" class="form-select" required>
                            <option value="Senin">Senin</option><option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option><option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="label-custom">BATAS KUOTA PASIEN</label>
                        <input type="number" name="kuota" class="form-control" placeholder="Contoh: 15" min="1" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6 mb-4">
                        <label class="label-custom">JAM MULAI</label>
                        <input type="time" name="jam_mulai" class="form-control" required>
                    </div>
                    <div class="col-6 mb-4">
                        <label class="label-custom">JAM SELESAI</label>
                        <input type="time" name="jam_selesai" class="form-control" required>
                    </div>
                </div>

                <button type="submit" name="simpan" class="btn w-100 py-3 fw-bold rounded-3 shadow-sm text-white" style="background: #0f3d2e;">
                    SIMPAN JADWAL SAYA
                </button>
                <a href="jadwal.php" class="btn btn-light w-100 mt-2 text-muted fw-bold rounded-3 py-2">Batalkan</a>
            </form>
        </div>
    </div>
</body>
</html>