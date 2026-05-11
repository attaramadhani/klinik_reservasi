<?php
session_start();
include '../koneksi.php';

// Proteksi akses Dokter
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$q_dokter = mysqli_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
$id_dokter = mysqli_fetch_assoc($q_dokter)['id_dokter'];

// PROSES SIMPAN HASIL PEMERIKSAAN
if (isset($_POST['simpan_pemeriksaan'])) {
    $id_reservasi   = mysqli_real_escape_string($conn, $_POST['id_reservasi']);
    
    // Sesuaikan variabel dengan nama kolom di database
    $tekanan_darah  = mysqli_real_escape_string($conn, $_POST['tekanan_darah']);
    $suhu_badan     = mysqli_real_escape_string($conn, $_POST['suhu_badan']);
    $berat_badan    = mysqli_real_escape_string($conn, $_POST['berat_badan']);
    $diagnosa       = mysqli_real_escape_string($conn, $_POST['diagnosa']);
    $alergi_obat    = mysqli_real_escape_string($conn, $_POST['alergi_obat']);
    $resep_obat     = mysqli_real_escape_string($conn, $_POST['resep_obat']);
    $tindakan       = mysqli_real_escape_string($conn, $_POST['tindakan']);
    $catatan_dokter = mysqli_real_escape_string($conn, $_POST['catatan_dokter']);

    // 1. Simpan ke tabel hasil_pemeriksaan sesuai struktur gambar
    $q_simpan = "INSERT INTO hasil_pemeriksaan (id_reservasi, tekanan_darah, suhu_badan, berat_badan, diagnosa, alergi_obat, resep_obat, tindakan, catatan_dokter) 
                 VALUES ('$id_reservasi', '$tekanan_darah', '$suhu_badan', '$berat_badan', '$diagnosa', '$alergi_obat', '$resep_obat', '$tindakan', '$catatan_dokter')";
    
    if (mysqli_query($conn, $q_simpan)) {
        // PERBAIKAN: Ubah status reservasi menjadi Menunggu Pembayaran (Bukan Selesai)
        mysqli_query($conn, "UPDATE reservasi SET status = 'Menunggu Pembayaran' WHERE id_reservasi = '$id_reservasi'");
        
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'success', title: 'Berhasil', text: 'Data Pemeriksaan Berhasil Disimpan! Pasien diarahkan ke Kasir.'}).then(() => { window.location='pemeriksaan.php'; }); });</script>";
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>document.addEventListener('DOMContentLoaded', function(){ Swal.fire({icon: 'error', title: 'Gagal', text: 'Gagal menyimpan: " . mysqli_error($conn) . "'}); });</script>";
    }
}

