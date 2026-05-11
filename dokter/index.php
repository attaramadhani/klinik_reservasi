<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$q_dokter = mysqli_query($conn, "SELECT * FROM dokter WHERE id_user = '$id_user'");
$dokter = mysqli_fetch_assoc($q_dokter);
$id_dokter = $dokter['id_dokter'];

// Mendapatkan nama hari ini dalam Bahasa Indonesia untuk filter jadwal
$hari_ini = date('l');
$daftar_hari = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
$hari_lokal = $daftar_hari[$hari_ini];

// 1. Total Pasien Hari Ini (Berdasarkan Hari Praktik & Status Konfirmasi)
// PERBAIKAN: Menggunakan j.hari karena kolom j.tanggal tidak ada
$q_pasien_hari_ini = mysqli_query($conn, "SELECT COUNT(*) as total FROM reservasi r 
                                          JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal 
                                          WHERE j.id_dokter = '$id_dokter' 
                                          AND j.hari = '$hari_lokal' 
                                          AND r.status = 'Dikonfirmasi'");
$total_pasien_hari_ini = mysqli_fetch_assoc($q_pasien_hari_ini)['total'];

// 2. Menunggu Konfirmasi (Total semua reservasi baru yang belum direspon)
$q_menunggu = mysqli_query($conn, "SELECT COUNT(*) as total FROM reservasi r 
                                   JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal 
                                   WHERE j.id_dokter = '$id_dokter' 
                                   AND r.status = 'Menunggu'");
$menunggu_konfirmasi = mysqli_fetch_assoc($q_menunggu)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dokter - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
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

        /* Card Statistik */
        .stat-card {
            border: none;
            border-radius: 24px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            padding: 25px;
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.08); }
        .icon-box {
            width: 60px; height: 60px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <header class="mb-5">
        <h2 class="fw-800 mb-1" style="color: var(--primary-green);">Selamat datang, <?php echo htmlspecialchars($dokter['nama_dokter']); ?>! 👋</h2>
        <p class="text-muted">Pantau jadwal praktik dan antrian pasien Anda hari ini.</p>
    </header>

    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted fw-bold text-uppercase" style="letter-spacing: 1px;">Pasien Hari Ini</small>
                        <h1 class="fw-800 mt-2 mb-0" style="color: var(--primary-green); font-size: 40px;"><?php echo $total_pasien_hari_ini; ?></h1>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 text-success">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="stat-card border-bottom border-warning border-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-warning fw-bold text-uppercase" style="letter-spacing: 1px;">Menunggu Konfirmasi</small>
                        <h1 class="fw-800 mt-2 mb-0 text-warning" style="font-size: 40px;"><?php echo $menunggu_konfirmasi; ?></h1>
                    </div>
                    <div class="icon-box bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-bell"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 rounded-4 shadow-sm">
        <div class="card-body p-4">
            <h5 class="fw-800 mb-4 text-dark"><i class="fas fa-bolt me-2 text-warning"></i>Aksi Cepat</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <a href="pemeriksaan.php" class="btn w-100 py-3 rounded-3 fw-bold text-white shadow-sm" style="background: var(--primary-green);">
                        <i class="fas fa-stethoscope me-2"></i> Mulai Pemeriksaan
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="reservasi.php" class="btn w-100 py-3 rounded-3 fw-bold text-dark border shadow-sm bg-white">
                        <i class="fas fa-check-circle me-2 text-success"></i> Cek Antrian Masuk
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="jadwal.php" class="btn w-100 py-3 rounded-3 fw-bold text-dark border shadow-sm bg-white">
                        <i class="fas fa-calendar-plus me-2 text-primary"></i> Atur Jadwal Baru
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>