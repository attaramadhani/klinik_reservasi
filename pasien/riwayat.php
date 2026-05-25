<?php
session_start();
include '../koneksi.php';

// Cek Login Pasien
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$q_pasien = db_query($conn, "SELECT nik FROM pasien WHERE id_user = '$id_user'");
$d_pasien = db_fetch_assoc($q_pasien);
$nik_pasien = $d_pasien['nik'];

// LOGIKA PEMBATALAN RESERVASI
if (isset($_GET['aksi']) && $_GET['aksi'] == 'batal' && isset($_GET['id'])) {
    $id_reservasi = db_real_escape_string($conn, $_GET['id']);
    $cek_milik = db_query($conn, "SELECT * FROM reservasi WHERE id_reservasi='$id_reservasi' AND nik='$nik_pasien' AND status='Menunggu'");
    
    if (db_num_rows($cek_milik) > 0) {
        $hapus = db_query($conn, "DELETE FROM reservasi WHERE id_reservasi='$id_reservasi'");
        if ($hapus) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success', title: 'Berhasil Dibatalkan', text: 'Reservasi Anda telah dihapus.', confirmButtonColor: '#155724'
                    }).then(() => { window.location = 'riwayat.php'; });
                });
            </script>";
        }
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'error', title: 'Gagal', text: 'Reservasi tidak dapat dibatalkan (Mungkin sudah diproses).'});
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Reservasi - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .navbar { background: linear-gradient(to right, #0f3d2e, #155724); }
        .card-history { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; transition: 0.3s; background: white; }
        .card-history:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-menunggu { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status-dikonfirmasi { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        /* WARNA BARU UNTUK MENUNGGU PEMBAYARAN */
        .status-bayar { background-color: #fcf0e3; color: #fd7e14; border: 1px solid #f8d7b4; } 
        .status-selesai { background-color: #cff4fc; color: #055160; border: 1px solid #b6effb; }
        .status-ditolak { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .queue-box { background: #155724; color: white; border-radius: 10px; padding: 10px; text-align: center; min-width: 80px; }
        .queue-number { font-size: 24px; font-weight: bold; line-height: 1; }
        .queue-label { font-size: 10px; opacity: 0.8; }
        .btn-cancel { font-size: 12px; color: #dc3545; border: 1px solid #dc3545; border-radius: 20px; padding: 5px 15px; text-decoration: none; transition: 0.3s; }
        .btn-cancel:hover { background-color: #dc3545; color: white; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .card-history .row {
                flex-wrap: wrap;
            }
            .queue-box {
                min-width: 60px;
                padding: 8px;
            }
            .queue-number {
                font-size: 20px;
            }
            .navbar .d-flex {
                gap: 5px;
            }
            .navbar .btn {
                font-size: 11px;
                padding: 5px 10px;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-heartbeat me-2"></i>Cliniq</a>
            <div class="d-flex">
                <a href="reservasi.php" class="btn btn-sm btn-light text-success fw-bold me-2"><i class="fas fa-plus"></i> Reservasi Baru</a>
                <a href="index.php" class="btn btn-sm btn-outline-light">Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px; margin-bottom: 50px;">
        <h3 class="fw-bold mb-4" style="color: #0f3d2e;">Riwayat Kunjungan Saya</h3>

        <?php
        $query = "SELECT r.*, j.hari, j.jam_mulai, j.jam_selesai, d.nama_dokter, d.spesialisasi 
                  FROM reservasi r 
                  JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal 
                  JOIN dokter d ON j.id_dokter = d.id_dokter 
                  WHERE r.nik = '$nik_pasien' 
                  ORDER BY r.id_reservasi DESC"; 
        
        $result = db_query($conn, $query);

        if (db_num_rows($result) > 0) {
            while ($row = db_fetch_assoc($result)) {
                $status_class = ""; $icon_status = "";
                // PERBAIKAN: Tambahan Switch Case untuk Menunggu Pembayaran
                switch($row['status']) {
                    case 'Menunggu': $status_class = "status-menunggu"; $icon_status = "fas fa-clock"; break;
                    case 'Dikonfirmasi': $status_class = "status-dikonfirmasi"; $icon_status = "fas fa-check-circle"; break;
                    case 'Menunggu Pembayaran': $status_class = "status-bayar"; $icon_status = "fas fa-wallet"; break;
                    case 'Selesai': $status_class = "status-selesai"; $icon_status = "fas fa-clipboard-check"; break;
                    case 'Ditolak': $status_class = "status-ditolak"; $icon_status = "fas fa-times-circle"; break;
                }
                $jam_praktek = date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai']));
                $tanggal_indo = date('d M Y', strtotime($row['tanggal_kunjungan']));
        ?>

        <div class="card card-history p-3">
            <div class="row align-items-center">
                <div class="col-3 col-md-2 text-center">
                    <div class="queue-box">
                        <div class="queue-label">ANTRIAN</div>
                        <div class="queue-number">#<?php echo $row['no_antrian']; ?></div>
                    </div>
                </div>

                <div class="col-9 col-md-6">
                    <h5 class="fw-bold mb-1 text-success"><?php echo $row['nama_dokter']; ?></h5>
                    <p class="text-muted small mb-1"><i class="fas fa-stethoscope me-1"></i> <?php echo $row['spesialisasi']; ?></p>
                    <p class="mb-0 fw-bold text-dark small">
                        <i class="far fa-calendar-alt me-1"></i> <?php echo $row['hari'] . ", " . $tanggal_indo; ?>
                        <span class="mx-2">|</span> 
                        <i class="far fa-clock me-1"></i> <?php echo $jam_praktek; ?>
                    </p>
                </div>

                <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
                    <span class="status-badge <?php echo $status_class; ?>">
                        <i class="<?php echo $icon_status; ?> me-1"></i> <?php echo $row['status']; ?>
                    </span>
                    
                    <div class="mt-3">
                        <?php if($row['status'] == 'Menunggu') : ?>
                            <button onclick="confirmCancel(<?php echo $row['id_reservasi']; ?>)" class="btn-cancel bg-white me-2">
                                <i class="fas fa-trash-alt me-1"></i> Batalkan
                            </button>
                        <?php endif; ?>
                        
                        <?php if($row['status'] == 'Menunggu Pembayaran') : ?>
                            <div class="text-warning small fw-bold mb-2">
                                <i class="fas fa-exclamation-triangle"></i> Selesaikan pembayaran di Kasir.
                            </div>
                        <?php endif; ?>

                        <?php if($row['status'] != 'Selesai' && $row['status'] != 'Ditolak') : ?>
                            <a href="cetak_tiket.php?id=<?php echo $row['id_reservasi']; ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm mt-1">
                                <i class="fas fa-print me-1"></i> Cetak Antrian
                            </a>
                        <?php endif; ?>

                        <?php if($row['status'] == 'Selesai') : ?>
                            <span class="text-success fw-bold small me-2"><i class="fas fa-check-double"></i> Lunas</span>
                            <a href="cetak_tiket.php?id=<?php echo $row['id_reservasi']; ?>" target="_blank" class="btn btn-sm btn-success rounded-pill px-3 shadow-sm">
                                <i class="fas fa-file-pdf me-1"></i> Bukti Pembayaran
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php 
            }
        } else {
        ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">Belum ada riwayat reservasi.</h5>
                <a href="reservasi.php" class="btn btn-success mt-2 rounded-pill px-4">Buat Reservasi Sekarang</a>
            </div>
        <?php } ?>
    </div>

    <script>
        function confirmCancel(id) {
            Swal.fire({
                title: 'Batalkan Reservasi?',
                text: "Data antrian akan dihapus permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Batalkan!',
                cancelButtonText: 'Kembali'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'riwayat.php?aksi=batal&id=' + id;
                }
            })
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>