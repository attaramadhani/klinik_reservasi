<?php
session_start();
include '../koneksi.php';

// Proteksi akses khusus dokter
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'dokter') {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
// Ambil ID Dokter berdasarkan user yang login
$q_dokter = db_query($conn, "SELECT id_dokter FROM dokter WHERE id_user = '$id_user'");
$dokter = db_fetch_assoc($q_dokter);
$id_dokter = $dokter['id_dokter'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Praktik Saya - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #0f3d2e; --accent-green: #76c720; --bg-light: #f4f7f6; }
        body { background: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; color: #2d3436; }
        

        
        .card-jadwal { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
        .badge-hari { background: #e9f7ef; color: #155724; border: 1px solid #d1e7dd; padding: 6px 15px; border-radius: 10px; font-weight: 600; }
        .badge-kuota { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; padding: 6px 12px; border-radius: 8px; font-weight: 700; font-size: 12px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h2 class="fw-800 mb-0" style="color: var(--primary-green);">Jadwal Praktik Saya</h2>
        <a href="tambah_jadwal.php" class="btn rounded-pill px-4 shadow-sm fw-bold align-self-start align-self-md-auto" style="background: var(--accent-green); color: var(--primary-green); white-space: nowrap;">
            <i class="fas fa-plus me-2"></i> Tambah Jadwal
        </a>
    </div>

    <?php
    // Hanya mengambil jadwal milik dokter yang sedang login
    $query = "SELECT * FROM jadwal_dokter 
              WHERE id_dokter = '$id_dokter' 
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
    if(db_num_rows($result) > 0) {
        while($row = db_fetch_assoc($result)) {
            $schedules[] = $row;
        }
    }
    ?>

    <!-- Desktop Table View (Tablet & Laptop) -->
    <div class="card card-jadwal p-4 d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Hari Praktek</th>
                        <th>Jam Kerja</th>
                        <th class="text-center">Kuota Pasien</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($schedules)): ?>
                        <?php foreach($schedules as $row): ?>
                        <tr>
                            <td class="ps-3"><span class="badge-hari"><?php echo $row['hari']; ?></span></td>
                            <td class="text-muted fw-medium">
                                <i class="far fa-clock me-1 text-primary"></i> 
                                <?php echo date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])); ?> WIB
                            </td>
                            <td class="text-center">
                                <span class="badge-kuota"><i class="fas fa-users me-1"></i> <?php echo $row['kuota']; ?> Orang</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group shadow-sm rounded-3 overflow-hidden">
                                    <a href="edit_jadwal.php?id=<?php echo $row['id_jadwal']; ?>" class="btn btn-sm btn-white border" title="Edit Jadwal">
                                        <i class="fas fa-edit text-primary"></i>
                                    </a>
                                    <button onclick="hapusJadwal(<?php echo $row['id_jadwal']; ?>)" class="btn btn-sm btn-white border" title="Hapus Jadwal">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan='4' class='text-center py-5 text-muted'>Anda belum mengatur jadwal praktik.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card View (Phone Screens) -->
    <div class="d-md-none">
        <?php if(!empty($schedules)): ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach($schedules as $row): ?>
                <div class="p-3 border-0 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #ffffff, #f4faf7); border-left: 5px solid var(--primary-green) !important;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge-hari"><?php echo $row['hari']; ?></span>
                        <span class="badge-kuota"><i class="fas fa-users me-1"></i> <?php echo $row['kuota']; ?> Orang</span>
                    </div>
                    <div class="text-muted fw-medium mb-3">
                        <i class="far fa-clock me-1 text-primary"></i> 
                        <?php echo date('H:i', strtotime($row['jam_mulai'])) . " - " . date('H:i', strtotime($row['jam_selesai'])); ?> WIB
                    </div>
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="edit_jadwal.php?id=<?php echo $row['id_jadwal']; ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill" title="Edit Jadwal">
                            <i class="fas fa-edit me-1"></i> Edit
                        </a>
                        <button onclick="hapusJadwal(<?php echo $row['id_jadwal']; ?>)" class="btn btn-sm btn-outline-danger px-3 rounded-pill" title="Hapus Jadwal">
                            <i class="fas fa-trash me-1"></i> Hapus
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted card card-jadwal border-0 shadow-sm rounded-4">
                Anda belum mengatur jadwal praktik.
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function hapusJadwal(id) {
        Swal.fire({
            title: 'Hapus Jadwal?',
            text: "Jadwal ini akan dihapus dari daftar praktik Anda.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'hapus_jadwal.php?id=' + id;
            }
        })
    }
</script>
</body>
</html>