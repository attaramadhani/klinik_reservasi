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

        <div class="card card-jadwal p-4">
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
                        <?php
                        // Memindahkan query ke atas loop untuk kerapihan
                        $query = "SELECT j.*, d.nama_dokter 
                                  FROM jadwal_dokter j 
                                  JOIN dokter d ON j.id_dokter = d.id_dokter 
                                  ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')";
                        $result = mysqli_query($conn, $query);

                        if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                        ?>
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
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada jadwal yang diatur.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
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