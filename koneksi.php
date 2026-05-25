<?php
require_once __DIR__ . '/db.php';

// Melakukan koneksi ke database
$conn = db_connect_from_env();

// Cek jika koneksi gagal
if (!$conn) {
    http_response_code(500);
    error_log('Database connection failed: ' . (db_last_connect_error() ?: 'Tidak ada detail error dari driver database.'));

    if (strtolower((string) getenv('DB_DEBUG')) === 'true') {
        $driver = getenv('DB_DRIVER') ?: getenv('DATABASE_DRIVER') ?: '(kosong, default mysql)';
        $host = getenv('DB_HOST') ?: getenv('PGHOST') ?: getenv('MYSQLHOST') ?: '(kosong)';
        $port = getenv('DB_PORT') ?: getenv('PGPORT') ?: getenv('MYSQLPORT') ?: '(kosong)';
        $user = getenv('DB_USER') ?: getenv('PGUSER') ?: getenv('MYSQLUSER') ?: '(kosong)';
        $name = getenv('DB_NAME') ?: getenv('PGDATABASE') ?: getenv('MYSQLDATABASE') ?: '(kosong)';
        $error = db_last_connect_error() ?: 'Tidak ada detail error dari driver database.';

        die(
            "Koneksi database gagal.<br>" .
            "Driver: " . htmlspecialchars($driver, ENT_QUOTES, 'UTF-8') . "<br>" .
            "Host: " . htmlspecialchars($host, ENT_QUOTES, 'UTF-8') . "<br>" .
            "Port: " . htmlspecialchars($port, ENT_QUOTES, 'UTF-8') . "<br>" .
            "User: " . htmlspecialchars($user, ENT_QUOTES, 'UTF-8') . "<br>" .
            "Database: " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "<br>" .
            "Error: " . htmlspecialchars($error, ENT_QUOTES, 'UTF-8')
        );
    }

    die("Koneksi database gagal. Pastikan konfigurasi database sudah benar.");
}

// Memulai session di sini agar tidak perlu menulis session_start() di setiap halaman nanti
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
