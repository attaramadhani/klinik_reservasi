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
$nik_pasien_login = $d_pasien['nik'];

// Baca id_jadwal dari URL (jika datang dari halaman jadwal)
$prefill_jadwal = isset($_GET['id_jadwal']) ? intval($_GET['id_jadwal']) : 0;
$prefill_tanggal = '';
$prefill_info = null;

if ($prefill_jadwal > 0) {
    $q_pf = db_query($conn, "SELECT j.*, d.nama_dokter, d.spesialisasi 
                                 FROM jadwal_dokter j 
                                 JOIN dokter d ON j.id_dokter = d.id_dokter 
                                 WHERE j.id_jadwal = '$prefill_jadwal'");
    $prefill_info = db_fetch_assoc($q_pf);

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
    $id_jadwal = db_real_escape_string($conn, $_POST['id_jadwal']);
    $tanggal   = db_real_escape_string($conn, $_POST['tanggal']);
    $keluhan   = htmlspecialchars($_POST['keluhan']);

    // 1. Validasi Hari
    $nama_hari_inggris = date('l', strtotime($tanggal));
    $hari_indo = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
    ];
    $hari_pilihan = $hari_indo[$nama_hari_inggris];

    // Ambil data jadwal beserta kuota maksimalnya
    $cek_jadwal = db_query($conn, "SELECT hari, kuota FROM jadwal_dokter WHERE id_jadwal = '$id_jadwal'");
    $data_jadwal = db_fetch_assoc($cek_jadwal);

    if ($data_jadwal['hari'] != $hari_pilihan) {
        $pesan_error = "Dokter tidak praktek pada hari <b>$hari_pilihan</b>. <br>Jadwal dokter ini: <b>" . $data_jadwal['hari'] . "</b>.";
    } else {
        // 2. CEK RESERVASI AKTIF: Mencegah pasien mendaftar jika masih punya reservasi berjalan
        $cek_aktif = db_query($conn, "SELECT id_reservasi FROM reservasi 
                                          WHERE nik = '$nik_pasien_login' 
                                          AND status IN ('Menunggu', 'Dikonfirmasi', 'Menunggu Pembayaran')");
        
        if (db_num_rows($cek_aktif) > 0) {
            $pesan_error = "Anda <b>sudah memiliki reservasi aktif</b> yang belum selesai. Silakan batalkan tiket lama di menu Riwayat jika ingin mendaftar ulang.";
        } else {
            // 3. CEK KUOTA: Hitung berapa pasien yang sudah mendaftar di tanggal & jadwal ini
            $cek_jumlah_pasien = db_query($conn, "SELECT COUNT(id_reservasi) as total_terdaftar 
                                                      FROM reservasi 
                                                      WHERE id_jadwal = '$id_jadwal' 
                                                      AND tanggal_kunjungan = '$tanggal' 
                                                      AND status != 'Ditolak'");
            $data_terdaftar = db_fetch_assoc($cek_jumlah_pasien);
            $total_terdaftar = $data_terdaftar['total_terdaftar'];
            $kuota_maksimal = $data_jadwal['kuota'];

            // Jika jumlah pendaftar sudah sama atau melebihi kuota
            if ($total_terdaftar >= $kuota_maksimal) {
                $pesan_error = "Mohon maaf, <b>Jadwal Penuh</b>. <br>Kuota harian dokter ini sudah mencapai batas maksimal ($kuota_maksimal pasien). Silakan pilih tanggal atau jadwal lain.";
            } else {
                // 4. Lolos Validasi Kuota -> Generate Nomor Antrian
                $cek_antrian = db_query($conn, "SELECT MAX(no_antrian) as antrian_terakhir FROM reservasi WHERE id_jadwal = '$id_jadwal' AND tanggal_kunjungan = '$tanggal'");
                $data_antrian = db_fetch_assoc($cek_antrian);
                $no_antrian_baru = (int)$data_antrian['antrian_terakhir'] + 1; // Pastikan konversi ke integer

                // 5. Simpan ke Database
                $query_simpan = "INSERT INTO reservasi (nik, id_jadwal, tanggal_kunjungan, keluhan, no_antrian, status) 
                                 VALUES ('$nik_pasien_login', '$id_jadwal', '$tanggal', '$keluhan', '$no_antrian_baru', 'Menunggu')";
                
                if (db_query($conn, $query_simpan)) {
                    $reservasi_berhasil = true;
                } else {
                    $pesan_error = "Gagal membuat reservasi: " . db_error($conn);
                }
            }
        }
    }
}

// Ambil semua jadwal untuk dropdown modal
$query_schedules = "SELECT j.*, d.nama_dokter, d.spesialisasi 
                    FROM jadwal_dokter j 
                    JOIN dokter d ON j.id_dokter = d.id_dokter 
                    ORDER BY d.nama_dokter ASC, CASE j.hari 
                        WHEN 'Senin' THEN 1 
                        WHEN 'Selasa' THEN 2 
                        WHEN 'Rabu' THEN 3 
                        WHEN 'Kamis' THEN 4 
                        WHEN 'Jumat' THEN 5 
                        WHEN 'Sabtu' THEN 6 
                        WHEN 'Minggu' THEN 7 
                        ELSE 8
                    END";
$res_schedules = db_query($conn, $query_schedules);
$all_schedules = [];
$polis = [];
while($row = db_fetch_assoc($res_schedules)) {
    // Format spesialisasi menjadi nama Poli yang mudah dipahami
    $spesialisasi = $row['spesialisasi'];
    $poli = (strpos(strtolower($spesialisasi), 'dokter') === false) ? 'Poli ' . $spesialisasi : str_ireplace('dokter', 'poli', $spesialisasi);
    $row['poli'] = $poli;
    $all_schedules[] = $row;
    
    if (!in_array($poli, $polis)) {
        $polis[] = $poli;
    }
}
sort($polis);
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
        .form-control, .form-select { border-radius: 12px; border: 1px solid #e0e0e0; transition: 0.3s; padding: 12px 15px; }
        .form-control:focus, .form-select:focus { border-color: #76c720; box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1); }
        .btn-submit { background-color: #76c720; color: #0f3d2e; padding: 15px; font-weight: 800; border-radius: 16px; width: 100%; transition: 0.3s; border: none; text-transform: uppercase; letter-spacing: 1px;}
        .btn-submit:hover { background-color: #0f3d2e; color: white; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(15, 61, 46, 0.15); }
        
        .custom-select-trigger:hover {
            border-color: #76c720 !important;
            box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.05);
        }
        .schedule-option-item {
            border: 2px solid #eef2f0 !important;
            background: #fafbfc;
            transition: all 0.2s ease;
        }
        .schedule-option-item:hover {
            border-color: #76c720 !important;
            background: #ffffff;
            transform: translateY(-2px);
        }
        .schedule-option-item.active {
            border-color: #0f3d2e !important;
            background: #f4faf7;
        }
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
            <div class="col-lg-6 col-md-8">
                <div class="card card-reservasi p-4 p-lg-5 bg-white">
                    <h2 class="fw-800 mb-1" style="color: #0f3d2e; font-size: 24px;">Buat Reservasi Baru</h2>
                    <p class="text-muted small mb-4">Silakan pilih dokter, tentukan tanggal kunjungan dan isi keluhan Anda.</p>
                    
                    <form method="POST">
                        <input type="hidden" name="id_jadwal" id="id_jadwal" value="<?php echo $prefill_jadwal; ?>" required>

                        <!-- FIELD 1: PILIH POLI -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small" style="letter-spacing: 1px;">PILIH POLI</label>
                            <select id="select_poli" class="form-select">
                                <option value="">-- Semua Poli --</option>
                                <?php foreach($polis as $p): ?>
                                    <option value="<?php echo htmlspecialchars($p); ?>"><?php echo htmlspecialchars($p); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- FIELD 2: TANGGAL KUNJUNGAN -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small" style="letter-spacing: 1px;">TANGGAL KUNJUNGAN</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo $prefill_tanggal; ?>">
                            <?php if ($prefill_tanggal): ?>
                            <small class="text-success fst-italic mt-1 d-block" id="tanggal-help"><i class="fas fa-check-circle me-1"></i>Tanggal otomatis diisi ke hari <?php echo $prefill_info['hari']; ?> terdekat. Ubah jika perlu.</small>
                            <?php else: ?>
                            <small class="text-secondary fst-italic mt-1 d-block" id="tanggal-help">*Tentukan tanggal kunjungan terlebih dahulu.</small>
                            <?php endif; ?>
                        </div>

                        <!-- FIELD 3: PILIH JADWAL DOKTER -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-muted small" style="letter-spacing: 1px;">PILIH JADWAL DOKTER</label>
                            
                            <div class="custom-select-trigger p-3 border rounded-4 d-flex justify-content-between align-items-center bg-white cursor-pointer" id="selectJadwalTrigger" style="cursor: pointer; border: 1px solid #e0e0e0 !important; min-height: 58px; transition: 0.2s;">
                                <?php if ($prefill_info): ?>
                                    <?php 
                                    $p_spes = $prefill_info['spesialisasi'];
                                    $p_poli = (strpos(strtolower($p_spes), 'dokter') === false) ? 'Poli ' . $p_spes : str_ireplace('dokter', 'poli', $p_spes);
                                    ?>
                                    <span id="selected-jadwal-label" class="fw-bold text-dark" style="font-size: 14px;">
                                        <?php echo htmlspecialchars($prefill_info['nama_dokter']); ?> (<?php echo htmlspecialchars($p_poli); ?>) - <?php echo $prefill_info['hari']; ?>
                                    </span>
                                <?php else: ?>
                                    <span id="selected-jadwal-label" class="text-muted" style="font-size: 14px;">-- Pilih Dokter & Hari --</span>
                                <?php endif; ?>
                                <i class="fas fa-chevron-down text-muted"></i>
                            </div>
                        </div>
 
                        <div id="info-kuota" class="mb-3" style="display: none;"></div>
 
                        <!-- FIELD 4: KELUHAN MEDIS -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-muted small" style="letter-spacing: 1px;">KELUHAN MEDIS</label>
                            <textarea name="keluhan" class="form-control" rows="3" placeholder="Ceritakan gejala yang Anda rasakan (Contoh: Demam sejak 2 hari yang lalu...)" required></textarea>
                        </div>
 
                        <button type="submit" name="buat_reservasi" class="btn btn-submit py-3 fw-bold rounded-4 shadow-sm text-white">
                            BUAT JADWAL KUNJUNGAN
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Jadwal Dokter -->
    <div class="modal fade" id="jadwalModal" tabindex="-1" aria-labelledby="jadwalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-800" id="jadwalModalLabel" style="color: #0f3d2e;">Pilih Jadwal Dokter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Pilih salah satu jadwal dokter di bawah ini:</p>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach($all_schedules as $row): ?>
                            <?php 
                            $jam = date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai']));
                            $display_text = htmlspecialchars($row['nama_dokter']) . " (" . htmlspecialchars($row['poli']) . ") - " . $row['hari'] . " | " . $jam;
                            $is_active = ($prefill_jadwal == $row['id_jadwal']) ? 'active' : '';
                            ?>
                            <div class="schedule-option-item p-3 border rounded-4 cursor-pointer transition-all mb-1 <?php echo $is_active; ?>" 
                                 data-id="<?php echo $row['id_jadwal']; ?>" 
                                 data-hari="<?php echo $row['hari']; ?>" 
                                 data-text="<?php echo $display_text; ?>"
                                 data-poli="<?php echo htmlspecialchars($row['poli']); ?>"
                                 style="cursor: pointer; transition: 0.2s;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="fw-bold mb-1 text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($row['nama_dokter']); ?></h6>
                                        <span class="badge bg-success bg-opacity-10 text-success fw-medium" style="font-size: 11px;"><?php echo htmlspecialchars($row['poli']); ?></span>
                                    </div>
                                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold" style="font-size: 11px;"><?php echo $row['hari']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top border-light">
                                    <span class="text-muted small"><i class="far fa-clock me-1 text-success"></i> <?php echo $jam; ?> WIB</span>
                                    <span class="text-muted small"><i class="fas fa-users me-1"></i> Kuota: <?php echo $row['kuota']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectPoli = document.getElementById('select_poli');
            const selectJadwalTrigger = document.getElementById('selectJadwalTrigger');
            const selectedJadwalLabel = document.getElementById('selected-jadwal-label');
            const idJadwalInput = document.getElementById('id_jadwal');
            const inputTanggal = document.getElementById('tanggal');
            const infoKuota = document.getElementById('info-kuota');
            const btnSubmit = document.querySelector('.btn-submit');
            const tanggalHelp = document.getElementById('tanggal-help');
            
            const HARI_MAP = {
                'Minggu': 0, 'Senin': 1, 'Selasa': 2, 'Rabu': 3, 'Kamis': 4, 'Jumat': 5, 'Sabtu': 6
            };
            const HARI_INDO = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

            // Function to filter schedules in the modal based on chosen Poli and Date
            function filterSchedules() {
                const selectedPoli = selectPoli.value;
                const tanggalVal = inputTanggal.value;
                
                let targetHari = '';
                if (tanggalVal) {
                    const parts = tanggalVal.split('-');
                    const dateObj = new Date(parts[0], parts[1] - 1, parts[2]);
                    const dow = dateObj.getDay();
                    targetHari = HARI_INDO[dow];
                }
                
                let visibleCount = 0;
                document.querySelectorAll('.schedule-option-item').forEach(item => {
                    const matchesPoli = (selectedPoli === "" || item.dataset.poli === selectedPoli);
                    const matchesHari = (targetHari === "" || item.dataset.hari === targetHari);
                    
                    if (matchesPoli && matchesHari) {
                        item.style.display = "block";
                        visibleCount++;
                    } else {
                        item.style.display = "none";
                    }
                });

                return visibleCount;
            }

            // Handle Poli Selection Change
            selectPoli.addEventListener('change', function() {
                resetJadwalSelection();
                filterSchedules();
                cekKuota();
            });

            // Handle Date Selection Change
            inputTanggal.addEventListener('change', function() {
                resetJadwalSelection();
                filterSchedules();
                cekKuota();
                if (this.value) {
                    tanggalHelp.innerHTML = `<i class="fas fa-check-circle text-success me-1"></i>Tanggal kunjungan telah dipilih.`;
                    tanggalHelp.className = "text-success fst-italic mt-1 d-block";
                } else {
                    tanggalHelp.innerHTML = `*Tentukan tanggal kunjungan terlebih dahulu.`;
                    tanggalHelp.className = "text-secondary fst-italic mt-1 d-block";
                }
            });

            function resetJadwalSelection() {
                idJadwalInput.value = "";
                selectedJadwalLabel.innerText = "-- Pilih Dokter & Hari --";
                selectedJadwalLabel.classList.remove('fw-bold', 'text-dark');
                selectedJadwalLabel.classList.add('text-muted');
                document.querySelectorAll('.schedule-option-item').forEach(i => i.classList.remove('active'));
            }

            // Trigger Doctor Schedule Modal programmatically
            selectJadwalTrigger.addEventListener('click', function() {
                if (!inputTanggal.value) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pilih Tanggal Kunjungan',
                        text: 'Silakan tentukan tanggal kunjungan Anda terlebih dahulu untuk melihat jadwal dokter yang tersedia.',
                        confirmButtonColor: '#0f3d2e',
                        customClass: { popup: 'rounded-4' }
                    });
                    return;
                }

                const count = filterSchedules();
                if (count === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Jadwal Tidak Tersedia',
                        text: 'Tidak ada jadwal dokter yang tersedia pada hari tersebut untuk poli yang dipilih.',
                        confirmButtonColor: '#0f3d2e',
                        customClass: { popup: 'rounded-4' }
                    });
                    return;
                }

                const modalEl = document.getElementById('jadwalModal');
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            });

            // Handle Schedule Selection Click
            document.querySelectorAll('.schedule-option-item').forEach(item => {
                item.addEventListener('click', function() {
                    document.querySelectorAll('.schedule-option-item').forEach(i => i.classList.remove('active'));
                    this.classList.add('active');
                    
                    const id = this.dataset.id;
                    const text = this.dataset.text;
                    const poli = this.dataset.poli;
                    
                    idJadwalInput.value = id;
                    
                    if (selectPoli.value === "") {
                        selectPoli.value = poli;
                        filterSchedules();
                    }
                    
                    selectedJadwalLabel.innerText = text;
                    selectedJadwalLabel.classList.remove('text-muted');
                    selectedJadwalLabel.classList.add('fw-bold', 'text-dark');
                    
                    const modalEl = document.getElementById('jadwalModal');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                    
                    cekKuota();
                });
            });

            function cekKuota() {
                const idJadwal = idJadwalInput.value;
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

            <?php if ($prefill_info): ?>
                const p_spes = "<?php echo $prefill_info['spesialisasi']; ?>";
                const p_poli = (p_spes.toLowerCase().indexOf('dokter') === -1) ? 'Poli ' + p_spes : p_spes.replace(/dokter/gi, 'Poli');
                selectPoli.value = p_poli;
                
                document.querySelectorAll('.schedule-option-item').forEach(item => {
                    if (item.dataset.id == "<?php echo $prefill_jadwal; ?>") {
                        item.classList.add('active');
                    }
                });
                
                filterSchedules();
            <?php endif; ?>

            if (idJadwalInput.value && inputTanggal.value) {
                cekKuota();
            }
        });

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
    </script>
</body>
</html>