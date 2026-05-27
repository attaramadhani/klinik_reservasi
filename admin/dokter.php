<?php
session_start();
include '../koneksi.php'; 

// Proteksi: Hanya Admin yang boleh akses
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Dokter - Cliniq Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f4f7f6; font-family: 'Plus Jakarta Sans', sans-serif; }
        .card-table { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Manajemen Dokter</h2>
                        <p class="text-muted">Total tenaga medis yang terdaftar di sistem.</p>
                    </div>
                    <a href="tambah_dokter.php" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                        <i class="fas fa-plus me-2"></i> Tambah Dokter Baru
                    </a>
                </div>

                <div class="card card-table">
                    <div class="card-body p-0">
                        <?php
                        $q = db_query($conn, "SELECT d.*, u.username, u.email 
                                                 FROM dokter d 
                                                 JOIN users u ON d.id_user = u.id_user 
                                                 ORDER BY d.nama_dokter ASC");
                        $dokters = [];
                        if ($q) {
                            while($row = db_fetch_assoc($q)) {
                                $dokters[] = $row;
                            }
                        }
                        ?>
                        <!-- Desktop View Table -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4" width="80">No.</th>
                                        <th>Nama Dokter</th>
                                        <th>Spesialisasi</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th class="text-center pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($dokters)): $no = 1; foreach($dokters as $d): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted"><?php echo $no++; ?>.</td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($d['nama_dokter']); ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill">
                                                <?php echo htmlspecialchars($d['spesialisasi']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="fw-bold"><i class="far fa-envelope me-1 opacity-50"></i> <?php echo htmlspecialchars($d['email']); ?></small>
                                        </td>
                                        <td>
                                            <code class="text-primary fw-bold">@<?php echo htmlspecialchars($d['username']); ?></code>
                                        </td>
                                        <td class="text-center pe-4">
                                            <div class="btn-group shadow-sm rounded-3 overflow-hidden">
                                                <a href="edit_dokter.php?id=<?php echo $d['id_dokter']; ?>" class="btn btn-sm btn-white border" title="Edit">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </a>
                                                <button onclick="hapusDokter(<?php echo $d['id_user']; ?>)" class="btn btn-sm btn-white border" title="Hapus">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted small">Data dokter tidak ditemukan.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View Cards -->
                        <div class="d-md-none p-3 d-flex flex-column gap-3">
                            <?php if(!empty($dokters)): $no = 1; foreach($dokters as $d): ?>
                                <div class="card border-0 shadow-sm p-3 rounded-4" style="background: linear-gradient(135deg, #f4faf7, #eaf6f0); border-left: 5px solid #0f3d2e !important;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="text-muted small fw-bold">#<?php echo $no++; ?></span>
                                            <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($d['nama_dokter']); ?></h6>
                                            <span class="badge bg-success bg-opacity-10 text-success px-3 rounded-pill small"><?php echo htmlspecialchars($d['spesialisasi']); ?></span>
                                        </div>
                                    </div>
                                    <div class="text-muted small mb-1">
                                        <i class="far fa-envelope me-1"></i> <?php echo htmlspecialchars($d['email']); ?>
                                    </div>
                                    <div class="small mb-3">
                                        <i class="far fa-user me-1"></i> <code class="text-primary fw-bold">@<?php echo htmlspecialchars($d['username']); ?></code>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-end border-top pt-2">
                                        <a href="edit_dokter.php?id=<?php echo $d['id_dokter']; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </a>
                                        <button onclick="hapusDokter(<?php echo $d['id_user']; ?>)" class="btn btn-sm btn-outline-danger px-3 rounded-pill">
                                            <i class="fas fa-trash me-1"></i> Hapus
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <div class="text-center py-5 text-muted small">Data dokter tidak ditemukan.</div>
                            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function hapusDokter(id) {
            Swal.fire({
                title: 'Konfirmasi Hapus',
                text: "Menghapus dokter akan menghapus akun loginnya juga.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                customClass: {
                    popup: 'rounded-4'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'hapus_dokter.php?id=' + id;
                }
            })
        }
    </script>
</body>
</html>