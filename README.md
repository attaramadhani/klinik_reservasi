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
- **Database**: MySQL lokal atau Supabase PostgreSQL untuk deploy
- **Frontend**: Bootstrap 5, Font Awesome 6
- **Library**: SweetAlert2 (Notifikasi), Midtrans Snap JS (Pembayaran)

## 📦 Cara Instalasi

1. **Clone Repositori**:
   ```bash
   git clone https://github.com/attaramadhani/klinik_reservasi.git
   ```

2. **Persiapan Database**:
   - Untuk Supabase, buat project Supabase lalu jalankan isi file `supabase_schema.sql` di SQL Editor.
   - File schema hanya berisi struktur tabel. Data akun/pasien/reservasi tidak disimpan di GitHub demi privasi.

3. **Konfigurasi Koneksi**:
   - Buka file `koneksi.php`.
   - Lokal MySQL tetap memakai default `127.0.0.1:3307`.
   - Untuk deploy, simpan konfigurasi database sebagai environment variables langsung di dashboard platform deploy.
   - Jangan menaruh host, user, password, project ref, atau connection string di GitHub.

4. **Konfigurasi Midtrans**:
   - Simpan key Midtrans di environment variables, bukan langsung di file PHP.
   - Untuk demo, gunakan mode Sandbox dari dashboard Midtrans.
   - Jangan menaruh Server Key, Client Key, atau kredensial pembayaran apa pun di GitHub.

5. **Jalankan Aplikasi**:
   - Pindahkan folder ke direktori server (seperti `htdocs` di XAMPP atau `www` di Laragon).
   - Akses melalui browser: `http://localhost/klinik`.

## 🔒 Catatan Keamanan
Jangan pernah mengunggah file yang berisi API key, connection string, password database, project ref, data akun, data pasien, atau dump database ke GitHub. Simpan semua nilai sensitif di environment variables platform deploy atau file lokal yang di-ignore.

---
Dikembangkan oleh **Atta Ramadhani**
