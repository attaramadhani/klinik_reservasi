<?php
session_start();
include '../koneksi.php';

// Proteksi akses Dokter
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
// Ambil ID Dokter
$q_dokter = mysqli_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
$dokter = mysqli_fetch_assoc($q_dokter);
$id_dokter = $dokter['id_dokter'];

// Proses Konfirmasi (Terima/Tolak)
if (isset($_GET['aksi']) && isset($_GET['id_res'])) {
    $id_res = mysqli_real_escape_string($conn, $_GET['id_res']);
    
    // Perbaikan baris 20: Pastikan sintaks if/else benar
    if ($_GET['aksi'] == 'terima') {
        $status_baru = 'Dikonfirmasi';
    } else {
        $status_baru = 'Ditolak';
    }

    $update = mysqli_query($conn, "UPDATE reservasi SET status = '$status_baru' WHERE id_reservasi = '$id_res'");
    
    if ($update) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'success', title: 'Berhasil', text: 'Reservasi berhasil diperbarui menjadi " . $status_baru . "'}).then(() => { window.location='reservasi.php'; }); });</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Konfirmasi Antrian - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #0f3d2e; --accent-green: #76c720; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }

        .main-content { margin-left: 260px; padding: 40px; }
        .card-res { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); overflow: hidden; }
        .table thead { background: var(--primary-green); color: white; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2 class="fw-800 mb-4" style="color: var(--primary-green);">Konfirmasi Reservasi Pasien</h2>
    
    <div class="card card-res">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">No. Antrian</th>
                        <th>Nama Pasien</th>
                        <th>Jadwal / Hari</th>
                        <th>Keluhan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q_res = mysqli_query($conn, "SELECT r.*, p.nama_lengkap, j.hari, j.jam_mulai 
                                                  FROM reservasi r 
                                                  JOIN pasien p ON r.nik = p.nik 
                                                  JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal 
                                                  WHERE j.id_dokter = '$id_dokter' AND r.status = 'Menunggu' 
                                                  ORDER BY r.id_reservasi ASC");

                    if(mysqli_num_rows($q_res) == 0) {
                        echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Tidak ada reservasi baru yang menunggu konfirmasi.</td></tr>";
                    }

                    while($r = mysqli_fetch_assoc($q_res)):
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary">#<?php echo $r['no_antrian']; ?></td>
                        <td><div class="fw-bold"><?php echo htmlspecialchars($r['nama_lengkap']); ?></div></td>
                        <td><?php echo $r['hari']; ?>, <?php echo date('H:i', strtotime($r['jam_mulai'])); ?> WIB</td>
                        <td><small class="text-muted"><?php echo htmlspecialchars($r['keluhan']); ?></small></td>
                        <td class="text-center">
                            <a href="?aksi=terima&id_res=<?php echo $r['id_reservasi']; ?>" class="btn btn-success btn-sm rounded-pill px-3 me-1">
                                <i class="fas fa-check me-1"></i> Terima
                            </a>
                            <a href="?aksi=tolak&id_res=<?php echo $r['id_reservasi']; ?>" class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="return confirm('Tolak pasien ini?')">
                                <i class="fas fa-times me-1"></i> Tolak
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>