<?php
session_start();
include '../koneksi.php';

// Cek Login Pasien
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$q_pasien = mysqli_query($conn, "SELECT nik FROM pasien WHERE id_user = '$id_user'");
$d_pasien = mysqli_fetch_assoc($q_pasien);
$nik_pasien = $d_pasien['nik'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Periksa - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f7f6; font-family: 'Plus Jakarta Sans', sans-serif; }
        .navbar { background: linear-gradient(to right, #0f3d2e, #155724); }
        .header-green { background: #0f3d2e; color: white; padding: 60px 0 40px 0; border-radius: 0 0 40px 40px; }
        .result-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: 0.3s; }
        .result-card:hover { transform: translateY(-5px); }
        .icon-circle { width: 50px; height: 50px; background: #e9f7ef; color: #155724; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .mt-minus { margin-top: -30px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-heartbeat me-2"></i>Cliniq</a>
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Dashboard</a>
        </div>
    </nav>

    <div class="header-green text-center">
        <div class="container">
            <h2 class="fw-bold">Riwayat Medis Anda</h2>
            <p class="opacity-75">Diagnosa resmi dan resep obat (Hanya tampil jika tagihan telah lunas)</p>
        </div>
    </div>

    <div class="container mt-minus py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php
                $query = "SELECT h.*, r.tanggal_kunjungan, d.nama_dokter, d.spesialisasi 
                          FROM hasil_pemeriksaan h 
                          JOIN reservasi r ON h.id_reservasi = r.id_reservasi 
                          JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal 
                          JOIN dokter d ON j.id_dokter = d.id_dokter 
                          WHERE r.nik = '$nik_pasien' AND r.status = 'Selesai'
                          ORDER BY r.tanggal_kunjungan DESC"; 
                
                $q = mysqli_query($conn, $query);

                if(mysqli_num_rows($q) == 0) {
                    echo "<div class='card result-card p-5 text-center'>
                            <i class='fas fa-file-medical fa-3x text-muted mb-3'></i>
                            <h5 class='text-muted'>Belum ada riwayat medis yang tersedia.</h5>
                            <p class='small text-muted'>Hasil pemeriksaan dan resep obat akan muncul di sini <br>setelah Anda <b>menyelesaikan pembayaran di Kasir</b>.</p>
                          </div>";
                }

                while($h = mysqli_fetch_assoc($q)) {
                ?>
                <div class="card result-card p-4 mb-4 bg-white">
                    <div class="row align-items-center">
                        <div class="col-md-3 border-end">
                            <div class="d-flex align-items-center mb-3">
                                <div class="icon-circle me-3"><i class="fas fa-calendar-day"></i></div>
                                <div>
                                    <small class="text-muted d-block">TANGGAL</small>
                                    <h6 class="mb-0 fw-bold"><?php echo date('d M Y', strtotime($h['tanggal_kunjungan'])); ?></h6>
                                </div>
                            </div>
                            <small class="text-muted d-block">DOKTER PEMERIKSA</small>
                            <p class="fw-bold mb-0 text-success"><?php echo $h['nama_dokter']; ?></p>
                            <small class="text-muted"><?php echo $h['spesialisasi']; ?></small>
                        </div>

                        <div class="col-md-6 px-md-4 mt-3 mt-md-0">
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase"><i class="fas fa-notes-medical me-1"></i> Diagnosa:</label>
                                <p class="mb-0 text-dark fw-bold"><?php echo nl2br(htmlspecialchars($h['diagnosa'])); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase"><i class="fas fa-exclamation-triangle me-1"></i> Alergi Obat:</label>
                                <p class="mb-0 text-danger fw-bold"><?php echo htmlspecialchars($h['alergi_obat']); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="small text-muted fw-bold text-uppercase"><i class="fas fa-procedures me-1"></i> Tindakan Medis:</label>
                                <p class="mb-0 text-dark fw-medium"><?php echo nl2br(htmlspecialchars($h['tindakan'])); ?></p>
                            </div>
                            <div>
                                <label class="small text-muted fw-bold text-uppercase"><i class="fas fa-pills me-1"></i> Resep Obat:</label>
                                <div class="p-2 bg-light rounded border-start border-3 border-success">
                                    <p class="mb-0 text-dark fw-medium small"><?php echo nl2br(htmlspecialchars($h['resep_obat'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3 text-md-end mt-3 mt-md-0">
                            <a href="cetak_hasil.php?id=<?php echo $h['id_reservasi']; ?>" target="_blank" class="btn btn-outline-success rounded-pill px-4 shadow-sm w-100 mb-2 d-block">
                                <i class="fas fa-print me-2"></i> Cetak Hasil
                            </a>
                            <small class="text-muted d-block">ID Periksa: #RES-<?php echo $h['id_reservasi']; ?></small>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>