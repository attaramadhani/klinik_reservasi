<?php
session_start();
include '../koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$query = "SELECT * FROM pasien WHERE id_user = '$id_user'";
$result = db_query($conn, $query);
$pasien = db_fetch_assoc($result);

$nama_sapaan = $pasien ? htmlspecialchars($pasien['nama_lengkap']) : htmlspecialchars($_SESSION['username']);
$nik_pasien = $pasien['nik'];

// AMBIL RESERVASI TERDEKAT
$q_status = "SELECT r.*, d.nama_dokter, d.spesialisasi, j.hari
             FROM reservasi r
             JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal
             JOIN dokter d ON j.id_dokter = d.id_dokter
             WHERE r.nik = '$nik_pasien' AND r.status IN ('Menunggu', 'Dikonfirmasi')
             ORDER BY r.tanggal_kunjungan ASC LIMIT 1";
$res_status = db_query($conn, $q_status);
$data_aktif = db_fetch_assoc($res_status);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pasien - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-green: #0f3d2e;
            --accent-green: #76c720;
            --bg-soft: #f0f4f3;
        }

        body {
            background-color: var(--bg-soft);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #2d3436;
        }

        /* Navbar Blur Effect */
        .navbar {
            background: rgba(15, 61, 46, 0.95) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .navbar-brand { font-weight: 800; font-size: 22px; }

        /* Hero Section Modern */
        .hero-section {
            background: linear-gradient(135deg, #0f3d2e 0%, #1a5c43 100%);
            color: white;
            padding: 80px 20px 80px 20px;
            border-radius: 0 0 50px 50px;
            position: relative;
        }

        /* Status Card Glassmorphism */
        .card-status {
            border: none;
            border-radius: 24px;
            background: white;
            margin-top: -60px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            padding: 25px;
            transition: 0.3s;
        }
        .card-status:hover { transform: translateY(-5px); }

        /* Menu Grid Modern */
        .menu-card {
            border: none;
            border-radius: 24px;
            background: white;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 10px 20px rgba(0,0,0,0.02);
            height: 100%;
            text-decoration: none;
            display: block;
            color: var(--primary-green);
            position: relative;
            overflow: hidden;
        }
        .menu-card:hover {
            transform: translateY(-12px);
            box-shadow: 0 20px 40px rgba(15, 61, 46, 0.12);
            background: var(--primary-green);
            color: white !important;
        }
        .menu-card:hover .icon-box { background: rgba(255,255,255,0.2); color: white; }
        .menu-card:hover .card-text { color: rgba(255,255,255,0.8); }

        .icon-box {
            width: 65px;
            height: 65px;
            line-height: 65px;
            border-radius: 20px;
            background: #f0f9eb;
            color: var(--primary-green);
            font-size: 26px;
            margin: 0 auto 20px auto;
            transition: 0.3s;
        }

        .card-title { font-weight: 800; font-size: 17px; margin-bottom: 8px; }
        .card-text { font-size: 13px; color: #636e72; }

        .status-badge {
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .btn-logout {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 12px;
            transition: 0.3s;
        }
        .btn-logout:hover { background: #ff7675; border-color: #ff7675; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 60px 15px 60px 15px;
                border-radius: 0 0 30px 30px;
            }
            .hero-section h1 {
                font-size: 1.8rem;
            }
            .card-status {
                margin-top: -40px;
                padding: 18px;
            }
            .card-status .border-end {
                border-right: none !important;
                border-bottom: 1px solid #eee;
                margin-bottom: 10px;
                padding-bottom: 10px;
            }
            .card-status h1 {
                font-size: 36px !important;
            }
            .menu-card {
                padding: 20px 15px;
            }
            .icon-box {
                width: 50px;
                height: 50px;
                line-height: 50px;
                font-size: 22px;
                border-radius: 16px;
                margin-bottom: 12px;
            }
            .card-title {
                font-size: 14px;
            }
            .card-text {
                font-size: 11px;
            }
            .navbar {
                padding: 10px 0 !important;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top py-3">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-heartbeat me-2" style="color: var(--accent-green);"></i>CLINIQ</a>
            <div class="d-flex align-items-center">
                <span class="text-white small d-none d-md-inline me-3 opacity-75">Halo, <b><?php echo $nama_sapaan; ?></b></span>
                <button onclick="confirmLogout()" class="btn btn-logout text-white px-3 fw-bold rounded-pill">Logout</button>
            </div>
        </div>
    </nav>

    <div class="hero-section text-center">
        <div class="container pt-4">
            <h1 class="fw-800 display-5 mb-2">Solusi Sehat Keluarga</h1>
            <p class="opacity-75">Kelola janji temu dokter Anda dengan lebih cerdas dan cepat.</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card-status mb-5">
                    <div class="row align-items-center">
                        <?php if($data_aktif): ?>
                            <div class="col-md-3 text-center border-end py-2">
                                <span class="text-muted d-block fw-bold x-small" style="font-size: 10px; letter-spacing: 1px;">NOMOR ANTRIAN</span>
                                <h1 class="fw-800 text-success mb-0" style="font-size: 48px;">#<?php echo $data_aktif['no_antrian']; ?></h1>
                            </div>
                            <div class="col-md-6 ps-md-4 py-2">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="status-badge <?php echo ($data_aktif['status'] == 'Dikonfirmasi') ? 'bg-success text-white' : 'bg-warning text-dark'; ?> me-2">
                                        <?php echo strtoupper($data_aktif['status']); ?>
                                    </span>
                                    <span class="text-muted small fw-bold">Jadwal Terdekat</span>
                                </div>
                                <h5 class="fw-bold mb-1"><?php echo $data_aktif['nama_dokter']; ?></h5>
                                <p class="mb-0 text-muted small">
                                    <i class="far fa-clock me-1"></i> <?php echo $data_aktif['hari']; ?>, <?php echo date('d M Y', strtotime($data_aktif['tanggal_kunjungan'])); ?>
                                </p>
                            </div>
                            <div class="col-md-3 text-md-end py-2">
                                <a href="riwayat.php" class="btn btn-dark w-100 rounded-pill fw-bold" style="font-size: 13px;">Lihat Tiket <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        <?php else: ?>
                            <div class="col-12 text-center py-2">
                                <p class="mb-0 text-muted fw-bold small">Belum ada reservasi aktif hari ini. <a href="reservasi.php" class="text-success text-decoration-none ms-1">Mulai Reservasi <i class="fas fa-external-link-alt"></i></a></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4 col-6">
                <a href="jadwal.php" class="menu-card">
                    <div class="icon-box"><i class="fas fa-calendar-alt"></i></div>
                    <div class="card-title">Jadwal Dokter</div>
                    <div class="card-text">Cek ketersediaan dokter favorit</div>
                </a>
            </div>
            <div class="col-md-4 col-6">
                <a href="reservasi.php" class="menu-card">
                    <div class="icon-box"><i class="fas fa-plus-circle"></i></div>
                    <div class="card-title">Reservasi Baru</div>
                    <div class="card-text">Daftar antrian periksa sekarang</div>
                </a>
            </div>
            <div class="col-md-4 col-6">
                <a href="riwayat.php" class="menu-card">
                    <div class="icon-box"><i class="fas fa-clipboard-list"></i></div>
                    <div class="card-title">Riwayat Saya</div>
                    <div class="card-text">Status reservasi & antrian</div>
                </a>
            </div>
            <div class="col-md-4 col-6">
                <a href="hasil.php" class="menu-card">
                    <div class="icon-box"><i class="fas fa-file-medical"></i></div>
                    <div class="card-title">Hasil Periksa</div>
                    <div class="card-text">Diagnosa & resep dokter digital</div>
                </a>
            </div>
            <div class="col-md-4 col-6">
                <a href="profil.php" class="menu-card">
                    <div class="icon-box"><i class="fas fa-user-cog"></i></div>
                    <div class="card-title">Profil Pasien</div>
                    <div class="card-text">Data diri & pengaturan akun</div>
                </a>
            </div>
        </div>
    </div>

    <footer class="text-center py-5 text-muted mt-5">
        <p class="small mb-0">&copy; 2026 <b>Cliniq System</b>. Crafted with care for your health.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmLogout() {
            Swal.fire({
                title: 'Akhiri Sesi?',
                text: "Anda akan keluar dari dashboard pasien.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#0f3d2e',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-4',
                    confirmButton: 'rounded-pill px-4',
                    cancelButton: 'rounded-pill px-4'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            })
        }
    </script>
</body>
</html>
