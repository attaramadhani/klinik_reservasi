<?php
session_start();
include '../koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// LOGIKA AKSI CEPAT (Konfirmasi / Tolak Antrian)
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_res = db_real_escape_string($conn, $_GET['id']);
    $aksi = $_GET['aksi'];

    if ($aksi == 'konfirmasi') {
        db_query($conn, "UPDATE reservasi SET status = 'Dikonfirmasi' WHERE id_reservasi = '$id_res'");
        $pesan_sukses = "Antrian berhasil dikonfirmasi!";
    } elseif ($aksi == 'tolak') {
        db_query($conn, "UPDATE reservasi SET status = 'Ditolak' WHERE id_reservasi = '$id_res'");
        $pesan_sukses = "Antrian telah ditolak.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Reservasi - Cliniq Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root { --sidebar-bg: #0f3d2e; --accent: #76c720; --bg-light: #f8f9fa; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* Content Area */
        .card-table { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .table th { font-size: 13px; color: #6c757d; text-transform: uppercase; letter-spacing: 1px; }
        .table td { vertical-align: middle; font-size: 15px; }
        .btn-action { width: 32px; height: 32px; padding: 0; line-height: 32px; text-align: center; border-radius: 8px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">Kelola Reservasi</h2>
            <p class="text-muted">Daftar seluruh antrian pasien klinik.</p>
        </div>
    </div>

    <div class="card card-table p-4">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Tgl Kunjungan</th>
                        <th>No. Antrian</th>
                        <th>Pasien</th>
                        <th>Dokter Tujuan</th>
                        <th>Keluhan</th>
                        <th>Status</th>
                        <th class="text-end">Aksi Kendali</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Ambil seluruh data reservasi
                    $q = db_query($conn, "SELECT r.*, p.nama_lengkap, d.nama_dokter, d.spesialisasi 
                                             FROM reservasi r 
                                             JOIN pasien p ON r.nik = p.nik
                                             JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal
                                             JOIN dokter d ON j.id_dokter = d.id_dokter
                                             ORDER BY r.tanggal_kunjungan DESC, r.no_antrian ASC");
                    
                    if (db_num_rows($q) > 0) {
                        while($row = db_fetch_assoc($q)):
                            // Tentukan warna badge status
                            $bg = 'bg-secondary';
                            if($row['status'] == 'Menunggu') $bg = 'bg-warning text-dark';
                            if($row['status'] == 'Dikonfirmasi') $bg = 'bg-info text-dark';
                            if($row['status'] == 'Selesai') $bg = 'bg-success';
                            if($row['status'] == 'Ditolak') $bg = 'bg-danger';
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-dark"><?php echo date('d M Y', strtotime($row['tanggal_kunjungan'])); ?></div>
                        </td>
                        <td><span class="badge bg-light text-dark border fs-6">#<?php echo $row['no_antrian']; ?></span></td>
                        <td><span class="fw-semibold text-success"><?php echo $row['nama_lengkap']; ?></span></td>
                        <td>
                            <div class="fw-bold"><?php echo $row['nama_dokter']; ?></div>
                            <small class="text-muted"><?php echo $row['spesialisasi']; ?></small>
                        </td>
                        <td>
                            <span class="d-inline-block text-truncate text-muted" style="max-width: 150px;">
                                <?php echo $row['keluhan']; ?>
                            </span>
                        </td>
                        <td><span class="badge rounded-pill <?php echo $bg; ?> px-3 py-2"><?php echo $row['status']; ?></span></td>
                        
                        <td class="text-end">
                            <?php if($row['status'] == 'Menunggu'): ?>
                                <a href="reservasi.php?aksi=konfirmasi&id=<?php echo $row['id_reservasi']; ?>" class="btn btn-success btn-action text-white" title="Konfirmasi Antrian">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="reservasi.php?aksi=tolak&id=<?php echo $row['id_reservasi']; ?>" class="btn btn-danger btn-action text-white ms-1" title="Tolak Antrian" onclick="return confirm('Yakin ingin menolak antrian ini?');">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>

                            <?php if($row['status'] == 'Dikonfirmasi'): ?>
                                <a href="input_tagihan.php?id=<?php echo $row['id_reservasi']; ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                    <i class="fas fa-cash-register me-1"></i> Input Kasir
                                </a>
                            <?php endif; ?>

                            <?php if($row['status'] == 'Selesai'): ?>
                                <span class="text-success small fw-bold"><i class="fas fa-check-double"></i> LUNAS</span>
                            <?php endif; ?>
                            
                            <?php if($row['status'] == 'Ditolak'): ?>
                                <span class="text-danger small fw-bold"><i class="fas fa-ban"></i> DIBATALKAN</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; } else { ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data reservasi pasien.</td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php if(isset($pesan_sukses)) : ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?php echo $pesan_sukses; ?>',
            timer: 2000,
            showConfirmButton: false
        }).then(() => {
            window.location = 'reservasi.php'; // Hilangkan parameter GET dari URL
        });
    <?php endif; ?>
</script>

</body>
</html>