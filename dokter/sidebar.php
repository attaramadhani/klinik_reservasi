<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Global Variables for Doctor Sidebar */
    :root {
        --primary-green: #0f3d2e;
        --accent-green: #76c720;
        --bg-light: #f4f7f6;
    }

    /* Sidebar Styling */
    .sidebar { 
        background: var(--primary-green); 
        min-height: 100vh; 
        color: white; 
        position: fixed; 
        width: 260px;
        z-index: 1000;
        top: 0; left: 0;
    }
    .sidebar .logo-area { 
        padding: 30px 20px; 
        border-bottom: 1px solid rgba(255,255,255,0.05); 
    }
    .nav-link { 
        color: rgba(255,255,255,0.6); 
        padding: 14px 25px; 
        border-radius: 12px; 
        margin: 8px 15px; 
        font-weight: 600;
        transition: 0.3s; 
        text-decoration: none;
        display: block;
    }
    .nav-link:hover { 
        background: rgba(118, 199, 32, 0.1);
        color: var(--accent-green) !important; 
    }
    .nav-link.active { 
        background: var(--accent-green) !important; 
        color: var(--primary-green) !important; 
    }

    .main-content { margin-left: 260px; padding: 40px; }
</style>

<div class="sidebar shadow">
    <div class="logo-area text-center">
        <h4 class="fw-800 mb-0"><i class="fas fa-stethoscope me-2 text-accent" style="color: var(--accent-green);"></i>CLINIQ</h4>
        <small class="opacity-50" style="font-size: 11px; letter-spacing: 1px;">PANEL DOKTER</small>
    </div>
    <nav class="nav flex-column mt-4">
        <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php"><i class="fas fa-home me-3"></i> Dashboard</a>
        <a class="nav-link <?= $current_page == 'jadwal.php' ? 'active' : '' ?>" href="jadwal.php"><i class="fas fa-calendar-alt me-3"></i> Kelola Jadwal</a>
        <a class="nav-link <?= $current_page == 'reservasi.php' ? 'active' : '' ?>" href="reservasi.php"><i class="fas fa-clipboard-check me-3"></i> Konfirmasi Antrian</a>
        <a class="nav-link <?= $current_page == 'pemeriksaan.php' ? 'active' : '' ?>" href="pemeriksaan.php"><i class="fas fa-notes-medical me-3"></i> Pemeriksaan Pasien</a>
        <a class="nav-link <?= $current_page == 'riwayat_medis.php' ? 'active' : '' ?>" href="riwayat_medis.php"><i class="fas fa-file-medical-alt me-3"></i> Riwayat Rekam Medis</a>
        
        <div style="margin-top: 100px; padding: 0 20px;">
            <a href="../logout.php" class="btn btn-danger w-100 rounded-4 py-3 fw-bold border-0 shadow-sm text-white" style="background: #e74c3c;">
                <i class="fas fa-power-off me-2"></i> KELUAR
            </a>
        </div>
    </nav>
</div>
