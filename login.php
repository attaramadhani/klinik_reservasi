<?php
session_start();
include 'koneksi.php';

// Jika sudah login, lempar ke folder masing-masing
if (isset($_SESSION['id_user'])) {
    header("Location: " . $_SESSION['role'] . "/index.php");
    exit;
}

$pesan_error = "";
$login_berhasil = false;
$redirect_url = "";

if (isset($_POST['login'])) {
    $input = db_real_escape_string($conn, $_POST['username']); 
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$input' OR email = '$input'";
    $result = db_query($conn, $query);

    if (db_num_rows($result) === 1) {
        $row = db_fetch_assoc($result);
        
        if (password_verify($password, $row['password'])) {
            // PERBAIKAN: Paksa role menjadi huruf kecil agar pas dengan nama folder
            $role_bersih = strtolower(trim($row['role'])); 
            
            $_SESSION['id_user']  = $row['id_user'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role']     = $role_bersih;

            // Redirect ke folder/index.php (contoh: admin/index.php)
            $redirect_url = $role_bersih . "/index.php";
            $login_berhasil = true;
        } else {
            $pesan_error = "Password salah!";
        }
    } else {
        $pesan_error = "Username atau Email tidak ditemukan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cliniq System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #0f3d2e 0%, #155724 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
        }
        .logo-text { position: absolute; top: 30px; left: 40px; color: white; font-weight: 800; font-size: 28px; letter-spacing: -1px; }
        .card-login {
            background: #ffffff;
            border: none;
            border-radius: 24px;
            padding: 45px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .form-label { font-weight: 600; color: #0f3d2e; font-size: 13px; }
        .form-control { 
            background-color: #f8f9fa; 
            border: 1px solid #e9ecef;
            padding: 12px 15px; 
            border-radius: 12px;
            transition: 0.2s;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: #76c720;
            box-shadow: 0 0 0 4px rgba(118, 199, 32, 0.1);
        }
        .btn-login { 
            background: #155724;
            color: white; border: none; 
            padding: 14px; border-radius: 12px; 
            font-weight: 700; width: 100%; 
            transition: 0.3s;
            margin-top: 10px;
        }
        .btn-login:hover { background: #0c3b19; transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); color: white; }
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-left: none;
            border-radius: 0 12px 12px 0;
            cursor: pointer;
        }
        .input-group .form-control { border-right: none; border-radius: 12px 0 0 12px; }
    </style>
</head>
<body>

    <div class="logo-text"><i class="fas fa-heartbeat me-2 text-accent" style="color: #76c720;"></i>Cliniq</div>

    <div class="card-login">
        <div class="text-center mb-4">
            <h3 class="fw-bold" style="color: #0f3d2e;">Selamat Datang</h3>
            <p class="text-muted small">Silakan masuk ke akun Anda</p>
        </div>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label text-uppercase">Username / Email</label>
                <div class="input-group">
                    <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                    <span class="input-group-text"><i class="fas fa-user-circle text-muted"></i></span>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-uppercase">Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="passInput" class="form-control" placeholder="Masukkan password" required>
                    <span class="input-group-text" onclick="togglePassword()">
                        <i class="fas fa-eye-slash text-muted" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <button type="submit" name="login" class="btn btn-login mb-4">MASUK SEKARANG</button>
            
            <div class="text-center">
                <span class="small text-secondary">Belum punya akun pasien?</span><br>
                <a href="register.php" class="text-success fw-bold text-decoration-none small">Daftar Akun Baru</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Fitur Lihat/Sembunyikan Password
        function togglePassword() {
            const input = document.getElementById("passInput");
            const icon = document.getElementById("eyeIcon");
            if (input.type === "password") {
                input.type = "text";
                icon.classList.replace("fa-eye-slash", "fa-eye");
            } else {
                input.type = "password";
                icon.classList.replace("fa-eye", "fa-eye-slash");
            }
        }

        // SweetAlert untuk Error
        <?php if($pesan_error != "") : ?>
            Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: '<?php echo $pesan_error; ?>',
                confirmButtonColor: '#155724',
                customClass: { popup: 'rounded-4' }
            });
        <?php endif; ?>

        // SweetAlert untuk Sukses
        <?php if($login_berhasil) : ?>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil Masuk',
                text: 'Menyiapkan dashboard Anda...',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            }).then(() => {
                window.location = '<?php echo $redirect_url; ?>'; 
            });
        <?php endif; ?>
    </script>
</body>
</html>