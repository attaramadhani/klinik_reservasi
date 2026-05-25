<?php
session_start();
include 'koneksi.php';

// Jika sudah login, tidak perlu daftar lagi
if (isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

$pesan_error = "";
$registrasi_berhasil = false;

if (isset($_POST['register'])) {
    // 1. Sanitasi Input (Mencegah karakter berbahaya)
    $nama     = db_real_escape_string($conn, $_POST['nama']);
    // PERBAIKAN: Tangkap input NIK dan Biodata Lengkap
    $nik      = db_real_escape_string($conn, $_POST['nik']);
    $jenis_kelamin = db_real_escape_string($conn, $_POST['jenis_kelamin']);
    $tanggal_lahir = db_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat   = db_real_escape_string($conn, $_POST['alamat']);
    
    $username = db_real_escape_string($conn, $_POST['username']);
    $email    = db_real_escape_string($conn, $_POST['email']);
    $hp       = db_real_escape_string($conn, $_POST['hp']);
    $pass1    = $_POST['password'];
    $pass2    = $_POST['konfirmasi_password'];
    $role     = 'pasien';

    // 2. Validasi NIK
    if (!preg_match('/^[0-9]{16}$/', $nik)) {
        $pesan_error = "NIK harus tepat 16 digit angka (0-9), tanpa huruf atau spasi!";
    }
    // 3. Validasi No. HP (hanya angka, 10-15 digit)
    elseif (!preg_match('/^[0-9]{10,15}$/', $hp)) {
        $pesan_error = "Nomor HP hanya boleh angka, minimal 10 dan maksimal 15 digit!";
    }
    // 4. Validasi Password
    elseif (strlen($pass1) < 6) {
        $pesan_error = "Password minimal harus 6 karakter!";
    } elseif (!preg_match('/[A-Za-z]/', $pass1) || !preg_match('/[0-9]/', $pass1)) {
        $pesan_error = "Password harus mengandung kombinasi huruf dan angka!";
    } elseif ($pass1 !== $pass2) {
        $pesan_error = "Konfirmasi password tidak cocok!";
    } else {
        // 3. Cek apakah Username atau Email sudah terpakai
        $cek = db_query($conn, "SELECT * FROM users WHERE username = '$username' OR email = '$email'");
        
        // PERBAIKAN: Cek juga apakah NIK sudah pernah didaftarkan
        $cek_nik = db_query($conn, "SELECT * FROM pasien WHERE nik = '$nik'");

        if (db_num_rows($cek) > 0) {
            $pesan_error = "Username atau Email sudah terdaftar! Gunakan yang lain.";
        } else if (db_num_rows($cek_nik) > 0) {
            $pesan_error = "NIK ini sudah terdaftar! Satu NIK hanya untuk satu akun.";
        } else {
            // 4. Enkripsi Password (Hashing)
            $password_hash = password_hash($pass1, PASSWORD_DEFAULT);

            // 5. Insert ke tabel USERS
            $queryUser = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password_hash', '$role')";
            
            if (db_query($conn, $queryUser)) {
                $id_user = db_insert_id($conn); // Ambil ID yang baru dibuat

                // 6. Insert ke tabel PASIEN (PERBAIKAN: Tambahkan NIK dan Biodata lainnya)
                $queryPasien = "INSERT INTO pasien (id_user, nik, nama_lengkap, jenis_kelamin, tanggal_lahir, alamat, email, no_hp) VALUES ('$id_user', '$nik', '$nama', '$jenis_kelamin', '$tanggal_lahir', '$alamat', '$email', '$hp')";
                
                if (db_query($conn, $queryPasien)) {
                    $registrasi_berhasil = true;
                } else {
                    $pesan_error = "Gagal menyimpan data profil: " . db_error($conn);
                }
            } else {
                $pesan_error = "Gagal membuat akun: " . db_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Pasien - Cliniq</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #0f3d2e 0%, #155724 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        .logo-text {
            position: absolute;
            top: 25px; left: 40px;
            color: white; font-weight: bold; font-size: 26px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .card-register {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 600px; /* Diperlebar sedikit agar form 2 kolom lebih leluasa */
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }
        .form-control {
            background-color: #f1f3f5;
            border: 1px solid #ced4da;
            padding: 10px 15px;
            border-radius: 8px;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: #155724;
            box-shadow: 0 0 0 0.2rem rgba(21, 87, 36, 0.25);
        }
        .btn-register { 
            background: linear-gradient(to right, #0f3d2e, #155724);
            color: white; border: none; 
            padding: 12px; border-radius: 8px; 
            font-weight: bold; width: 100%; 
            letter-spacing: 1px;
            transition: transform 0.2s;
        }
        .btn-register:hover { 
            transform: scale(1.02); 
            color: #fff;
        }
        .input-group-text {
            background-color: #f1f3f5;
            border: 1px solid #ced4da;
            cursor: pointer;
        }
    </style>
</head>
<body>

    <div class="logo-text">
        <i class="fas fa-plus-circle me-2"></i> Cliniq
    </div>

    <div class="card-register">
        <h3 class="mb-2 fw-bold text-center" style="color: #0f3d2e;">Buat Akun Pasien</h3>
        <p class="text-center text-muted mb-4 small">Isi data diri Anda untuk mulai reservasi</p>

        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small mb-1">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-id-card text-muted"></i></span>
                        <input type="text" name="nama" class="form-control" placeholder="Sesuai KTP" required>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small mb-1">NIK <span class="text-danger">*</span> <span class="text-muted small fw-normal">(16 digit angka)</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-fingerprint text-muted"></i></span>
                        <input type="text" name="nik" id="nik_input" class="form-control" placeholder="Nomor KTP 16 digit" maxlength="16" inputmode="numeric" required>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small id="nik_hint" class="text-muted"><i class="fas fa-info-circle me-1"></i>Hanya angka 0-9, tanpa spasi atau huruf</small>
                        <small id="nik_counter" class="fw-bold">0 / 16</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small mb-1">Jenis Kelamin</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-venus-mars text-muted"></i></span>
                        <select name="jenis_kelamin" class="form-control" required>
                            <option value="">Pilih...</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small mb-1">Tanggal Lahir</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-calendar-alt text-muted"></i></span>
                        <input type="date" name="tanggal_lahir" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold small mb-1">Alamat Lengkap</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-map-marker-alt text-muted"></i></span>
                    <textarea name="alamat" class="form-control" rows="2" placeholder="Sesuai domisili aktif" required></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small mb-1">Username <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                        <input type="text" name="username" id="username_input" class="form-control" placeholder="Hanya huruf & angka, min 3" required>
                        <div class="invalid-feedback">Username minimal 3 karakter (huruf/angka).</div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small mb-1">No. Ponsel <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                        <input type="text" name="hp" id="hp_input" class="form-control" placeholder="08..." inputmode="numeric" maxlength="15" required>
                        <div class="invalid-feedback">No. HP harus 10-15 digit angka.</div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold small mb-1">Email <span class="text-danger">*</span></label>
                <div class="input-group has-validation">
                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                    <input type="email" name="email" id="email_input" class="form-control" placeholder="email@contoh.com" required>
                    <div class="invalid-feedback">Format email tidak valid.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold small mb-1">Password <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                        <input type="password" name="password" id="pass1" class="form-control" placeholder="Min. 6 karakter" required>
                        <span class="input-group-text" onclick="togglePass('pass1', 'icon1')" style="cursor: pointer; border-radius: 0 8px 8px 0;">
                            <i class="fas fa-eye-slash text-muted" id="icon1"></i>
                        </span>
                        <div class="invalid-feedback" id="pass1_feedback">Password min. 6 karakter & kombinasi huruf + angka.</div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="fw-bold small mb-1">Ulangi Password <span class="text-danger">*</span></label>
                    <div class="input-group has-validation">
                        <span class="input-group-text"><i class="fas fa-check-double text-muted"></i></span>
                        <input type="password" name="konfirmasi_password" id="pass2" class="form-control" placeholder="Ketik ulang" required>
                        <span class="input-group-text" onclick="togglePass('pass2', 'icon2')" style="cursor: pointer; border-radius: 0 8px 8px 0;">
                            <i class="fas fa-eye-slash text-muted" id="icon2"></i>
                        </span>
                        <div class="invalid-feedback">Konfirmasi password tidak cocok!</div>
                    </div>
                </div>
            </div>

            <button type="submit" name="register" class="btn btn-register mb-3">DAFTAR SEKARANG</button>
            
            <div class="text-center">
                <span class="small text-secondary">Sudah punya akun?</span>
                <a href="login.php" class="text-success fw-bold text-decoration-none ms-1">Login disini</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // === NIK Validator: hanya angka, real-time counter ===
        const nikInput   = document.getElementById('nik_input');
        const nikCounter = document.getElementById('nik_counter');
        const nikHint    = document.getElementById('nik_hint');

        nikInput.addEventListener('input', function () {
            // Hapus karakter non-angka secara otomatis
            this.value = this.value.replace(/[^0-9]/g, '');

            const len = this.value.length;
            nikCounter.textContent = len + ' / 16';

            if (len === 0) {
                nikCounter.style.color = '#6c757d';
                nikInput.classList.remove('is-valid', 'is-invalid');
            } else if (len === 16) {
                nikCounter.style.color = '#198754'; // hijau
                nikInput.classList.remove('is-invalid');
                nikInput.classList.add('is-valid');
                nikHint.innerHTML = '<i class="fas fa-check-circle me-1 text-success"></i><span class="text-success">NIK valid!</span>';
            } else {
                nikCounter.style.color = '#dc3545'; // merah
                nikInput.classList.remove('is-valid');
                nikInput.classList.add('is-invalid');
                nikHint.innerHTML = '<i class="fas fa-exclamation-circle me-1 text-danger"></i><span class="text-danger">NIK harus tepat 16 digit</span>';
            }
        });

        // Blokir paste karakter non-angka
        nikInput.addEventListener('paste', function (e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text');
            const onlyDigits = pasted.replace(/[^0-9]/g, '').slice(0, 16);
            this.value = onlyDigits;
            this.dispatchEvent(new Event('input'));
        });

        // Blokir tombol keyboard non-angka (kecuali kontrol: Backspace, Tab, dll)
        nikInput.addEventListener('keydown', function (e) {
            const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
            if (!allowed.includes(e.key) && !/^[0-9]$/.test(e.key)) {
                e.preventDefault();
            }
        });

        // === Username Validator ===
        const userInput = document.getElementById('username_input');
        userInput.addEventListener('input', function() {
            if (this.value.length >= 3 && /^[a-zA-Z0-9_]+$/.test(this.value)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        // === No. HP Validator ===
        const hpInput = document.getElementById('hp_input');
        hpInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length >= 10 && this.value.length <= 15) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        // === Email Validator ===
        const emailInput = document.getElementById('email_input');
        emailInput.addEventListener('input', function() {
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (pattern.test(this.value)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        });

        // === Password Validator ===
        const pass1 = document.getElementById('pass1');
        const pass2 = document.getElementById('pass2');

        function validatePassword() {
            // Check pass1
            const pVal = pass1.value;
            const hasLetterAndNumber = /[a-zA-Z]/.test(pVal) && /[0-9]/.test(pVal);

            if (pVal.length >= 6 && hasLetterAndNumber) {
                pass1.classList.remove('is-invalid');
                pass1.classList.add('is-valid');
            } else if (pVal.length > 0) {
                pass1.classList.remove('is-valid');
                pass1.classList.add('is-invalid');
                
                // Ubah text secara dinamis agar pesan informatif dan spesifik
                if (pVal.length < 6) {
                    document.getElementById('pass1_feedback').innerText = "Password minimal 6 karakter.";
                } else if (!hasLetterAndNumber) {
                    document.getElementById('pass1_feedback').innerText = "Password harus mengandung kombinasi huruf dan angka!";
                }
            } else {
                pass1.classList.remove('is-valid', 'is-invalid');
            }
            
            // Check pass2
            if (pass2.value.length > 0) {
                if (pass2.value === pass1.value) {
                    pass2.classList.remove('is-invalid');
                    pass2.classList.add('is-valid');
                } else {
                    pass2.classList.remove('is-valid');
                    pass2.classList.add('is-invalid');
                }
            } else {
                 pass2.classList.remove('is-valid', 'is-invalid');
            }
        }
        
        pass1.addEventListener('input', validatePassword);
        pass2.addEventListener('input', validatePassword);

        // Fitur Show/Hide Password dengan ubah Ikon
        function togglePass(inputId, iconId) {
            var input = document.getElementById(inputId);
            var icon = document.getElementById(iconId);
            
            if (input.type === "password") {
                input.type = "text";
                // Ganti ikon menjadi mata terbuka
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
                icon.classList.remove("text-muted");
                icon.classList.add("text-success"); // Berubah hijau saat diintip
            } else {
                input.type = "password";
                // Ganti ikon menjadi mata tertutup
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
                icon.classList.remove("text-success");
                icon.classList.add("text-muted");
            }
        }

        // Notifikasi Error
        <?php if($pesan_error != "") : ?>
            Swal.fire({
                icon: 'error',
                title: 'Gagal Daftar',
                text: '<?php echo $pesan_error; ?>',
                confirmButtonColor: '#d33'
            });
        <?php endif; ?>

        // Notifikasi Sukses
        <?php if($registrasi_berhasil) : ?>
            Swal.fire({
                icon: 'success',
                title: 'Registrasi Berhasil!',
                html: 'Akun Anda telah dibuat.<br>Silakan login untuk melanjutkan.',
                confirmButtonColor: '#155724'
            }).then(() => {
                window.location = 'login.php';
            });
        <?php endif; ?>
    </script>

</body>
</html>