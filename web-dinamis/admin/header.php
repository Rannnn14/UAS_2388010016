<?php
/**
 * Template Header Admin & Anggota Perpustakaan
 */
require_once '../config/koneksi.php';

// Proteksi halaman: Jika belum login, alihkan ke login
if (!isset($_SESSION['role'])) {
    header("Location: ../auth/login.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$nama_lengkap = $_SESSION['nama_lengkap'];

// Mengambil inisial nama untuk avatar
$nama_inisial = strtoupper(substr($nama_lengkap, 0, 2));

// Deteksi halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faqih Firansyah_2388010016</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- FontAwesome untuk ikon gratis -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Glowing background elements -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fa-solid fa-book-open" style="font-size: 1.5rem; color: var(--accent-primary);"></i>
                <h2>E-Perpus</h2>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-menu-item <?= ($current_page == 'index.php') ? 'active' : '' ?>">
                    <a href="index.php">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <?php if ($role === 'admin'): ?>
                    <li class="sidebar-menu-item <?= ($current_page == 'kategori.php') ? 'active' : '' ?>">
                        <a href="kategori.php">
                            <i class="fa-solid fa-tags"></i>
                            <span>Kategori Buku</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <li class="sidebar-menu-item <?= ($current_page == 'buku.php') ? 'active' : '' ?>">
                    <a href="buku.php">
                        <i class="fa-solid fa-book"></i>
                        <span>Daftar Buku</span>
                    </a>
                </li>
                
                <?php if ($role === 'admin'): ?>
                    <li class="sidebar-menu-item <?= ($current_page == 'anggota.php') ? 'active' : '' ?>">
                        <a href="anggota.php">
                            <i class="fa-solid fa-users"></i>
                            <span>Kelola Anggota</span>
                        </a>
                    </li>
                <?php endif; ?>
                
                <li class="sidebar-menu-item <?= ($current_page == 'transaksi.php') ? 'active' : '' ?>">
                    <a href="transaksi.php">
                        <i class="fa-solid fa-right-left"></i>
                        <span><?= ($role === 'admin') ? 'Transaksi Pinjam' : 'Peminjaman Saya' ?></span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-profile-summary">
                    <div class="avatar"><?= $nama_inisial ?></div>
                    <div class="user-info-text">
                        <h4><?= htmlspecialchars(substr($nama_lengkap, 0, 16)) ?></h4>
                        <p><?= htmlspecialchars($role) ?></p>
                    </div>
                </div>
                <a href="../auth/logout.php" class="btn btn-secondary btn-block btn-delete-confirm" data-message="Apakah Anda yakin ingin keluar dari sistem?">
                    Keluar <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Topbar / Header Mobile Toggle -->
            <div class="topbar">
                <button class="sidebar-toggle btn-icon" style="display: none;">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div style="margin-left: auto;">
                    <span style="color: var(--text-secondary); font-size: 0.9rem;">
                        <i class="fa-regular fa-calendar"></i> <?= date('d M Y') ?>
                    </span>
                </div>
            </div>
