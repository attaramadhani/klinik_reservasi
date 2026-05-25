<?php
include 'koneksi.php';
$bulan_ini = date('Y-m');
$query_pendapatan = db_query($conn, "SELECT SUM(b.jumlah_bayar) as total_pendapatan 
                                         FROM pembayaran b 
                                         JOIN reservasi r ON b.id_reservasi = r.id_reservasi 
                                         WHERE b.status_pembayaran = 'Lunas' 
                                         AND DATE_FORMAT(r.tanggal_kunjungan, '%Y-%m') = '$bulan_ini'");
$pendapatan_bulan_ini = db_fetch_assoc($query_pendapatan)['total_pendapatan'];
echo "Bulan Ini ($bulan_ini): " . ($pendapatan_bulan_ini ? $pendapatan_bulan_ini : 0) . "\n";

$tahun_ini = date('Y');
$query_pendapatan_tahun = db_query($conn, "SELECT SUM(b.jumlah_bayar) as total_pendapatan 
                                         FROM pembayaran b 
                                         JOIN reservasi r ON b.id_reservasi = r.id_reservasi 
                                         WHERE b.status_pembayaran = 'Lunas' 
                                         AND DATE_FORMAT(r.tanggal_kunjungan, '%Y') = '$tahun_ini'");
$pendapatan_tahun_ini = db_fetch_assoc($query_pendapatan_tahun)['total_pendapatan'];
echo "Tahun Ini ($tahun_ini): " . ($pendapatan_tahun_ini ? $pendapatan_tahun_ini : 0) . "\n";

// Show the most recent payments and their tanggal_kunjungan
$q2 = db_query($conn, "SELECT b.id_pembayaran, b.jumlah_bayar, b.status_pembayaran, r.tanggal_kunjungan 
                           FROM pembayaran b JOIN reservasi r ON b.id_reservasi = r.id_reservasi 
                           ORDER BY b.id_pembayaran DESC LIMIT 5");
while($r = db_fetch_assoc($q2)) {
    print_r($r);
}
?>
