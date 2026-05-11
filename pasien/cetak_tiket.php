<?php
session_start();
include '../koneksi.php';

// Cek Login Pasien
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    header("Location: ../login.php"); 
    exit;
}

if (!isset($_GET['id'])) {
    echo "ID Reservasi tidak ditemukan."; 
    exit;
}

$id_reservasi = mysqli_real_escape_string($conn, $_GET['id']);
$id_user = $_SESSION['id_user'];

// Query Gabungan: Menarik data Reservasi, Dokter, Pasien, DAN Pembayaran
$query = "SELECT r.*, j.hari, j.jam_mulai, j.jam_selesai, d.nama_dokter, d.spesialisasi, 
                 p.nama_lengkap, p.nik, 
                 b.jumlah_bayar, b.metode_pembayaran, b.status_pembayaran, b.id_pembayaran
          FROM reservasi r 
          JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal 
          JOIN dokter d ON j.id_dokter = d.id_dokter 
          JOIN pasien p ON r.nik = p.nik
          LEFT JOIN pembayaran b ON r.id_reservasi = b.id_reservasi
          WHERE r.id_reservasi = '$id_reservasi' AND p.id_user = '$id_user'";

$result = mysqli_query($conn, $query);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    echo "Data tiket tidak valid atau Anda tidak memiliki akses."; 
    exit;
}

// LOGIKA SKENARIO 2: Dokumen berubah jadi Bukti Bayar jika status pembayaran Lunas
// Fallback: jika tidak ada record pembayaran tapi status reservasi sudah Selesai, tetap tampilkan sebagai bukti bayar
$is_paid = ($data['status_pembayaran'] == 'Lunas') || ($data['status'] == 'Selesai');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_paid ? 'Bukti Pembayaran Sah' : 'Tiket Antrian'; ?> - #<?php echo $data['no_antrian']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: #333; margin: 0; padding: 40px; background: #f4f7f6; }
        .ticket-box { max-width: 600px; margin: auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 10px solid <?php echo $is_paid ? '#155724' : '#0f3d2e'; ?>; position: relative; }
        .header { text-align: center; border-bottom: 2px dashed #ddd; padding-bottom: 20px; margin-bottom: 20px; }
        .header h2 { margin: 0; color: #0f3d2e; font-size: 30px; font-weight: 800; letter-spacing: -1px; }
        
        .jenis-dokumen { text-align: center; font-size: 18px; font-weight: 800; color: <?php echo $is_paid ? '#155724' : '#0f3d2e'; ?>; text-transform: uppercase; margin-bottom: 15px; letter-spacing: 2px;}
        
        /* Watermark Lunas */
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-15deg); font-size: 100px; color: rgba(40, 167, 69, 0.1); font-weight: 800; z-index: 0; pointer-events: none; text-transform: uppercase; }

        .antrian { text-align: center; margin: 20px 0 30px 0; position: relative; z-index: 1; }
        .antrian p { margin: 0; font-size: 13px; color: #555; font-weight: 800; letter-spacing: 2px; }
        .antrian h1 { margin: 5px 0; font-size: 70px; color: <?php echo $is_paid ? '#28a745' : '#76c720'; ?>; line-height: 1; font-weight: 800; }
        
        .details { width: 100%; border-collapse: collapse; margin-bottom: 30px; position: relative; z-index: 1; }
        .details th, .details td { padding: 14px 0; border-bottom: 1px solid #eee; text-align: left; font-size: 15px; }
        .details th { color: #888; width: 40%; font-weight: 600; }
        .details td { font-weight: 800; color: #222; }
        
        .total-box { background: #f0fff4; padding: 20px; border-radius: 12px; border: 2px solid #28a745; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .total-label { color: #155724; font-weight: 800; }
        .total-amount { color: #155724; font-size: 24px; font-weight: 800; }

        .footer { text-align: center; font-size: 12px; color: #999; border-top: 2px dashed #ddd; padding-top: 20px; margin-top: 30px; }

        @media print {
            body { background: white; padding: 0; }
            .ticket-box { box-shadow: none; max-width: 100%; padding: 20px; border-top-width: 6px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()"> 

    <div class="ticket-box">
        <?php if ($is_paid): ?>
            <div class="watermark">LUNAS</div>
        <?php endif; ?>

        <div class="header">
            <h2>Klinik Cliniq</h2>
            <p>Sistem Reservasi Online Terpadu</p>
            <p>No. Transaksi: <?php echo $is_paid ? 'PAY-'.$data['id_pembayaran'] : 'REG-'.$data['id_reservasi']; ?></p>
        </div>

        <div class="jenis-dokumen">
            <?php echo $is_paid ? 'BUKTI PEMBAYARAN SAH (INVOICE)' : 'TIKET ANTRIAN PASIEN'; ?>
        </div>

        <div class="antrian">
            <p>NOMOR ANTRIAN</p>
            <h1>#<?php echo $data['no_antrian']; ?></h1>
            <p style="letter-spacing: 0; color: #999; font-size: 11px;">Tgl Cetak: <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <table class="details">
            <tr>
                <th>Nama Pasien</th>
                <td><?php echo $data['nama_lengkap']; ?></td>
            </tr>
            <tr>
                <th>Dokter</th>
                <td><?php echo $data['nama_dokter']; ?></td>
            </tr>
            <tr>
                <th>Spesialisasi</th>
                <td><?php echo $data['spesialisasi']; ?></td>
            </tr>
            <tr>
                <th>Waktu Kunjungan</th>
                <td><?php echo $data['hari']; ?>, <?php echo date('d M Y', strtotime($data['tanggal_kunjungan'])); ?></td>
            </tr>
            
            <?php if ($is_paid): ?>
            <tr>
                <th>Metode Bayar</th>
                <td><span style="color: #28a745;"><?php echo strtoupper($data['metode_pembayaran']); ?></span></td>
            </tr>
            <?php endif; ?>
        </table>

        <?php if ($is_paid): ?>
        <div class="total-box">
            <div class="total-label text-uppercase">Total Pembayaran</div>
            <div class="total-amount">Rp <?php echo number_format($data['jumlah_bayar'], 0, ',', '.'); ?></div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <?php if ($is_paid): ?>
                <p><strong>PEMBAYARAN DITERIMA</strong></p>
                <p>Dokumen ini adalah bukti pembayaran yang sah dan dihasilkan secara elektronik oleh sistem kasir Klinik Cliniq.</p>
            <?php else: ?>
                <p>Silakan tunjukkan tiket ini kepada petugas di meja pendaftaran.</p>
                <p>Mohon hadir tepat waktu sesuai dengan jadwal yang telah dipilih.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-center no-print" style="margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; border-radius: 5px; background: #0f3d2e; color: white; border: none; cursor: pointer; font-weight: bold;">
            <i class="fas fa-print"></i> Cetak Ulang
        </button>
    </div>

</body>
</html>