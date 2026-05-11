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
$nik_pasien_login = $d_pasien['nik'];

// Baca id_jadwal dari URL (jika datang dari halaman jadwal)
$prefill_jadwal = isset($_GET['id_jadwal']) ? intval($_GET['id_jadwal']) : 0;
$prefill_tanggal = '';
$prefill_info = null;

if ($prefill_jadwal > 0) {
    $q_pf = mysqli_query($conn, "SELECT j.*, d.nama_dokter, d.spesialisasi 
                                 FROM jadwal_dokter j 
                                 JOIN dokter d ON j.id_dokter = d.id_dokter 
                                 WHERE j.id_jadwal = '$prefill_jadwal'");
    $prefill_info = mysqli_fetch_assoc($q_pf);

    if ($prefill_info) {
        // Cari tanggal terdekat yang sesuai dengan hari praktek dokter
        $hari_map = ['Minggu'=>0,'Senin'=>1,'Selasa'=>2,'Rabu'=>3,'Kamis'=>4,'Jumat'=>5,'Sabtu'=>6];
        $hari_target = $hari_map[$prefill_info['hari']];
        $today = new DateTime();
        $today_dow = (int)$today->format('w'); // 0=Sun, 1=Mon, ...
        $diff = ($hari_target - $today_dow + 7) % 7;
        if ($diff === 0) $diff = 7; // Jika hari ini sama, ambil minggu depan
        $next_date = clone $today;
        $next_date->modify("+{$diff} days");
        $prefill_tanggal = $next_date->format('Y-m-d');
    }
}

$pesan_error = "";
$reservasi_berhasil = false;
$no_antrian_baru = 0;

if (isset($_POST['buat_reservasi'])) {
    $id_jadwal = mysqli_real_escape_string($conn, $_POST['id_jadwal']);
    $tanggal   = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $keluhan   = htmlspecialchars($_POST['keluhan']);

    // 1. Validasi Hari
    $nama_hari_inggris = date('l', strtotime($tanggal));
    $hari_indo = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
    ];
    $hari_pilihan = $hari_indo[$nama_hari_inggris];

    // Ambil data jadwal beserta kuota maksimalnya
    $cek_jadwal = mysqli_query($conn, "SELECT hari, kuota FROM jadwal_dokter WHERE id_jadwal = '$id_jadwal'");
    $data_jadwal = mysqli_fetch_assoc($cek_jadwal);

    if ($data_jadwal['hari'] != $hari_pilihan) {
        $pesan_error = "Dokter tidak praktek pada hari <b>$hari_pilihan</b>. <br>Jadwal dokter ini: <b>" . $data_jadwal['hari'] . "</b>.";
    } else {
        // 2. CEK RESERVASI AKTIF: Mencegah pasien mendaftar jika masih punya reservasi berjalan
        $cek_aktif = mysqli_query($conn, "SELECT id_reservasi FROM reservasi 
                                          WHERE nik = '$nik_pasien_login' 
                                          AND status IN ('Menunggu', 'Dikonfirmasi', 'Menunggu Pembayaran')");
        
        if (mysqli_num_rows($cek_aktif) > 0) {
            $pesan_error = "Anda <b>sudah memiliki reservasi aktif</b> yang belum selesai. Silakan batalkan tiket lama di menu Riwayat jika ingin mendaftar ulang.";
        } else {
            // 3. CEK KUOTA: Hitung berapa pasien yang sudah mendaftar di tanggal & jadwal ini
            $cek_jumlah_pasien = mysqli_query($conn, "SELECT COUNT(id_reservasi) as total_terdaftar 
                                                      FROM reservasi 
                                                      WHERE id_jadwal = '$id_jadwal' 
                                                      AND tanggal_kunjungan = '$tanggal' 
                                                      AND status != 'Ditolak'");
            $data_terdaftar = mysqli_fetch_assoc($cek_jumlah_pasien);
            $total_terdaftar = $data_terdaftar['total_terdaftar'];
            $kuota_maksimal = $data_jadwal['kuota'];

            // Jika jumlah pendaftar sudah sama atau melebihi kuota
            if ($total_terdaftar >= $kuota_maksimal) {
                $pesan_error = "Mohon maaf, <b>Jadwal Penuh</b>. <br>Kuota harian dokter ini sudah mencapai batas maksimal ($kuota_maksimal pasien). Silakan pilih tanggal atau jadwal lain.";
            } else {
                // 4. Lolos Validasi Kuota -> Generate Nomor Antrian
                $cek_antrian = mysqli_query($conn, "SELECT MAX(no_antrian) as antrian_terakhir FROM reservasi WHERE id_jadwal = '$id_jadwal' AND tanggal_kunjungan = '$tanggal'");
                $data_antrian = mysqli_fetch_assoc($cek_antrian);
                $no_antrian_baru = (int)$data_antrian['antrian_terakhir'] + 1; // Pastikan konversi ke integer

                // 5. Simpan ke Database
                $query_simpan = "INSERT INTO reservasi (nik, id_jadwal, tanggal_kunjungan, keluhan, no_antrian, status) 
                                 VALUES ('$nik_pasien_login', '$id_jadwal', '$tanggal', '$keluhan', '$no_antrian_baru', 'Menunggu')";
                
                if (mysqli_query($conn, $query_simpan)) {
                    $reservasi_berhasil = true;
                } else {
                    $pesan_error = "Gagal membuat reservasi: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Reservasi - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; }
        .navbar { background: #0f3d2e; }
        .main-container { margin-top: 100px; margin-bottom: 50px; }
        .card-reservasi { border: none; border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); overflow: hidden; }
        .bg-side { background: linear-gradient(135deg, #0f3d2e 0%, #1a5c43 100%); color: white; padding: 40px; }
        .doctor-img { width: 220px; height: 220px; object-fit: cover; border: 8px solid rgba(255,255,255,0.1); border-radius: 40px; transform: rotate(-3deg); transition: 0.3s; }
        .doctor-img:hover { transform: rotate(0); }
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e0e0e0; transition: 0.3s; padding: 12px 15px; }
        .form-control:focus, .form-select:focus { border-color: #76c720; box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1); }
        .btn-submit { background-color: #76c720; color: #0f3d2e; padding: 15px; font-weight: 800; border-radius: 16px; width: 100%; transition: 0.3s; border: none; text-transform: uppercase; letter-spacing: 1px;}
        .btn-submit:hover { background-color: #0f3d2e; color: white; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(15, 61, 46, 0.15); }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark fixed-top shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php"><i class="fas fa-heartbeat me-2 text-success"></i>CLINIQ</a>
            <a href="index.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-bold"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
        </div>
    </nav>

    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card card-reservasi">
                    <div class="row g-0">
                        <div class="col-md-7 p-4 p-lg-5 bg-white">
                            <h2 class="fw-800 mb-1" style="color: #0f3d2e;">Buat Reservasi Baru</h2>
                            <p class="text-muted small mb-4">Silakan pilih dokter dan jadwalkan kunjungan Anda.</p>

                            <?php if ($prefill_info): ?>
                            <div class="mb-4 p-3 d-flex align-items-center gap-3" style="background:linear-gradient(135deg,#f0f9eb,#e8f5e0);border-left:5px solid #76c720;border-radius:14px;">
                                <div style="background:#76c720;color:#0f3d2e;width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div>
                                    <div style="font-size:11px;font-weight:800;letter-spacing:1px;color:#76c720;">JADWAL DIPILIH OTOMATIS</div>
                                    <div class="fw-bold" style="color:#0f3d2e;font-size:15px;"><?php echo htmlspecialchars($prefill_info['nama_dokter']); ?></div>
                                    <div class="text-muted" style="font-size:12px;">
                                        <?php echo htmlspecialchars($prefill_info['spesialisasi']); ?> &bull;
                                        <?php echo $prefill_info['hari']; ?> |
                                        <?php echo date('H:i', strtotime($prefill_info['jam_mulai'])); ?> &ndash; <?php echo date('H:i', strtotime($prefill_info['jam_selesai'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small" style="letter-spacing: 1px;">PILIH JADWAL DOKTER</label>
                                    <select name="id_jadwal" id="id_jadwal" class="form-select" required>
                                        <option value="">-- Pilih Dokter & Hari --</option>
                                        <?php
                                        $query = "SELECT j.*, d.nama_dokter, d.spesialisasi 
                                                  FROM jadwal_dokter j 
                                                  JOIN dokter d ON j.id_dokter = d.id_dokter 
                                                  ORDER BY d.nama_dokter ASC";
                                        $result = mysqli_query($conn, $query);
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            $jam = date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai']));
                                            $selected = ($prefill_jadwal == $row['id_jadwal']) ? 'selected' : '';
                                            echo "<option value='{$row['id_jadwal']}' $selected>{$row['nama_dokter']} ({$row['spesialisasi']}) - {$row['hari']} | $jam (Maks. {$row['kuota']} Pasien)</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted small" style="letter-spacing: 1px;">TANGGAL KUNJUNGAN</label>
                                    <input type="date" name="tanggal" id="tanggal" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo $prefill_tanggal; ?>">
                                    <?php if ($prefill_tanggal): ?>
                                    <small class="text-success fst-italic mt-1 d-block"><i class="fas fa-check-circle me-1"></i>Tanggal otomatis diisi ke hari <?php echo $prefill_info['hari']; ?> terdekat. Ubah jika perlu.</small>
                                    <?php else: ?>
                                    <small class="text-danger fst-italic mt-1 d-block">*Pastikan hari sesuai dengan jadwal dokter yang Anda pilih di atas.</small>
                                    <?php endif; ?>
                                </div>

                                <div id="info-kuota" class="mb-3" style="display: none;"></div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-muted small" style="letter-spacing: 1px;">KELUHAN MEDIS</label>
                                    <textarea name="keluhan" class="form-control" rows="3" placeholder="Ceritakan gejala yang Anda rasakan (Contoh: Demam sejak 2 hari yang lalu...)" required></textarea>
                                </div>

                                <button type="submit" name="buat_reservasi" class="btn btn-submit">
                                    BUAT JADWAL KUNJUNGAN
                                </button>
                            </form>
                        </div>

                        <div class="col-md-5 bg-side text-center d-none d-md-flex flex-column align-items-center justify-content-center">
                            <img src="https://img.freepik.com/free-photo/pleased-young-female-doctor-wearing-medical-robe-stethoscope-around-neck-standing-with-closed-posture_409827-254.jpg" 
                                 class="doctor-img mb-4" alt="Dokter Cliniq">
                            <h4 class="fw-800 text-white mb-1">Pelayanan Profesional</h4>
                            <p class="small opacity-75 px-4">Kami membatasi jumlah pasien harian agar setiap konsultasi berjalan maksimal.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        <?php if($pesan_error != "") { ?>
            Swal.fire({
                icon: 'error',
                title: 'Reservasi Ditolak',
                html: '<?php echo $pesan_error; ?>',
                confirmButtonColor: '#0f3d2e',
                customClass: { popup: 'rounded-4' }
            });
        <?php } ?>

        <?php if($reservasi_berhasil) { ?>
            Swal.fire({
                icon: 'success',
                title: 'Antrian Berhasil Dibuat!',
                html: 'Nomor Antrian Anda: <br><b style="font-size: 40px; color: #76c720;">#<?php echo $no_antrian_baru; ?></b><br><br><small>Menunggu konfirmasi dokter. Silakan pantau di menu Riwayat.</small>',
                confirmButtonText: 'Lihat Tiket Antrian',
                confirmButtonColor: '#0f3d2e',
                customClass: { popup: 'rounded-4' }
            }).then(() => {
                window.location = 'riwayat.php';
            });
        <?php } ?>

        // Fitur Cek Sisa Kuota via AJAX
        document.addEventListener('DOMContentLoaded', function() {
            const selectJadwal = document.getElementById('id_jadwal');
            const inputTanggal = document.getElementById('tanggal');
            const infoKuota = document.getElementById('info-kuota');
            const btnSubmit = document.querySelector('.btn-submit');

            function cekKuota() {
                const idJadwal = selectJadwal.value;
                const tanggal = inputTanggal.value;

                if (idJadwal && tanggal) {
                    infoKuota.style.display = 'block';
                    infoKuota.innerHTML = '<div class="text-muted small"><i class="fas fa-spinner fa-spin"></i> Mengecek sisa kuota...</div>';
                    
                    const formData = new FormData();
                    formData.append('id_jadwal', idJadwal);
                    formData.append('tanggal', tanggal);

                    fetch('cek_kuota.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            let badgeClass = data.sisa_kuota > 5 ? 'bg-success' : (data.sisa_kuota > 0 ? 'bg-warning' : 'bg-danger');
                            let textMuted = `<span class="text-muted small d-block mt-1">Maksimal ${data.kuota_maksimal} pasien. Sudah terdaftar: ${data.terdaftar} orang.</span>`;
                            
                            infoKuota.innerHTML = `
                                <div class="p-3 rounded-3" style="background: rgba(0,0,0,0.03); border: 1px solid #e0e0e0;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold" style="color: #0f3d2e; font-size: 14px;">Ketersediaan Slot</span>
                                        <span class="badge ${badgeClass} fs-6 rounded-pill px-3">${data.sisa_kuota} Tersisa</span>
                                    </div>
                                    ${textMuted}
                                </div>
                            `;

                            if (data.sisa_kuota === 0) {
                                btnSubmit.disabled = true;
                                btnSubmit.innerHTML = 'KUOTA HABIS';
                                btnSubmit.style.backgroundColor = '#e74c3c';
                                btnSubmit.style.color = '#fff';
                            } else {
                                btnSubmit.disabled = false;
                                btnSubmit.innerHTML = 'BUAT JADWAL KUNJUNGAN';
                                btnSubmit.style.backgroundColor = '';
                                btnSubmit.style.color = '';
                            }
                        } else {
                            infoKuota.innerHTML = `
                                <div class="p-2 rounded text-danger small bg-danger bg-opacity-10 border border-danger border-opacity-25">
                                    <i class="fas fa-exclamation-circle me-1"></i> ${data.pesan}
                                </div>
                            `;
                            btnSubmit.disabled = true;
                        }
                    })
                    .catch(error => {
                        infoKuota.innerHTML = '<div class="text-danger small">Terjadi kesalahan saat mengecek kuota.</div>';
                    });
                } else {
                    infoKuota.style.display = 'none';
                    btnSubmit.disabled = false;
                }
            }

            selectJadwal.addEventListener('change', cekKuota);
            inputTanggal.addEventListener('change', cekKuota);

            // Cek langsung jika prefill sudah ada
            if (selectJadwal.value && inputTanggal.value) {
                cekKuota();
            }
        });
    </script>
</body>
</html>