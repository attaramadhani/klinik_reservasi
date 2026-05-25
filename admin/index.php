<?php
session_start();
include '../koneksi.php'; 

// Proteksi Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// 1. Ambil Data Statistik
$total_pasien = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as total FROM pasien"))['total'];
$total_dokter = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as total FROM dokter"))['total'];
$total_reservasi = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as total FROM reservasi"))['total'];
$bayar_pending = db_fetch_assoc(db_query($conn, "SELECT COUNT(*) as total FROM pembayaran WHERE status_pembayaran = 'Pending'"))['total'];

// Hitung total pendapatan bulan ini
$bulan_ini = date('Y-m');
$query_pendapatan = db_query($conn, "SELECT SUM(b.jumlah_bayar) as total_pendapatan 
                                         FROM pembayaran b 
                                         JOIN reservasi r ON b.id_reservasi = r.id_reservasi 
                                         WHERE b.status_pembayaran = 'Lunas' 
                                         AND DATE_FORMAT(r.tanggal_kunjungan, '%Y-%m') = '$bulan_ini'");
$pendapatan_bulan_ini = db_fetch_assoc($query_pendapatan)['total_pendapatan'];
$pendapatan_bulan_ini = $pendapatan_bulan_ini ? $pendapatan_bulan_ini : 0; // Jika null jadikan 0

// Hitung total pendapatan tahun ini
$tahun_ini = date('Y');
$query_pendapatan_tahun = db_query($conn, "SELECT SUM(b.jumlah_bayar) as total_pendapatan 
                                         FROM pembayaran b 
                                         JOIN reservasi r ON b.id_reservasi = r.id_reservasi 
                                         WHERE b.status_pembayaran = 'Lunas' 
                                         AND DATE_FORMAT(r.tanggal_kunjungan, '%Y') = '$tahun_ini'");
$pendapatan_tahun_ini = db_fetch_assoc($query_pendapatan_tahun)['total_pendapatan'];
$pendapatan_tahun_ini = $pendapatan_tahun_ini ? $pendapatan_tahun_ini : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Cliniq System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --sidebar-bg: #0f3d2e; 
            --accent: #76c720; 
            --bg-light: #f4f7f6; 
            --white: #ffffff;
            --text-dark: #1a2b23;
        }

        body { 
            background: var(--bg-light); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--text-dark);
        }
        
        /* Stat Cards Modern */
        .stat-card { 
            border: none; 
            border-radius: 24px; 
            background: var(--white);
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        .stat-card:hover { 
            transform: translateY(-10px); 
            box-shadow: 0 20px 35px rgba(0,0,0,0.06); 
        }
        .icon-box {
            width: 56px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 18px;
        }

        /* Quick Action Buttons */
        .btn-quick { 
            background: var(--white);
            border-radius: 20px; 
            padding: 20px; 
            font-weight: 700; 
            border: 1px solid transparent; 
            transition: all 0.3s ease; 
            color: var(--text-dark);
            text-decoration: none;
            display: block;
        }
        .btn-quick:hover { 
            border-color: var(--accent);
            background: #f0f9eb;
            color: #0f3d2e;
            transform: scale(1.02);
        }

        /* Tables Modern */
        .table-card {
            background: var(--white);
            border-radius: 28px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.02);
            overflow: hidden;
        }
        .table thead th {
            background: #fafafa;
            border-bottom: none;
            padding: 18px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
        }
        .table tbody td {
            padding: 18px;
            border-bottom: 1px solid #f1f1f1;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
        }

        header .avatar {
            width: 48px;
            height: 48px;
            border: 3px solid var(--white);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <header class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-800 mb-1" style="color: #0f3d2e;">Halo, <?php echo $_SESSION['username']; ?>! 👋</h2>
            <p class="text-muted mb-0">Selamat datang kembali di panel manajemen klinik.</p>
        </div>
        <div class="d-flex align-items-center bg-white p-2 rounded-pill shadow-sm">
            <span class="mx-3 fw-bold small text-dark d-none d-md-inline">Administrator</span>
            <img src="https://ui-avatars.com/api/?name=Admin&background=76c720&color=0f3d2e&bold=true" class="rounded-circle avatar">
        </div>
    </header>

    <div class="row g-3 mb-5">
        <!-- Revenue Card (New) -->
        <div class="col-md-12 col-lg-4">
            <div class="card stat-card" style="background: linear-gradient(135deg, #155724 0%, #28a745 100%); color: white;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75 fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Pendapatan Bulan Ini</small>
                        <h2 class="fw-800 mt-2 mb-0" style="font-size: 28px;">Rp <?php echo number_format($pendapatan_bulan_ini, 0, ',', '.'); ?></h2>
                        <div class="mt-2 text-white-50" style="font-size: 11px; font-weight: 600;">
                            <i class="fas fa-chart-line me-1"></i> TH KINI: Rp <?php echo number_format($pendapatan_tahun_ini, 0, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="icon-box bg-white shadow-sm" style="width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-wallet fa-lg" style="color: #155724;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="card stat-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Total Dokter</small>
                        <h3 class="fw-800 mt-1 mb-0"><?php echo $total_dokter; ?></h3>
                    </div>
                    <div class="icon-box bg-primary bg-opacity-10 text-primary" style="width: 40px; height: 40px;">
                        <i class="fas fa-user-md"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="card stat-card h-100 border-0 bg-dark text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75 fw-bold text-uppercase" style="font-size: 10px;">Total Pasien</small>
                        <h3 class="fw-800 mt-1 mb-0"><?php echo $total_pasien; ?></h3>
                    </div>
                    <div class="icon-box bg-white bg-opacity-10" style="width: 40px; height: 40px;">
                        <i class="fas fa-users text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="card stat-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-muted fw-bold text-uppercase" style="font-size: 10px;">Reservasi</small>
                        <h3 class="fw-800 mt-1 mb-0"><?php echo $total_reservasi; ?></h3>
                    </div>
                    <div class="icon-box bg-info bg-opacity-10 text-info" style="width: 40px; height: 40px;">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-2">
            <div class="card stat-card h-100 border-bottom border-danger border-5">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <small class="text-danger fw-bold text-uppercase" style="font-size: 10px;">Tagihan Pending</small>
                        <h3 class="fw-800 mt-1 mb-0 text-danger"><?php echo $bayar_pending; ?></h3>
                    </div>
                    <div class="icon-box bg-danger bg-opacity-10 text-danger" style="width: 40px; height: 40px;">
                        <i class="fas fa-receipt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-5">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-body p-0">
                    <div class="p-4 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background: linear-gradient(135deg, #0f3d2e 0%, #1a5c43 100%); color: white;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-trophy text-warning fa-2x me-3"></i>
                            <div>
                                <h5 class="fw-800 mb-0">Peringkat Dokter Terfavorit</h5>
                                <small class="opacity-75">Bulan Ini (<?php echo date('F Y'); ?>)</small>
                            </div>
                        </div>
                        
                        <a href="laporan.php" class="btn btn-warning btn-sm fw-bold px-3 text-dark rounded-pill ms-auto shadow-sm">
                            <i class="fas fa-chart-line me-1"></i> Detail Laporan
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4 text-center" style="width: 80px;">Peringkat</th>
                                    <th>Nama Dokter</th>
                                    <th>Total Reservasi Pasien</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $bulan_ini = date('Y-m');
                                
                                $query_ranking = db_query($conn, "SELECT d.nama_dokter, COUNT(r.id_reservasi) as total_reservasi 
                                                                         FROM dokter d 
                                                                         LEFT JOIN jadwal_dokter j ON d.id_dokter = j.id_dokter 
                                                                         LEFT JOIN reservasi r ON j.id_jadwal = r.id_jadwal 
                                                                         AND DATE_FORMAT(r.tanggal_kunjungan, '%Y-%m') = '$bulan_ini'
                                                                         GROUP BY d.id_dokter 
                                                                         ORDER BY total_reservasi DESC");
                                $rank = 1;
                                while($row_rank = db_fetch_assoc($query_ranking)):
                                    $is_top = $rank == 1;
                                    $is_second = $rank == 2;
                                    $is_third = $rank == 3;
                                ?>
                                <tr <?php echo $is_top ? 'class="table-warning bg-opacity-25"' : ''; ?>>
                                    <td class="ps-4 text-center">
                                        <?php if($is_top): ?>
                                            <i class="fas fa-medal text-warning fa-xl"></i>
                                        <?php elseif($is_second): ?>
                                            <i class="fas fa-medal text-secondary fa-lg"></i>
                                        <?php elseif($is_third): ?>
                                            <i class="fas fa-medal fa-lg" style="color: #cd7f32;"></i>
                                        <?php else: ?>
                                            <span class="fw-bold text-muted">#<?php echo $rank; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3 <?php echo $is_top ? 'bg-warning text-white' : 'bg-light text-muted'; ?>" style="width: 40px; height: 40px;">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold <?php echo $is_top ? 'text-dark fs-5' : ''; ?>"><?php echo $row_rank['nama_dokter']; ?></span>
                                                <?php if($is_top): ?> <span class="badge bg-warning text-dark ms-2"><i class="fas fa-star me-1"></i> TOP</span> <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $is_top ? 'bg-success' : 'bg-secondary'; ?> rounded-pill px-3 py-2" style="font-size: 13px;">
                                            <i class="fas fa-users me-1"></i> <?php echo $row_rank['total_reservasi']; ?> Pasien
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                $rank++;
                                endwhile; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-800 mb-4" style="color: #0f3d2e;">Aksi Cepat Manajemen</h5>
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <a href="tambah_dokter.php" class="btn-quick shadow-sm">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-4 me-3">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <span>Tambah Dokter Baru</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="tambah_jadwal.php" class="btn-quick shadow-sm">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-4 me-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <span>Atur Jadwal Dokter</span>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="konfirmasi_pembayaran.php" class="btn-quick shadow-sm">
                <div class="d-flex align-items-center">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-4 me-3">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <span>Menu Kasir (Tagihan)</span>
                </div>
            </a>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body p-0">
            <div class="p-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-800 mb-0">Antrian Terbaru Pasien</h5>
                <a href="reservasi.php" class="btn btn-sm px-4 rounded-pill fw-bold" style="background: #f0f0f0; color: #555;">Lihat Semua <i class="fas fa-chevron-right ms-1" style="font-size: 10px;"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">No. Antrian</th>
                            <th>Nama Pasien</th>
                            <th>Dokter</th>
                            <th>Status</th>
                            <th class="text-center pe-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $q = db_query($conn, "SELECT r.*, p.nama_lengkap, d.nama_dokter 
                                                 FROM reservasi r 
                                                 JOIN pasien p ON r.nik = p.nik
                                                 JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal
                                                 JOIN dokter d ON j.id_dokter = d.id_dokter
                                                 ORDER BY r.id_reservasi DESC LIMIT 5");
                        while($row = db_fetch_assoc($q)):
                        ?>
                        <tr>
                            <td class="ps-4"><span class="fw-800 text-muted">#<?php echo $row['no_antrian']; ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 35px; height: 35px;">
                                        <i class="fas fa-user text-muted" style="font-size: 12px;"></i>
                                    </div>
                                    <span class="fw-bold"><?php echo $row['nama_lengkap']; ?></span>
                                </div>
                            </td>
                            <td class="fw-600 text-muted small"><?php echo $row['nama_dokter']; ?></td>
                            <td>
                                <?php
                                $color = 'secondary';
                                if($row['status'] == 'Menunggu') $color = 'warning';
                                if($row['status'] == 'Dikonfirmasi') $color = 'info';
                                if($row['status'] == 'Selesai') $color = 'success';
                                ?>
                                <span class="status-badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?>">
                                    <i class="fas fa-circle me-1" style="font-size: 8px;"></i> <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td class="text-center pe-4">
                                <?php if($row['status'] != 'Selesai'): ?>
                                    <a href="input_tagihan.php?id=<?php echo $row['id_reservasi']; ?>" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold" style="font-size: 11px;">
                                        PROSES KASIR
                                    </a>
                                <?php else: ?>
                                    <span class="text-success small fw-800"><i class="fas fa-check-double"></i> SELESAI</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>