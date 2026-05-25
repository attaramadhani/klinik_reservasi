<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
    /* Global Sidebar Variables */
    :root { 
        --sidebar-bg: #0f3d2e; 
        --accent: #76c720; 
        --bg-light: #f4f7f6; 
        --white: #ffffff;
        --text-dark: #1a2b23;
    }

    /* Sidebar Modern */
    .sidebar { 
        background: var(--sidebar-bg); 
        height: 100vh; 
        color: white; 
        position: fixed; 
        width: 260px;
        transition: all 0.3s;
        z-index: 1000;
        top: 0; left: 0;
        display: flex;
        flex-direction: column;
    }
    
    .sidebar-nav-container {
        flex-grow: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    
    /* Custom Scrollbar for sidebar */
    .sidebar-nav-container::-webkit-scrollbar {
        width: 5px;
    }
    .sidebar-nav-container::-webkit-scrollbar-track {
        background: transparent;
    }
    .sidebar-nav-container::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.2);
        border-radius: 10px;
    }
    .sidebar-nav-container::-webkit-scrollbar-thumb:hover {
        background: rgba(255,255,255,0.4);
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
        font-weight: 500;
        transition: all 0.3s ease; 
        text-decoration: none;
        display: block;
    }
    .nav-link:hover {
        color: var(--accent);
        background: rgba(118, 199, 32, 0.1);
    }
    .nav-link.active { 
        background: var(--accent) !important; 
        color: var(--sidebar-bg) !important; 
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(118, 199, 32, 0.3);
    }
    
    /* Main Content */
    .main-content { margin-left: 260px; padding: 40px; transition: all 0.3s; }

    /* Hamburger Menu Button */
    .mobile-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
        background: var(--sidebar-bg);
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    /* Overlay for mobile sidebar */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }

    /* Responsive Styles */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }
        .sidebar.show {
            transform: translateX(0);
        }
        .main-content {
            margin-left: 0 !important;
            padding: 20px !important;
            padding-top: 70px !important; /* space for hamburger */
        }
        .mobile-toggle {
            display: block;
        }
        .sidebar-overlay.show {
            display: block;
        }
    }
</style>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Mobile Toggle Button -->
<button class="mobile-toggle" id="mobileToggle">
    <i class="fas fa-bars"></i>
</button>

<div class="sidebar shadow">
    <div class="logo-area text-center">
        <h3 class="fw-800 mb-0" style="letter-spacing: -1.5px;">
            <i class="fas fa-heartbeat me-2 text-accent" style="color: var(--accent);"></i>CLINIQ
        </h3>
        <span class="badge bg-white bg-opacity-10 text-uppercase small mt-2" style="font-size: 9px; letter-spacing: 2px;">Management System</span>
    </div>
    <div class="sidebar-nav-container">
        <nav class="nav flex-column mt-3">
            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" href="index.php"><i class="fas fa-th-large me-3"></i> Dashboard</a>
            <a class="nav-link <?= $current_page == 'dokter.php' ? 'active' : '' ?>" href="dokter.php"><i class="fas fa-user-md me-3"></i> Data Dokter</a>
            <a class="nav-link <?= $current_page == 'jadwal.php' ? 'active' : '' ?>" href="jadwal.php"><i class="fas fa-calendar-alt me-3"></i> Jadwal Praktek</a>
            <a class="nav-link <?= $current_page == 'pasien.php' ? 'active' : '' ?>" href="pasien.php"><i class="fas fa-users me-3"></i> Data Pasien</a>
            <a class="nav-link <?= $current_page == 'reservasi.php' ? 'active' : '' ?>" href="reservasi.php"><i class="fas fa-clipboard-list me-3"></i> Reservasi</a>
            <a class="nav-link <?= $current_page == 'konfirmasi_pembayaran.php' ? 'active' : '' ?>" href="konfirmasi_pembayaran.php"><i class="fas fa-credit-card me-3"></i> Kasir / Tagihan</a>
            <a class="nav-link <?= $current_page == 'laporan.php' ? 'active' : '' ?>" href="laporan.php"><i class="fas fa-chart-line me-3"></i> Laporan Keuangan</a>
        </nav>
        
        <div style="margin-top: auto; padding: 20px;">
            <a href="../logout.php" class="btn btn-danger w-100 rounded-4 py-3 fw-bold border-0 shadow-sm text-white" style="background: #e74c3c;">
                <i class="fas fa-power-off me-2"></i> KELUAR
            </a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileToggle = document.getElementById('mobileToggle');
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        if (mobileToggle && sidebar && overlay) {
            mobileToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            });

            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    });
</script>
