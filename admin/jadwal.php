<?php
session_start();
include '../koneksi.php';

// Proteksi Admin
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
    <title>Jadwal Dokter - Cliniq Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        .card-jadwal { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .badge-hari { background: #e9f7ef; color: #155724; border: 1px solid #d1e7dd; padding: 6px 15px; border-radius: 10px; font-weight: 600; }
        .badge-kuota { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 12px; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mt-2" style="color: var(--primary);">Jadwal Praktek Dokter</h2>
            </div>
            <a href="tambah_jadwal.php" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold">
                <i class="fas fa-plus me-2"></i> Tambah Jadwal Baru
            </a>
        </div>

        <?php
        $query = "SELECT j.*, d.nama_dokter 
                  FROM jadwal_dokter j 
                  JOIN dokter d ON j.id_dokter = d.id_dokter 
                  ORDER BY CASE hari 
                      WHEN 'Senin' THEN 1 
                      WHEN 'Selasa' THEN 2 
                      WHEN 'Rabu' THEN 3 
                      WHEN 'Kamis' THEN 4 
                      WHEN 'Jumat' THEN 5 
                      WHEN 'Sabtu' THEN 6 
                      WHEN 'Minggu' THEN 7 
                      ELSE 8 
                  END";
        $result = db_query($conn, $query);
        $schedules = [];
        if ($result && db_num_rows($result) > 0) {
            while ($row = db_fetch_assoc($result)) {
                $schedules[] = $row;
            }
        }
        ?>

        <!-- Desktop View (Table-based layout) -->
        <div class="card card-jadwal p-4 d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Nama Dokter</th>
                            <th>Hari Praktek</th>
                            <th>Jam Kerja</th>
                            <th class="text-center">Kuota Pasien</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($schedules)): ?>
                            <?php foreach($schedules as $row): ?>
                            <tr>
                                <td class="fw-bold text-dark ps-3"><?php echo htmlspecialchars($row['nama_dokter']); ?></td>
                                <td><span class="badge-hari"><?php echo $row['hari']; ?></span></td>
                                <td class="text-muted fw-medium">
                                    <i class="far fa-clock me-1 text-primary"></i> 
                                    <?php echo date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])); ?> WIB
                                </td>
                                <td class="text-center">
                                    <span class="badge-kuota"><i class="fas fa-users me-1"></i> <?php echo $row['kuota']; ?> Orang</span>
                                </td>
                                <td class="text-center">
                                    <a href="edit_jadwal.php?id=<?php echo $row['id_jadwal']; ?>" class="btn btn-sm btn-outline-primary border-0 me-1" title="Edit Jadwal">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="hapusJadwal(<?php echo $row['id_jadwal']; ?>)" class="btn btn-sm btn-outline-danger border-0" title="Hapus Jadwal">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada jadwal yang diatur.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View (Card-based layout) -->
        <div class="d-md-none d-flex flex-column gap-3 mb-4">
            <?php if (!empty($schedules)): ?>
                <?php foreach ($schedules as $row): ?>
                    <div class="card card-jadwal p-3 shadow-sm border-0 rounded-4" style="background: linear-gradient(135deg, #f4faf7, #eaf6f0); border-left: 5px solid #0f3d2e !important;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-1"><i class="fas fa-user-md text-success me-1"></i> <?php echo htmlspecialchars($row['nama_dokter']); ?></h6>
                                <span class="badge-hari small d-inline-block mt-1"><?php echo $row['hari']; ?></span>
                            </div>
                            <span class="badge-kuota"><i class="fas fa-users me-1"></i> <?php echo $row['kuota']; ?> Orang</span>
                        </div>
                        
                        <div class="text-muted small fw-medium mb-3">
                            <i class="far fa-clock me-1 text-primary"></i> 
                            <?php echo date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])); ?> WIB
                        </div>
                        
                        <div class="d-flex gap-2 justify-content-end border-top pt-2">
                            <a href="edit_jadwal.php?id=<?php echo $row['id_jadwal']; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill" title="Edit Jadwal">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <button onclick="hapusJadwal(<?php echo $row['id_jadwal']; ?>)" class="btn btn-sm btn-outline-danger px-3 rounded-pill" title="Hapus Jadwal">
                                <i class="fas fa-trash-alt me-1"></i> Hapus
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card card-jadwal p-5 text-center text-muted">
                    <i class="far fa-calendar-times fa-3x mb-3 opacity-25"></i>
                    <p class="mb-0">Belum ada jadwal yang diatur.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function hapusJadwal(id) {
            Swal.fire({
                title: 'Hapus Jadwal?',
                text: "Jadwal ini akan dihapus secara permanen.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                customClass: { popup: 'rounded-4' }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'hapus_jadwal.php?id=' + id;
                }
            })
        }
    </script>
</body>
</html>