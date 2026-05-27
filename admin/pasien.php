<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pasien - Cliniq Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0f3d2e; --accent: #76c720; --bg-light: #f8f9fa; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-table { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .btn-action { width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; transition: 0.2s; }
        .btn-action:hover { transform: translateY(-2px); }
    </style>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <h2 class="fw-bold mb-4">Daftar Pasien Terdaftar</h2>
    <div class="card card-table p-4">
        <?php
        $q = db_query($conn, "SELECT * FROM pasien JOIN users ON pasien.id_user = users.id_user ORDER BY pasien.nik ASC");
        $pasiens = [];
        if ($q) {
            while ($row = db_fetch_assoc($q)) {
                $pasiens[] = $row;
            }
        }
        ?>
        <!-- Desktop View Table -->
        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NIK</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>No. Ponsel</th>
                        <th>Tgl Daftar</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($pasiens)): foreach($pasiens as $row): ?>
                    <tr>
                        <td class="fw-bold"><?php echo $row['nik']; ?></td>
                        <td><?php echo $row['nama_lengkap']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['no_hp']; ?></td>
                        <td class="small text-muted"><?php echo date('d/m/Y', strtotime($row['created_at'] ?? 'now')); ?></td>
                        <td class="text-center">
                            <a href="edit_pasien.php?nik=<?php echo urlencode($row['nik']); ?>" class="btn btn-warning btn-sm btn-action text-white shadow-sm" title="Edit Pasien">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="confirmHapus('<?php echo htmlspecialchars($row['nik']); ?>', '<?php echo htmlspecialchars($row['nama_lengkap']); ?>')" class="btn btn-danger btn-sm btn-action shadow-sm" title="Hapus Pasien">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">Belum ada pasien terdaftar.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View Cards -->
        <div class="d-md-none d-flex flex-column gap-3">
            <?php if(!empty($pasiens)): foreach($pasiens as $row): ?>
                <div class="card border-0 shadow-sm p-3 rounded-4" style="background: linear-gradient(135deg, #f4faf7, #eaf6f0); border-left: 5px solid #0f3d2e !important;">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['nama_lengkap']); ?></h6>
                            <span class="badge bg-success bg-opacity-10 text-success px-2 rounded-pill small">NIK: <?php echo $row['nik']; ?></span>
                        </div>
                    </div>
                    <div class="text-muted small mb-1">
                        <i class="far fa-envelope me-1"></i> <?php echo htmlspecialchars($row['email']); ?>
                    </div>
                    <div class="text-muted small mb-1">
                        <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($row['no_hp']); ?>
                    </div>
                    <div class="small mb-3 text-muted">
                        <i class="far fa-calendar me-1"></i> Daftar: <?php echo date('d/m/Y', strtotime($row['created_at'] ?? 'now')); ?>
                    </div>
                    <div class="d-flex gap-2 justify-content-end border-top pt-2">
                        <a href="edit_pasien.php?nik=<?php echo urlencode($row['nik']); ?>" class="btn btn-sm btn-outline-warning px-3 rounded-pill text-dark">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <button onclick="confirmHapus('<?php echo htmlspecialchars($row['nik']); ?>', '<?php echo htmlspecialchars($row['nama_lengkap']); ?>')" class="btn btn-sm btn-outline-danger px-3 rounded-pill">
                            <i class="fas fa-trash-alt me-1"></i> Hapus
                        </button>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="text-center py-5 text-muted small">Belum ada pasien terdaftar.</div>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>
</div>

<script>
    function confirmHapus(nik, nama) {
        Swal.fire({
            title: 'Hapus Data Pasien?',
            text: "Anda akan menghapus pasien '" + nama + "' secara permanen beserta akun loginnya! Semua riwayat reservasi yang terkait juga mungkin akan terhapus.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-2"></i>Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'hapus_pasien.php?nik=' + encodeURIComponent(nik);
            }
        });
    }
</script>
</body>
</html>