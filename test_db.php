<?php
include 'koneksi.php';
$q = mysqli_query($conn, "SELECT b.*, r.tanggal_kunjungan FROM pembayaran b JOIN reservasi r ON b.id_reservasi = r.id_reservasi");
while($r = mysqli_fetch_assoc($q)) {
    print_r($r);
}
?>
