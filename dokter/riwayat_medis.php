<?php
session_start();
include '../koneksi.php';

// Proteksi akses Dokter
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$q_dokter = db_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
$id_dokter = db_fetch_assoc($q_dokter)['id_dokter'];

// Ambil semua data pasien yang pernah diperiksa oleh dokter ini atau semua pasien
// Untuk rekam medis, dokter sebaiknya bisa mencari semua pasien
$search = isset($_GET['search']) ? db_real_escape_string($conn, $_GET['search']) : '';

$query_pasien = "SELECT DISTINCT p.* 
                 FROM pasien p 
                 JOIN reservasi r ON p.nik = r.nik 
                 JOIN hasil_pemeriksaan h ON r.id_reservasi = h.id_reservasi";

if ($search != '') {
    $query_pasien .= " WHERE p.nama_lengkap LIKE '%$search%' OR p.nik LIKE '%$search%'";
}

$query_pasien .= " ORDER BY p.nama_lengkap ASC";
$result_pasien = db_query($conn, $query_pasien);

// Jika ada pasien yang dipilih untuk dilihat riwayatnya
$pasien_terpilih = null;
$riwayat_medis = null;

if (isset($_GET['nik'])) {
    $nik_pasien_aktif = db_real_escape_string($conn, $_GET['nik']);
    
    // Ambil detail pasien
    $q_detail = db_query($conn, "SELECT * FROM pasien WHERE nik = '$nik_pasien_aktif'");
    $pasien_terpilih = db_fetch_assoc($q_detail);
    
    // Ambil histori pemeriksaannya, urutkan dari yang terbaru
    $q_riwayat = "SELECT h.*, r.tanggal_kunjungan, d.nama_dokter, d.spesialisasi, j.id_dokter 
                  FROM hasil_pemeriksaan h 
                  JOIN reservasi r ON h.id_reservasi = r.id_reservasi 
                  JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal
                  JOIN dokter d ON j.id_dokter = d.id_dokter
                  WHERE r.nik = '$nik_pasien_aktif' AND r.status = 'Selesai'
                  ORDER BY r.tanggal_kunjungan DESC";
    $riwayat_medis = db_query($conn, $q_riwayat);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Rekam Medis - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #0f3d2e; --accent-green: #76c720; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .main-content { margin-left: 260px; padding: 30px; }
        
        .patient-list-card { background: white; border-radius: 20px; height: calc(100vh - 80px); overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .patient-item { padding: 15px; border-bottom: 1px solid #eee; transition: 0.2s; cursor: pointer; text-decoration: none; display: block; color: inherit; }
        .patient-item:hover, .patient-item.active { background: #f8f9fa; border-left: 4px solid var(--accent-green); }
        
        .history-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); height: calc(100vh - 80px); overflow-y: auto; }
        
        .timeline-item { border-left: 3px solid var(--accent-green); padding-left: 20px; position: relative; margin-bottom: 30px; }
        .timeline-item::before { content: ''; position: absolute; left: -10px; top: 0; width: 17px; height: 17px; border-radius: 50%; background: var(--accent-green); border: 4px solid white; box-shadow: 0 0 0 2px var(--accent-green); }
        
        .label-custom { font-size: 11px; font-weight: 800; color: #6c757d; letter-spacing: 1px; margin-bottom: 5px; text-transform: uppercase; }
        .data-value { font-weight: 600; color: #212529; font-size: 15px; }
        .box-info { background: #f8f9fa; border-radius: 12px; padding: 15px; border: 1px solid #eee; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="row g-4">
        
        <div class="col-md-4">
            <div class="patient-list-card">
                <div class="p-4 border-bottom bg-light sticky-top" style="border-radius: 20px 20px 0 0; z-index: 10;">
                    <h6 class="fw-800 mb-3" style="color: var(--primary-green);">Daftar Pasien Terdaftar</h6>
                    
                    <form action="" method="GET">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Cari nama pasien..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-outline-success" type="submit">Cari</button>
                        </div>
                    </form>
                </div>
                
                <div>
                    <?php
                    if(db_num_rows($result_pasien) == 0) {
                        echo "<div class='p-4 text-center text-muted small'>Tidak ada pasien ditemukan.</div>";
                    }

                    while($p = db_fetch_assoc($result_pasien)):
                        $is_active = (isset($_GET['nik']) && $_GET['nik'] == $p['nik']) ? 'active' : '';
                    ?>
                    <a href="?nik=<?php echo $p['nik']; ?>&search=<?php echo urlencode($search); ?>" class="patient-item <?php echo $is_active; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['nama_lengkap']); ?></div>
                                <small class="text-muted"><i class="fas fa-id-card me-1"></i> NIK: <?php echo $p['nik']; ?></small>
                            </div>
                            <i class="fas fa-chevron-right text-muted opacity-50"></i>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="history-card">
                <?php if($pasien_terpilih): ?>
                
                <div class="d-flex justify-content-between align-items-md-center flex-column flex-md-row mb-4 border-bottom pb-3">
                    <div>
                        <h4 class="fw-800 mb-1" style="color: var(--primary-green);">Riwayat Medis Pasien</h4>
                        <span class="badge bg-success bg-opacity-10 text-success fs-6 mt-2"><i class="fas fa-user-injured me-2"></i><?php echo htmlspecialchars($pasien_terpilih['nama_lengkap']); ?></span>
                    </div>
                </div>

                <div class="row mb-5 box-info mx-0">
                    <div class="col-md-3 col-6 mb-3 mb-md-0">
                        <div class="label-custom">Jenis Kelamin</div>
                        <div class="data-value"><?php echo $pasien_terpilih['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></div>
                    </div>
                    <div class="col-md-3 col-6 mb-3 mb-md-0">
                        <div class="label-custom">Tgl Lahir</div>
                        <div class="data-value"><?php echo date('d M Y', strtotime($pasien_terpilih['tanggal_lahir'])); ?></div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="label-custom">No. Telepon</div>
                        <div class="data-value"><?php echo htmlspecialchars($pasien_terpilih['no_hp']); ?></div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="label-custom">NIK</div>
                        <div class="data-value"><?php echo htmlspecialchars($pasien_terpilih['nik']); ?></div>
                    </div>
                </div>

                <h5 class="fw-bold mb-4"><i class="fas fa-history me-2 text-success"></i> Histori Kunjungan</h5>

                <?php 
                if ($riwayat_medis && db_num_rows($riwayat_medis) > 0) {
                    while($h = db_fetch_assoc($riwayat_medis)) {
                ?>
                
                <div class="timeline-item">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center">
                            <h5 class="fw-bold text-dark mb-0 me-3"><?php echo date('d F Y', strtotime($h['tanggal_kunjungan'])); ?></h5>
                            <span class="badge bg-light text-dark border"><i class="fas fa-user-md me-1 text-success"></i> <?php echo htmlspecialchars($h['nama_dokter']); ?></span>
                        </div>
                        <?php if($h['id_dokter'] == $id_dokter): ?>
                        <a href="edit_pemeriksaan.php?id=<?php echo $h['id_reservasi']; ?>" class="btn btn-sm btn-outline-warning rounded-pill px-3 shadow-sm border-2" style="position: relative; z-index: 5;">
                            <i class="fas fa-edit me-1"></i> Edit Mode
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card border-0 bg-light rounded-4 p-4 mb-4">
                        <div class="row g-3">
                            <!-- Tanda Vital -->
                            <div class="col-12 mb-2 border-bottom pb-2">
                                <span class="fw-bold text-success"><i class="fas fa-heartbeat me-2"></i>Tanda Vital</span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Tekanan Darah</small>
                                <span class="fw-medium"><?php echo htmlspecialchars($h['tekanan_darah']); ?></span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Suhu Badan</small>
                                <span class="fw-medium"><?php echo htmlspecialchars($h['suhu_badan']); ?></span>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Berat Badan</small>
                                <span class="fw-medium"><?php echo htmlspecialchars($h['berat_badan']); ?></span>
                            </div>
                            
                            <!-- Diagnosa & Alergi -->
                            <div class="col-12 mt-4 mb-2 border-bottom pb-2">
                                <span class="fw-bold text-primary"><i class="fas fa-stethoscope me-2"></i>Pemeriksaan & Tindakan</span>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Diagnosa Klinis</small>
                                <span class="fw-bold text-dark"><?php echo nl2br(htmlspecialchars($h['diagnosa'])); ?></span>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-danger d-block text-uppercase fw-bold" style="font-size: 10px;">Alergi Obat</small>
                                <span class="fw-bold text-danger"><?php echo htmlspecialchars($h['alergi_obat']); ?></span>
                            </div>
                            <div class="col-12 mb-2">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Tindakan Medis</small>
                                <span class="fw-medium"><?php echo nl2br(htmlspecialchars($h['tindakan'])); ?></span>
                            </div>
                            
                            <!-- Resep & Catatan -->
                            <div class="col-12 mt-4 mb-2 border-bottom pb-2">
                                <span class="fw-bold text-warning"><i class="fas fa-pills me-2"></i>Perawatan</span>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Resep Obat</small>
                                <div class="bg-white p-2 rounded border-start border-3 border-warning mt-1">
                                    <span class="fw-medium small"><?php echo nl2br(htmlspecialchars($h['resep_obat'])); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 10px;">Catatan Dokter</small>
                                <span class="fw-medium small"><?php echo nl2br(htmlspecialchars($h['catatan_dokter'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php 
                    }
                } else {
                    echo "<div class='text-center p-5 bg-light rounded-4 border'>
                            <i class='fas fa-folder-open fa-3x text-muted mb-3 opacity-50'></i>
                            <h6 class='text-muted'>Belum ada histori rekam medis.</h6>
                            <p class='small text-muted mb-0'>Pasien ini belum memiliki catatan pemeriksaan medis yang telah diselesaikan.</p>
                          </div>";
                }
                ?>

                <?php else: ?>
                <div class="d-flex flex-column align-items-center justify-content-center h-100 opacity-50">
                    <i class="fas fa-notes-medical fa-4x mb-3 text-success"></i>
                    <h5 class="fw-bold">Pencarian Rekam Medis</h5>
                    <p class="text-center small">Pilih nama pasien dari daftar di sebelah kiri atau gunakan kotak pencarian untuk melihat seluruh rekam medis pasien tersebut secara lengkap.</p>
                </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
