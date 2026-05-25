<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasir & Tagihan - Cliniq Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0f3d2e; --accent: #76c720; --bg-light: #f8f9fa; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-table { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .badge-status { font-size: 13px; font-weight: 600; padding: 6px 12px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h2 class="fw-bold mb-1">Kasir & Rekapitulasi Pembayaran</h2>
            <p class="text-muted mb-0">Kelola tagihan pasien yang telah selesai diperiksa dokter.</p>
        </div>
    </div>

    <div class="card card-table p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>No. Antrian</th>
                        <th>Nama Pasien</th>
                        <th>Status Pemeriksaan</th>
                        <th>Metode Bayar</th>
                        <th>Total Biaya</th>
                        <th class="text-center">Aksi Kasir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // PERBAIKAN: Mengambil data dari tabel reservasi yang statusnya berhubungan dengan pembayaran
                    // Kita pakai LEFT JOIN pembayaran, karena pasien yang baru selesai diperiksa belum punya data di tabel pembayaran
                    $q = db_query($conn, "SELECT r.*, p.nama_lengkap, b.id_pembayaran, b.metode_pembayaran, b.jumlah_bayar, b.status_pembayaran 
                                              FROM reservasi r 
                                              JOIN pasien p ON r.nik = p.nik 
                                              LEFT JOIN pembayaran b ON r.id_reservasi = b.id_reservasi 
                                              WHERE r.status IN ('Menunggu Pembayaran', 'Selesai') 
                                              ORDER BY r.tanggal_kunjungan DESC, r.no_antrian DESC");
                    
                    if(db_num_rows($q) > 0){
                        while($row = db_fetch_assoc($q)):
                    ?>
                    <tr>
                        <td class="fw-bold text-secondary">#<?php echo $row['no_antrian']; ?></td>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        
                        <td>
                            <?php if($row['status'] == 'Menunggu Pembayaran'): ?>
                                <span class="badge badge-status bg-warning text-dark"><i class="fas fa-clock me-1"></i> Menunggu Bayar</span>
                            <?php else: ?>
                                <span class="badge badge-status bg-success"><i class="fas fa-check-double me-1"></i> Selesai (Lunas)</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if(isset($row['metode_pembayaran'])): ?>
                                <span class="badge bg-light text-dark border"><i class="fas fa-money-check-alt me-1"></i> <?php echo $row['metode_pembayaran']; ?></span>
                            <?php else: ?>
                                <span class="text-muted small fst-italic">Belum ada</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="fw-bold text-success">
                            <?php echo (isset($row['jumlah_bayar'])) ? 'Rp ' . number_format($row['jumlah_bayar'], 0, ',', '.') : '-'; ?>
                        </td>

                        <td class="text-center">
                            <?php if($row['status'] == 'Menunggu Pembayaran'): ?>
                                <a href="input_tagihan.php?id=<?php echo $row['id_reservasi']; ?>" class="btn btn-sm btn-primary fw-bold px-3 rounded-pill shadow-sm">
                                    <i class="fas fa-calculator me-1"></i> Input Tagihan
                                </a>
                            <?php else: ?>
                                <span class="text-success small fw-bold"><i class="fas fa-check-circle"></i> LUNAS</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; } else { echo "<tr><td colspan='6' class='text-center py-5 text-muted'>Belum ada pasien yang menunggu pembayaran di kasir.</td></tr>"; } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>