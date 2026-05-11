<?php
session_start();
include '../koneksi.php'; 

// Proteksi Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Ambil parameter filter bulan (default ke bulan ini jika kosong)
$filter_bulan = isset($_GET['bulan']) ? mysqli_real_escape_string($conn, $_GET['bulan']) : date('Y-m');
$tahun_filter = date('Y', strtotime($filter_bulan . '-01'));

// 1. Hitung total pendapatan berdasarkan bulan yang difilter
$query_pendapatan = mysqli_query($conn, "SELECT SUM(b.jumlah_bayar) as total_pendapatan 
                                         FROM pembayaran b 
                                         JOIN reservasi r ON b.id_reservasi = r.id_reservasi 
                                         WHERE b.status_pembayaran = 'Lunas' 
                                         AND DATE_FORMAT(r.tanggal_kunjungan, '%Y-%m') = '$filter_bulan'");
$pendapatan_total = mysqli_fetch_assoc($query_pendapatan)['total_pendapatan'];
$pendapatan_total = $pendapatan_total ? $pendapatan_total : 0; 

// Hitung total pendapatan tahunan (sepanjang tahun dari filter yang dipilih)
$query_pendapatan_tahun = mysqli_query($conn, "SELECT SUM(b.jumlah_bayar) as total_pendapatan 
                                         FROM pembayaran b 
                                         JOIN reservasi r ON b.id_reservasi = r.id_reservasi 
                                         WHERE b.status_pembayaran = 'Lunas' 
                                         AND DATE_FORMAT(r.tanggal_kunjungan, '%Y') = '$tahun_filter'");
$pendapatan_tahun_ini = mysqli_fetch_assoc($query_pendapatan_tahun)['total_pendapatan'];
$pendapatan_tahun_ini = $pendapatan_tahun_ini ? $pendapatan_tahun_ini : 0;

// 2. Data untuk Grafik (Pendapatan per Bulan dalam Tahun Terpilih)
$pendapatan_bulanan = array_fill(1, 12, 0); // Inisialisasi array 12 bulan dengan nilai 0
$query_grafik = mysqli_query($conn, "SELECT MONTH(r.tanggal_kunjungan) as bulan, SUM(b.jumlah_bayar) as total 
                                     FROM pembayaran b 
                                     JOIN reservasi r ON b.id_reservasi = r.id_reservasi 
                                     WHERE b.status_pembayaran = 'Lunas' 
                                     AND YEAR(r.tanggal_kunjungan) = '$tahun_filter' 
                                     GROUP BY MONTH(r.tanggal_kunjungan)");

while ($row_grafik = mysqli_fetch_assoc($query_grafik)) {
    $pendapatan_bulanan[$row_grafik['bulan']] = (int)$row_grafik['total'];
}
// Konversi ke format JSON untuk Javascript
$data_grafik_json = json_encode(array_values($pendapatan_bulanan));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan & Kinerja - Cliniq Admin</title>
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
        
        .main-content { margin-left: 260px; padding: 40px; }

        .stat-card { 
            border: none; 
            border-radius: 24px; 
            background: var(--white);
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.02); 
        }

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
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    
    <!-- HEADER & FILTER -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-800 mb-1" style="color: #0f3d2e;">Laporan Keuangan & Kinerja</h2>
            <p class="text-muted mb-0">Pantau riwayat pendapatan dan performa dokter per bulan.</p>
        </div>
        <form action="" method="GET" class="d-flex align-items-center bg-white p-2 rounded-pill shadow-sm">
            <label class="me-2 ms-3 small fw-bold text-muted">Filter Bulan:</label>
            <input type="month" name="bulan" class="form-control form-control-sm border-0 bg-light rounded-pill me-2 px-3" value="<?php echo $filter_bulan; ?>" style="width: 160px; height: 40px;">
            <button type="submit" class="btn fw-bold px-4 rounded-pill" style="background: var(--accent); color: var(--sidebar-bg); height: 40px;">
                Tampilkan
            </button>
        </form>
    </div>

    <!-- STATS & REPORT CARDS -->
    <div class="row mb-5 g-4">
        <!-- REVENUE SUMMARY CARD TAHUNAN -->
        <div class="col-lg-4">
            <div class="card stat-card h-100" style="background: linear-gradient(135deg, #0f3d2e 0%, #1a5c43 100%); color: white;">
                <div class="d-flex flex-column justify-content-center h-100 py-4">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-box bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-wallet fa-xl text-white"></i>
                        </div>
                        <div>
                            <small class="opacity-75 fw-bold text-uppercase" style="letter-spacing: 1px;">Kinerja Keuangan</small>
                            <h5 class="fw-800 mb-0 mt-1">Bulan <?php echo date('F Y', strtotime($filter_bulan . '-01')); ?></h5>
                        </div>
                    </div>
                    <div>
                        <small class="opacity-75 d-block mb-1">Total Pendapatan (Lunas)</small>
                        <h1 class="fw-800 mb-0" style="font-size: 38px;">Rp <?php echo number_format($pendapatan_total, 0, ',', '.'); ?></h1>
                        <div class="mt-3 pt-3 border-top border-light border-opacity-25 text-white-50" style="font-size: 13px; font-weight: 600;">
                            <i class="fas fa-chart-line me-1"></i> TAHUN <?php echo $tahun_filter; ?>: <span class="text-white">Rp <?php echo number_format($pendapatan_tahun_ini, 0, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- CHART SUMMARY -->
        <div class="col-lg-8">
            <div class="card stat-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-800 mb-0 text-dark">Grafik Pendapatan Klinik</h5>
                        <small class="text-muted">Akumulasi pendapatan per bulan di Tahun <?php echo $tahun_filter; ?></small>
                    </div>
                    <div class="icon-box bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-chart-line text-success"></i>
                    </div>
                </div>
                <div style="height: 250px; width: 100%;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- RANKING TABLE -->
    <div class="row">
        <div class="col-12">
            <div class="card table-card">
                <div class="card-body p-0">
                    <div class="p-4 bg-light border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-trophy text-warning fa-2x me-3"></i>
                            <div>
                                <h5 class="fw-800 mb-0">Peringkat Kinerja Dokter</h5>
                                <small class="text-muted">Berdasarkan total pasien yang ditangani pada bulan <?php echo date('F Y', strtotime($filter_bulan . '-01')); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-5 text-center" style="width: 100px;">Peringkat</th>
                                    <th>Nama Dokter</th>
                                    <th class="text-center">Total Reservasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_ranking = mysqli_query($conn, "SELECT d.nama_dokter, COUNT(r.id_reservasi) as total_reservasi 
                                                                         FROM dokter d 
                                                                         LEFT JOIN jadwal_dokter j ON d.id_dokter = j.id_dokter 
                                                                         LEFT JOIN reservasi r ON j.id_jadwal = r.id_jadwal 
                                                                         AND DATE_FORMAT(r.tanggal_kunjungan, '%Y-%m') = '$filter_bulan'
                                                                         GROUP BY d.id_dokter 
                                                                         ORDER BY total_reservasi DESC");
                                $rank = 1;
                                $has_data = false;
                                
                                while($row_rank = mysqli_fetch_assoc($query_ranking)):
                                    if($row_rank['total_reservasi'] > 0) $has_data = true; // Hanya tampilkan data jika ada
                                    
                                    $is_top = $rank == 1;
                                    $is_second = $rank == 2;
                                    $is_third = $rank == 3;
                                ?>
                                
                                <tr <?php echo $is_top ? 'class="table-warning bg-opacity-10"' : ''; ?>>
                                    <td class="ps-5 text-center">
                                        <?php if($is_top): ?>
                                            <i class="fas fa-medal text-warning fa-2x"></i>
                                        <?php elseif($is_second): ?>
                                            <i class="fas fa-medal text-secondary fa-xl"></i>
                                        <?php elseif($is_third): ?>
                                            <i class="fas fa-medal fa-xl" style="color: #cd7f32;"></i>
                                        <?php else: ?>
                                            <span class="fw-bold fs-5 text-muted">#<?php echo $rank; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3 <?php echo $is_top ? 'bg-warning text-white shadow-sm' : 'bg-light text-muted'; ?>" style="width: 45px; height: 45px;">
                                                <i class="fas fa-user-md"></i>
                                            </div>
                                            <div>
                                                <span class="fw-bold d-block <?php echo $is_top ? 'text-dark fs-5' : 'fs-6'; ?>"><?php echo $row_rank['nama_dokter']; ?></span>
                                                <?php if($is_top): ?> <span class="badge bg-warning text-dark mt-1"><i class="fas fa-star me-1"></i> DOKTER TERFAVORIT</span> <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $is_top ? 'bg-success' : 'bg-secondary'; ?> rounded-pill px-4 py-2 fs-6 shadow-sm">
                                            <i class="fas fa-users me-1"></i> <?php echo $row_rank['total_reservasi']; ?> Pasien
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                $rank++;
                                endwhile; 
                                
                                if(!$has_data):
                                ?>
                                <tr>
                                    <td colspan="3" class="text-center py-5 text-muted">
                                        <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i>
                                        <p>Belum ada data reservasi atau kinerja dokter pada bulan yang dipilih.</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Data dari PHP
    const dataBulanan = <?php echo $data_grafik_json; ?>;
    const tahunTerpilih = '<?php echo $tahun_filter; ?>';
    
    // Konfigurasi Chart.js
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            datasets: [{
                label: 'Total Pendapatan (Rp)',
                data: dataBulanan,
                borderColor: '#76c720', // accent green
                backgroundColor: 'rgba(118, 199, 32, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#0f3d2e', // primary green
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                fill: true,
                tension: 0.4 // membuat garis lebih melengkung (smooth curve)
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false // Sembunyikan legend karena sudah ada judul
                },
                tooltip: {
                    backgroundColor: '#1a2b23',
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 13 },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 14, weight: 'bold' },
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            let value = context.raw;
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }, color: '#888' }
                },
                y: {
                    grid: { color: '#f1f1f1', drawBorder: false, borderDash: [5, 5] },
                    ticks: {
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 },
                        color: '#6c757d',
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000).toFixed(0) + ' Rb';
                            }
                            return 'Rp ' + value;
                        }
                    },
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
</body>
</html>
