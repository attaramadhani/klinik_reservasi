<?php
session_start();
include '../koneksi.php';

// Hanya untuk pasien yang login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    echo json_encode(['status' => 'error', 'pesan' => 'Unauthorized']);
    exit;
}

if (isset($_POST['id_jadwal']) && isset($_POST['tanggal'])) {
    $id_jadwal = mysqli_real_escape_string($conn, $_POST['id_jadwal']);
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    
    // Jika tanggal kosong
    if(empty($tanggal)) {
         echo json_encode(['status' => 'error', 'pesan' => 'Pilih tanggal kunjungan terlebih dahulu.']);
         exit;
    }

    // Validasi Hari
    $nama_hari_inggris = date('l', strtotime($tanggal));
    $hari_indo = [
        'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'
    ];
    $hari_pilihan = $hari_indo[$nama_hari_inggris];

    // Ambil kuota maksimal dan hari jadwal
    $q_jadwal = mysqli_query($conn, "SELECT hari, kuota FROM jadwal_dokter WHERE id_jadwal = '$id_jadwal'");
    if (mysqli_num_rows($q_jadwal) > 0) {
        $d_jadwal = mysqli_fetch_assoc($q_jadwal);
        
        if ($d_jadwal['hari'] != $hari_pilihan) {
            echo json_encode(['status' => 'error', 'pesan' => "Jadwal dokter ini hanya ada di hari " . $d_jadwal['hari'] . "."]);
            exit;
        }

        // Hitung jumlah pasien yang sudah mendaftar
        $q_terdaftar = mysqli_query($conn, "SELECT COUNT(id_reservasi) as total_terdaftar 
                                            FROM reservasi 
                                            WHERE id_jadwal = '$id_jadwal' 
                                            AND tanggal_kunjungan = '$tanggal' 
                                            AND status != 'Ditolak'");
        $d_terdaftar = mysqli_fetch_assoc($q_terdaftar);
        $terdaftar = $d_terdaftar['total_terdaftar'];
        $kuota_maksimal = $d_jadwal['kuota'];
        
        $sisa_kuota = $kuota_maksimal - $terdaftar;
        if($sisa_kuota < 0) $sisa_kuota = 0;

        echo json_encode([
            'status' => 'success',
            'kuota_maksimal' => $kuota_maksimal,
            'terdaftar' => $terdaftar,
            'sisa_kuota' => $sisa_kuota
        ]);
        exit;
    } else {
        echo json_encode(['status' => 'error', 'pesan' => 'Jadwal tidak ditemukan.']);
        exit;
    }
}
echo json_encode(['status' => 'error', 'pesan' => 'Data tidak lengkap.']);