// Ambil data pasien yang terpilih (jika dokter mengklik tombol di daftar kiri)
$pasien_terpilih = null;
if (isset($_GET['periksa'])) {
    $id_res_aktif = mysqli_real_escape_string($conn, $_GET['periksa']);
    $q_aktif = mysqli_query($conn, "SELECT r.*, p.nama_lengkap 
                                    FROM reservasi r 
                                    JOIN pasien p ON r.nik = p.nik 
                                    WHERE r.id_reservasi = '$id_res_aktif'");
    $pasien_terpilih = mysqli_fetch_assoc($q_aktif);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pemeriksaan Pasien - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #0f3d2e; --accent-green: #76c720; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }

        
        .main-content { margin-left: 260px; padding: 30px; }
        
        .patient-list-card { background: white; border-radius: 20px; height: calc(100vh - 80px); overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .patient-item { padding: 15px; border-bottom: 1px solid #eee; transition: 0.2s; cursor: pointer; text-decoration: none; display: block; color: inherit; }
        .patient-item:hover, .patient-item.active { background: #f8f9fa; border-left: 4px solid var(--accent-green); }
        
        .exam-card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); height: calc(100vh - 80px); overflow-y: auto; }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e0e0e0; padding: 12px; }
        .form-control:focus { border-color: var(--accent-green); box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1); }
        .label-custom { font-size: 11px; font-weight: 800; color: var(--primary-green); letter-spacing: 1px; margin-bottom: 8px; text-transform: uppercase; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="row g-4">
        
        <div class="col-md-4">
            <div class="patient-list-card">
                <div class="p-4 border-bottom bg-light sticky-top" style="border-radius: 20px 20px 0 0;">
                    <h6 class="fw-800 mb-0" style="color: var(--primary-green);">Antrian Pemeriksaan</h6>
                </div>
                <div>
                    <?php
                    $q_antrian = mysqli_query($conn, "SELECT r.id_reservasi, r.no_antrian, p.nama_lengkap 
                                                      FROM reservasi r 
                                                      JOIN pasien p ON r.nik = p.nik 
                                                      JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal 
                                                      WHERE j.id_dokter = '$id_dokter' 
                                                      AND r.status IN ('Dikonfirmasi', 'Lunas') 
                                                      ORDER BY r.no_antrian ASC");
                    
                    if(mysqli_num_rows($q_antrian) == 0) {
                        echo "<div class='p-4 text-center text-muted small'>Tidak ada pasien dalam antrian siap periksa.</div>";
                    }

                    while($a = mysqli_fetch_assoc($q_antrian)):
                        $is_active = (isset($_GET['periksa']) && $_GET['periksa'] == $a['id_reservasi']) ? 'active' : '';
                    ?>
                    <a href="?periksa=<?php echo $a['id_reservasi']; ?>" class="patient-item <?php echo $is_active; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($a['nama_lengkap']); ?></div>
                                <small class="text-muted">No. Antrian: #<?php echo $a['no_antrian']; ?></small>
                            </div>
                            <i class="fas fa-chevron-right text-muted opacity-50"></i>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="exam-card">
                <?php if($pasien_terpilih): ?>
                
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                    <div>
                        <h4 class="fw-800 mb-1" style="color: var(--primary-green);">Pemeriksaan Medis</h4>
                        <span class="badge bg-success bg-opacity-10 text-success">Pasien: <?php echo htmlspecialchars($pasien_terpilih['nama_lengkap']); ?></span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">No. Antrian</small>
                        <h3 class="fw-bold mb-0 text-dark">#<?php echo $pasien_terpilih['no_antrian']; ?></h3>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="id_reservasi" value="<?php echo $pasien_terpilih['id_reservasi']; ?>">
                    
                    <div class="mb-4 p-3 rounded-3" style="background: #f8f9fa; border: 1px solid #e0e0e0;">
                        <label class="label-custom"><i class="fas fa-comment-medical me-2"></i>Keluhan Utama (Dari Pasien)</label>
                        <p class="mb-0 text-dark fw-medium"><?php echo htmlspecialchars($pasien_terpilih['keluhan']); ?></p>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="label-custom">Tekanan Darah</label>
                            <input type="text" name="tekanan_darah" class="form-control" placeholder="Contoh: 120/80 mmHg" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="label-custom">Suhu Badan</label>
                            <input type="text" name="suhu_badan" class="form-control" placeholder="Contoh: 36.5 C" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="label-custom">Berat Badan</label>
                            <input type="text" name="berat_badan" class="form-control" placeholder="Contoh: 60 kg" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="label-custom">Diagnosa</label>
                            <input type="text" name="diagnosa" class="form-control" placeholder="Diagnosis penyakit pasien..." required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="label-custom">Alergi Obat <small class="text-muted fw-normal">(Centang cepat)</small></label>
                            <div class="d-flex flex-wrap gap-2 mb-2 p-2 rounded" style="background:#f1f4f3; border: 1px solid #e0e6e4;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="al_tidak" value="Tidak ada" onchange="updateAlergi()" checked>
                                    <label class="form-check-label fw-medium text-success" for="al_tidak">Tidak Ada</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="al_antibiotik" value="Antibiotik" onchange="updateAlergi()">
                                    <label class="form-check-label text-dark" for="al_antibiotik">Antibiotik</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="al_nsaid" value="NSAID/Obat Nyeri" onchange="updateAlergi()">
                                    <label class="form-check-label text-dark" for="al_nsaid">NSAID (Nyeri)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="al_paracetamol" value="Paracetamol" onchange="updateAlergi()">
                                    <label class="form-check-label text-dark" for="al_paracetamol">Paracetamol</label>
                                </div>
                            </div>
                            <input type="text" name="alergi_obat" id="alergi_input" class="form-control" placeholder="Alergi lainnya (bisa ditambahkan manual)..." value="Tidak ada" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <label class="label-custom mb-0">Tindakan Medis</label>
                            <div class="quick-tags">
                                <span class="badge bg-light text-success border border-success" style="cursor:pointer;" onclick="addText('tindakan_input', 'Pemeriksaan fisik standar')">+ Fisik</span>
                                <span class="badge bg-light text-success border border-success" style="cursor:pointer;" onclick="addText('tindakan_input', 'Cek tekanan darah')">+ Tensi</span>
                                <span class="badge bg-light text-success border border-success" style="cursor:pointer;" onclick="addText('tindakan_input', 'Perawatan luka ringan')">+ Rawat Luka</span>
                            </div>
                        </div>
                        <textarea name="tindakan" id="tindakan_input" class="form-control" rows="2" placeholder="Tindakan yang diberikan saat pemeriksaan..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="label-custom">Resep Obat</label>
                        <textarea name="resep_obat" class="form-control" rows="3" placeholder="Tuliskan resep obat untuk pasien..." required></textarea>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <label class="label-custom mb-0">Catatan Dokter / Tindak Lanjut</label>
                            <div class="quick-tags">
                                <span class="badge bg-light text-success border border-success" style="cursor:pointer;" onclick="addText('catatan_input', 'Istirahat cukup 3 hari.')">+ Istirahat</span>
                                <span class="badge bg-light text-success border border-success" style="cursor:pointer;" onclick="addText('catatan_input', 'Banyak minum air putih.')">+ Air Putih</span>
                                <span class="badge bg-light text-success border border-success" style="cursor:pointer;" onclick="addText('catatan_input', 'Rujuk ke dokter spesialis.')">+ Rujuk Spesialis</span>
                            </div>
                        </div>
                        <textarea name="catatan_dokter" id="catatan_input" class="form-control" rows="2" placeholder="Catatan tambahan, anjuran istirahat, atau rujuk..." required></textarea>
                    </div>

                    <button type="submit" name="simpan_pemeriksaan" class="btn w-100 py-3 fw-bold rounded-3 shadow-sm text-white" style="background: var(--primary-green);">
                        <i class="fas fa-save me-2"></i> SIMPAN HASIL PEMERIKSAAN
                    </button>
                </form>

                <?php else: ?>
                <div class="d-flex flex-column align-items-center justify-content-center h-100 opacity-50">
                    <i class="fas fa-user-md fa-4x mb-3 text-success"></i>
                    <h5 class="fw-bold">Pilih Pasien</h5>
                    <p class="text-center small">Silakan pilih nama pasien di daftar antrian sebelah kiri untuk memulai pengisian rekam medis.</p>
                </div>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JS for Checkbox Alergi
    function updateAlergi() {
        const c_tidak = document.getElementById('al_tidak');
        const checkboxes = document.querySelectorAll('.form-check-input[id^="al_"]:not(#al_tidak)');
        const alergi_input = document.getElementById('alergi_input');
        
        // Auto uncheck 'Tidak Ada' if other allergies are checked
        if (event && event.target.id !== 'al_tidak' && event.target.checked) {
            c_tidak.checked = false;
        }
        
        let selected = [];
        if (c_tidak.checked) {
            // If user checks 'Tidak Ada', clear others
            if (event && event.target.id === 'al_tidak') {
                checkboxes.forEach(chk => chk.checked = false);
            }
            selected.push('Tidak ada');
        } else {
            checkboxes.forEach(chk => {
                if(chk.checked) selected.push(chk.value);
            });
        }
        
        alergi_input.value = selected.join(', ');
    }

    // JS for Quick Action Tags
    function addText(elementId, text) {
        const el = document.getElementById(elementId);
        if(el.value.length > 0 && !el.value.endsWith(' ') && !el.value.endsWith(', ')) {
            el.value += ', ';
        }
        el.value += text;
    }
</script>
</body>
</html>