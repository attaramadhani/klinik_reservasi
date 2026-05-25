<?php
session_start();
include '../koneksi.php';

// Proteksi Admin
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "<html><body><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon: 'warning', title: 'Peringatan', text: 'Pilih antrian terlebih dahulu!'}).then(() => { window.location='index.php'; });</script></body></html>";
    exit;
}

$id_reservasi = db_real_escape_string($conn, $_GET['id']);

// ==========================================
// KONFIGURASI MIDTRANS
// ==========================================
$server_key = getenv('MIDTRANS_SERVER_KEY') ?: '';
$client_key = getenv('MIDTRANS_CLIENT_KEY') ?: '';
$is_midtrans_production = strtolower((string) getenv('MIDTRANS_IS_PRODUCTION')) === 'true';
$midtrans_app_url = $is_midtrans_production
    ? 'https://app.midtrans.com'
    : 'https://app.sandbox.midtrans.com';

$snap_token = "";

// Cek apakah data pembayaran untuk reservasi ini sudah ada atau belum
$cek_bayar = db_query($conn, "SELECT * FROM pembayaran WHERE id_reservasi = '$id_reservasi'");
$is_bayar_exist = db_num_rows($cek_bayar) > 0;

// 1. PROSES BAYAR TUNAI (CASH)
if (isset($_POST['simpan_tunai'])) {
    $jumlah_bayar = db_real_escape_string($conn, $_POST['jumlah_bayar']);
    
    // Logika INSERT atau UPDATE
    if ($is_bayar_exist) {
        db_query($conn, "UPDATE pembayaran SET jumlah_bayar = '$jumlah_bayar', metode_pembayaran = 'Tunai', status_pembayaran = 'Lunas' WHERE id_reservasi = '$id_reservasi'");
    } else {
        db_query($conn, "INSERT INTO pembayaran (id_reservasi, jumlah_bayar, metode_pembayaran, status_pembayaran) VALUES ('$id_reservasi', '$jumlah_bayar', 'Tunai', 'Lunas')");
    }
    
    // Ubah status antrian jadi selesai
    db_query($conn, "UPDATE reservasi SET status = 'Selesai' WHERE id_reservasi = '$id_reservasi'");
    $sukses = true;
}

// 2. PROSES BAYAR VIA MIDTRANS (QRIS/TRANSFER)
if (isset($_POST['simpan_midtrans'])) {
    $jumlah_bayar = db_real_escape_string($conn, $_POST['jumlah_bayar']);

    if ($server_key === '' || $client_key === '') {
        $error = 'Konfigurasi Midtrans belum lengkap. Pastikan MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY sudah di-set di Vercel.';
    } else {

        // Logika INSERT atau UPDATE (Status masih Pending)
        if ($is_bayar_exist) {
            db_query($conn, "UPDATE pembayaran SET jumlah_bayar = '$jumlah_bayar', metode_pembayaran = 'Midtrans', status_pembayaran = 'Pending' WHERE id_reservasi = '$id_reservasi'");
        } else {
            db_query($conn, "INSERT INTO pembayaran (id_reservasi, jumlah_bayar, metode_pembayaran, status_pembayaran) VALUES ('$id_reservasi', '$jumlah_bayar', 'Midtrans', 'Pending')");
        }

        // Siapkan Payload Data untuk Midtrans
        $order_id = "INV-" . $id_reservasi . "-" . time();
        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => (int)$jumlah_bayar,
            ],
            'customer_details' => [
                'first_name' => "Pasien Antrian #",
                'last_name' => $id_reservasi
            ]
        ];

        // Request Snap Token menggunakan cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $midtrans_app_url . '/snap/v1/transactions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($server_key . ':')
        ]);

        // Bypass SSL Localhost Laragon
        if (in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1'], true)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($ch);

        if ($result === false) {
            $error = "cURL Error: " . curl_error($ch);
        } else {
            $response = json_decode($result);
            if(isset($response->token)) {
                $snap_token = $response->token;
                db_query($conn, "UPDATE pembayaran SET snap_token = '$snap_token' WHERE id_reservasi = '$id_reservasi'");
            } else {
                $error = "Midtrans Error: " . (isset($response->error_messages[0]) ? $response->error_messages[0] : 'Gagal terhubung ke server Midtrans.');
            }
        }
        curl_close($ch);
    }
}

// 3. CALLBACK KETIKA POPUP MIDTRANS BERHASIL DIBAYAR
if (isset($_GET['aksi']) && $_GET['aksi'] == 'lunas_midtrans') {
    db_query($conn, "UPDATE pembayaran SET status_pembayaran = 'Lunas' WHERE id_reservasi = '$id_reservasi'");
    db_query($conn, "UPDATE reservasi SET status = 'Selesai' WHERE id_reservasi = '$id_reservasi'");
    $sukses = true;
}

