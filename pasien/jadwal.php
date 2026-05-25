<?php
session_start();
include '../koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Dokter - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-dark: #0f3d2e;
            --accent-green: #76c720;
            --glass-bg: rgba(255, 255, 255, 0.08);
        }

        body { 
            background: radial-gradient(circle at top left, #02340cff, #024b33ff);
            color: white; 
            min-height: 100vh; 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            padding-bottom: 50px;
        }

        .navbar-custom {
            background: rgba(15, 61, 46, 0.7);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .header-title {
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(to right, #fff, #76c720);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Card Modern Glassmorphism */
        .doctor-card { 
            background: var(--glass-bg); 
            backdrop-filter: blur(12px); 
            border: 1px solid rgba(255,255,255,0.15); 
            border-radius: 24px; 
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            position: relative;
            overflow: hidden;
        }
        
        .doctor-card::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
            transition: 0.5s;
        }

        .doctor-card:hover::before { left: 100%; }

        .doctor-card:hover { 
            transform: translateY(-8px); 
            background: rgba(255, 255, 255, 0.12);
            border-color: var(--accent-green);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }

        .doctor-avatar-box { 
            width: 70px; height: 70px; 
            background: linear-gradient(135deg, var(--accent-green), #5eb018); 
            color: var(--primary-dark); 
            border-radius: 20px; 
            display: flex; align-items: center; justify-content: center; 
            font-size: 28px;
            box-shadow: 0 8px 16px rgba(118, 199, 32, 0.3);
        }

        .schedule-info {
            background: rgba(0,0,0,0.2);
            border-radius: 16px;
            padding: 15px;
            border-left: 4px solid var(--accent-green);
        }

        .btn-booking {
            background: var(--accent-green);
            color: var(--primary-dark);
            font-weight: 800;
            border-radius: 14px;
            padding: 12px;
            transition: 0.3s;
            border: none;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .btn-booking:hover {
            background: #fff;
            color: var(--primary-dark);
            transform: scale(1.03);
        }

        .day-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--accent-green);
            font-weight: 700;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark navbar-custom sticky-top p-3 mb-4">
        <div class="container">
            <a class="btn btn-sm btn-outline-light rounded-pill px-3" href="index.php">
                <i class="fas fa-chevron-left me-1"></i> Dashboard
            </a>
            <h5 class="mb-0 fw-800 text-white">JADWAL PRAKTEK</h5>
        </div>
    </nav>

    <div class="container py-2">
        <div class="text-center mb-5">
            <h2 class="header-title display-6">Temukan Dokter Terbaik</h2>
            <p class="text-white-50 small mb-4">Pilih jadwal berdasarkan spesialisasi yang Anda butuhkan</p>
            
            <!-- Filter Poli -->
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <?php 
                $filter_poli = isset($_GET['poli']) ? db_real_escape_string($conn, $_GET['poli']) : '';
                $q_poli = db_query($conn, "SELECT DISTINCT spesialisasi FROM dokter ORDER BY spesialisasi ASC");
                ?>
                <a href="jadwal.php" style="border: 2px solid rgba(255,255,255,0.4);" class="btn px-4 rounded-pill fw-bold <?php echo ($filter_poli == '') ? 'btn-light text-dark' : 'text-white border'; ?>">Semua Poli</a>
                <?php while($p = db_fetch_assoc($q_poli)): 
                    $is_active = ($filter_poli == $p['spesialisasi']);
                ?>
                    <a href="jadwal.php?poli=<?php echo urlencode($p['spesialisasi']); ?>" 
                       style="border: 2px solid rgba(255,255,255,0.4);"
                       class="btn px-4 rounded-pill fw-bold <?php echo $is_active ? 'btn-light text-dark shadow' : 'text-white border opacity-75'; ?>">
                        <?php echo htmlspecialchars($p['spesialisasi']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="row g-4">
            <?php
            $where_clause = "";
            if ($filter_poli !== '') {
                $where_clause = "WHERE d.spesialisasi = '$filter_poli'";
            }
            $q = db_query($conn, "SELECT d.*, j.id_jadwal, j.hari, j.jam_mulai, j.jam_selesai 
                                     FROM dokter d 
                                     JOIN jadwal_dokter j ON d.id_dokter = j.id_dokter
                                     $where_clause
                                     ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')");
            
            if(db_num_rows($q) == 0) {
                echo "<div class='col-12 text-center py-5'><i class='fas fa-calendar-times fa-3x mb-3 opacity-25'></i><p class='opacity-50'>Jadwal belum tersedia untuk saat ini.</p></div>";
            }

            while($d = db_fetch_assoc($q)) {
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="doctor-card p-4 h-100 d-flex flex-column">
                    <div class="d-flex align-items-center mb-4">
                        <div class="doctor-avatar-box me-3">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($d['nama_dokter']); ?></h5>
                            <span class="badge bg-white bg-opacity-10 text-accent-green small fw-normal mt-1">
                                <?php echo htmlspecialchars($d['spesialisasi']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="schedule-info mt-auto">
                        <div class="day-label mb-1">Jadwal Praktek</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold"><i class="far fa-calendar-check me-2"></i><?php echo $d['hari']; ?></span>
                            <span class="fw-bold text-accent-green">
                                <?php echo date('H:i', strtotime($d['jam_mulai'])); ?> - <?php echo date('H:i', strtotime($d['jam_selesai'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <a href="reservasi.php?id_jadwal=<?php echo $d['id_jadwal']; ?>" class="btn btn-booking w-100 mt-4 shadow-sm">
                        Booking Sekarang
                    </a>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>