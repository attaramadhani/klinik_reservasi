<?php
include 'koneksi.php';
$q = db_query($conn, "SELECT b.*, r.tanggal_kunjungan FROM pembayaran b JOIN reservasi r ON b.id_reservasi = r.id_reservasi");
while($r = db_fetch_assoc($q)) {
    print_r($r);
}
?>