// Ambil data detail pasien untuk layar kasir
$query_detail = "SELECT r.*, p.nama_lengkap, d.nama_dokter, d.spesialisasi 
                 FROM reservasi r 
                 JOIN pasien p ON r.nik = p.nik
                 JOIN jadwal_dokter j ON r.id_jadwal = j.id_jadwal
                 JOIN dokter d ON j.id_dokter = d.id_dokter
                 WHERE r.id_reservasi = '$id_reservasi'";
$data = db_fetch_assoc(db_query($conn, $query_detail));

if (!$data) {
    echo "<html><body><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon: 'error', title: 'Gagal', text: 'Data reservasi tidak ditemukan!'}).then(() => { window.location='index.php'; });</script></body></html>";
    exit;
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Tagihan Pasien - Cliniq Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script type="text/javascript" src="<?php echo $midtrans_app_url; ?>/snap/snap.js" data-client-key="<?php echo htmlspecialchars($client_key, ENT_QUOTES, 'UTF-8'); ?>"></script>
    
    <style>
        body { background-color: #f8f9fa; font-family: 'Plus Jakarta Sans', sans-serif; }
        .kasir-card { background: white; border: none; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); overflow: hidden; }
        .kasir-header { background: #0f3d2e; color: white; padding: 25px; text-align: center; }
        .data-box { background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #e9ecef; margin-bottom: 20px; }
        .input-rupiah { font-size: 24px; font-weight: 800; color: #155724; }
        .btn-tunai { background: #155724; color: white; border: none; transition: 0.3s; }
        .btn-tunai:hover { background: #0c3b19; transform: translateY(-2px); color: white;}
        .btn-midtrans { background: #0088cc; color: white; border: none; transition: 0.3s; }
        .btn-midtrans:hover { background: #006699; transform: translateY(-2px); color: white;}
    </style>
</head>
<body class="py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <a href="index.php" class="btn btn-sm btn-outline-secondary rounded-pill mb-4">
                <i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard
            </a>

            <div class="kasir-card">
                <div class="kasir-header">
                    <h4 class="fw-bold mb-0"><i class="fas fa-cash-register me-2"></i> Kasir & Tagihan</h4>
                    <p class="text-white-50 small mb-0 mt-1">Selesaikan pemeriksaan dan pilih metode bayar</p>
                </div>
                
                <div class="p-4">
                    <div class="data-box">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted small fw-bold">NOMOR ANTRIAN</span>
                            <span class="badge bg-success fs-6">#<?php echo $data['no_antrian']; ?></span>
                        </div>
                        <h5 class="fw-bold text-dark mb-1"><?php echo $data['nama_lengkap']; ?></h5>
                        <p class="mb-0 text-muted small"><i class="fas fa-user-md me-1"></i> Dokter: <b><?php echo $data['nama_dokter']; ?></b></p>
                    </div>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="fw-bold mb-2 text-dark">Total Biaya (Konsultasi + Obat)</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light fw-bold text-secondary border-end-0">Rp</span>
                                <input type="number" name="jumlah_bayar" class="form-control input-rupiah border-start-0" placeholder="0" min="1000" value="<?php echo isset($_POST['jumlah_bayar']) ? $_POST['jumlah_bayar'] : ''; ?>" required>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <button type="submit" name="simpan_tunai" class="btn btn-tunai w-100 py-3 rounded-3 shadow-sm fw-bold">
                                    <i class="fas fa-money-bill-wave d-block fs-4 mb-1"></i> BAYAR TUNAI
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="simpan_midtrans" class="btn btn-midtrans w-100 py-3 rounded-3 shadow-sm fw-bold">
                                    <i class="fas fa-qrcode d-block fs-4 mb-1"></i> E-WALLET / QRIS
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // 1. Script Jika Midtrans Token Berhasil Digenerate
    <?php if($snap_token != "") : ?>
        window.snap.pay('<?php echo $snap_token; ?>', {
            onSuccess: function(result){
                window.location.href = 'input_tagihan.php?id=<?php echo $id_reservasi; ?>&aksi=lunas_midtrans';
            },
            onPending: function(result){
                Swal.fire('Tertunda', 'Menunggu pembayaran diselesaikan.', 'warning');
            },
            onError: function(result){
                Swal.fire('Gagal', 'Pembayaran dibatalkan atau gagal.', 'error');
            },
            onClose: function(){
                Swal.fire('Batal', 'Anda menutup jendela pembayaran sebelum menyelesaikannya.', 'info');
            }
        });
    <?php endif; ?>

    // 2. Notifikasi Jika Berhasil Disimpan & Lunas
    <?php if(isset($sukses)) : ?>
        Swal.fire({
            icon: 'success',
            title: 'Transaksi Selesai!',
            text: 'Tagihan tersimpan dan pasien dinyatakan Lunas.',
            confirmButtonColor: '#155724',
            showConfirmButton: true,
            confirmButtonText: 'Kembali ke Beranda'
        }).then((result) => {
            window.location = 'index.php';
        });
    <?php endif; ?>

    // 3. Notifikasi Jika Error
    <?php if(isset($error)) : ?>
        Swal.fire('Terjadi Kesalahan', '<?php echo $error; ?>', 'error');
    <?php endif; ?>
</script>

</body>
</html>
