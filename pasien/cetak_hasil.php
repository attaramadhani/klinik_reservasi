<?php
session_start();
include '../koneksi.php';

// Cek Login
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'pasien') {
    exit("Akses ditolak."); 
}

if (!isset($_GET['id'])) {
    exit("ID Pemeriksaan tidak ditemukan."); 
}

$id_reservasi = db_real_escape_string($conn, $_GET['id']);
$id_user = $_SESSION['id_user'];

// Tarik data HANYA 1 ID Reservasi
$query = "SELECT h.*, r.tanggal_kunjungan, r.no_antrian, d.nama_dokter, d.spesialisasi, p.nama_lengkap
          FROM hasil_pemeriksaan h 
          JOIN reservasi r ON h.id_reservasi = r.id_reservasi 
          JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal 
          JOIN dokter d ON j.id_dokter = d.id_dokter 
          JOIN pasien p ON r.nik = p.nik
          WHERE h.id_reservasi = '$id_reservasi' AND p.id_user = '$id_user' AND r.status = 'Selesai'";

$result = db_query($conn, $query);
$data = db_fetch_assoc($result);

if (!$data) {
    exit("Data tidak ditemukan atau belum lunas."); 
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Hasil - <?php echo $data['nama_lengkap']; ?></title>
    <style>
        /* Desain Khusus Kertas A4 & PDF (Sangat Bersih) */
        body { font-family: Arial, sans-serif; color: #222; line-height: 1.5; padding: 40px; max-width: 800px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #0f3d2e; padding-bottom: 10px; margin-bottom: 30px; }
        .header h1 { color: #0f3d2e; margin: 0; font-size: 26px; text-transform: uppercase; letter-spacing: 2px;}
        .header p { margin: 5px 0 0; color: #555; font-size: 14px;}
        
        .doc-title { text-align: center; font-size: 18px; font-weight: bold; margin-bottom: 30px; text-decoration: underline; color: #155724; }

        table { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        td { padding: 6px 0; vertical-align: top; font-size: 14px;}
        .label { font-weight: bold; width: 140px; color: #444; }
        
        .section-title { font-weight: bold; color: #0f3d2e; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; margin-top: 20px; font-size: 15px;}
        .content-box { font-size: 14px; padding-left: 10px; border-left: 3px solid #155724; margin-bottom: 20px;}
        
        .ttd-container { display: flex; justify-content: flex-end; margin-top: 60px; }
        .ttd-box { text-align: center; width: 250px; }
        .ttd-name { font-weight: bold; text-decoration: underline; margin-top: 70px; color: #0f3d2e;}
        
        @media print {
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="header">
        <h1>Klinik Cliniq</h1>
        <p>Jl. Kesehatan No. 123, Kota Sehat | Telp: (021) 1234-5678</p>
    </div>

    <div class="doc-title">HASIL PEMERIKSAAN MEDIS</div>

    <table>
        <tr>
            <td class="label">No. Registrasi</td><td>: #RES-<?php echo $data['id_reservasi']; ?></td>
            <td class="label">Tanggal Periksa</td><td>: <?php echo date('d M Y', strtotime($data['tanggal_kunjungan'])); ?></td>
        </tr>
        <tr>
            <td class="label">Nama Pasien</td><td>: <?php echo htmlspecialchars($data['nama_lengkap']); ?></td>
            <td class="label">Dokter</td><td>: <?php echo htmlspecialchars($data['nama_dokter']); ?></td>
        </tr>
    </table>

    <div class="section-title">Pemeriksaan Tanda Vital</div>
    <div class="content-box">
        Tekanan Darah : <?php echo htmlspecialchars($data['tekanan_darah']); ?><br>
        Suhu Badan : <?php echo htmlspecialchars($data['suhu_badan']); ?><br>
        Berat Badan : <?php echo htmlspecialchars($data['berat_badan']); ?>
    </div>

    <div class="section-title">Informasi Medis</div>
    <div class="content-box">
        <span style="font-weight: bold;">Diagnosa Klinis:</span><br>
        <?php echo nl2br(htmlspecialchars($data['diagnosa'])); ?><br><br>
        
        <span style="font-weight: bold; color: #d9534f;">Alergi Obat:</span><br>
        <?php echo htmlspecialchars($data['alergi_obat']); ?>
    </div>

    <div class="section-title">Tindakan Medis</div>
    <div class="content-box">
        <?php echo nl2br(htmlspecialchars($data['tindakan'])); ?>
    </div>

    <div class="section-title">Resep Obat</div>
    <div class="content-box">
        <?php echo nl2br(htmlspecialchars($data['resep_obat'])); ?>
    </div>

    <?php if(!empty($data['catatan_dokter'])): ?>
    <div class="section-title">Catatan & Anjuran Dokter</div>
    <div class="content-box">
        <?php echo nl2br(htmlspecialchars($data['catatan_dokter'])); ?>
    </div>
    <?php endif; ?>

    <div class="ttd-container">
        <div class="ttd-box">
            <p style="margin-bottom: 0; font-size: 14px;">Dokter Pemeriksa,</p>
            <div class="ttd-name"><?php echo htmlspecialchars($data['nama_dokter']); ?></div>
            <div style="font-size: 12px; color: #777; margin-top: 5px;"><?php echo htmlspecialchars($data['spesialisasi']); ?></div>
        </div>
    </div>

</body>
</html>