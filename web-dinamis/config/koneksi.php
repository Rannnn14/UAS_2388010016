<?php
/**
 * Konfigurasi Koneksi Database Perpustakaan
 * Menggunakan PDO untuk keamanan dan portabilitas.
 */

$host = getenv('DB_HOST') ?: 'localhost';
$db   = getenv('DB_NAME') ?: 'perpustakaan';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Jika gagal koneksi, tampilkan pesan error yang rapi
    die("Koneksi database gagal: " . $e->getMessage());
}

// Mulai session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
