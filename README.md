# Cliniq - Sistem Reservasi Klinik Online

Cliniq adalah aplikasi berbasis web yang dirancang untuk memudahkan proses reservasi layanan kesehatan di klinik. Aplikasi ini mendukung manajemen dokter, jadwal praktik, pendaftaran pasien, hingga sistem pembayaran terintegrasi.

## 🚀 Fitur Utama

- **Dashboard Admin**: Mengelola data dokter, jadwal, dan memantau antrian reservasi.
- **Portal Pasien**: Pasien dapat melihat jadwal dokter, melakukan reservasi online, dan mencetak tiket antrian.
- **Manajemen Dokter**: Dokter dapat mengelola jadwal praktik dan melihat riwayat medis pasien.
- **Sistem Pembayaran**: Mendukung pembayaran Tunai (Cash) dan E-Wallet/QRIS melalui integrasi **Midtrans**.
- **Laporan**: Rekapitulasi data reservasi dan pendapatan klinik.

## 🛠️ Teknologi yang Digunakan

- **Bahasa Pemrograman**: PHP (Native)
- **Database**: MySQL
- **Frontend**: Bootstrap 5, Font Awesome 6
- **Library**: SweetAlert2 (Notifikasi), Midtrans Snap JS (Pembayaran)

## 📦 Cara Instalasi

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/attaramadhani/klinik_reservasi.git
   ```

2. **Persiapan Database**:
   - Buat database baru bernama `klinik_reservasi` di MySQL (phpMyAdmin).
   - Import file `klinik_reservasi.sql` yang tersedia di root folder.

3. **Konfigurasi Koneksi**:
   - Buka file `koneksi.php`.
   - Sesuaikan konfigurasi `$host`, `$user`, `$pass`, dan `$db` dengan server lokal Anda.

4. **Konfigurasi Midtrans**:
   - Buka file `admin/input_tagihan.php`.
   - Masukkan `Server Key` dan `Client Key` dari dashboard Sandbox/Production Midtrans Anda pada variabel `$server_key` dan `$client_key`.

5. **Jalankan Aplikasi**:
   - Pindahkan folder ke direktori server (seperti `htdocs` di XAMPP atau `www` di Laragon).
   - Akses melalui browser: `http://localhost/klinik`.

## 🔒 Catatan Keamanan
Jangan pernah mengunggah (push) file yang berisi API Key asli ke GitHub. Gunakan placeholder atau environment variables untuk menjaga keamanan kredensial Anda.

---
Dikembangkan oleh **Atta Ramadhani**
