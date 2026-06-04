<?php
/**
 * Dashboard Perpustakaan
 */
require_once 'header.php';

// Inisialisasi variabel statistik
$count_buku = 0;
$count_kategori = 0;
$count_anggota = 0;
$count_pinjam = 0;

$recent_loans = [];

try {
    if ($role === 'admin') {
        // Query untuk Admin
        $count_buku = $pdo->query("SELECT COUNT(*) FROM buku")->fetchColumn();
        $count_kategori = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();
        $count_anggota = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'anggota'")->fetchColumn();
        $count_pinjam = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE status = 'dipinjam'")->fetchColumn();

        // Riwayat peminjaman terbaru (5 transaksi terakhir)
        $stmt = $pdo->query("SELECT t.*, u.nama_lengkap, b.judul 
                             FROM transaksi t 
                             JOIN users u ON t.id_user = u.id 
                             JOIN buku b ON t.id_buku = b.id 
                             ORDER BY t.id DESC LIMIT 5");
        $recent_loans = $stmt->fetchAll();
    } else {
        // Query untuk Anggota
        $user_id = $_SESSION['user_id'];
        $count_pinjam = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE id_user = $user_id AND status = 'dipinjam'")->fetchColumn();
        $count_kembali = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE id_user = $user_id AND status = 'kembali'")->fetchColumn();
        
        // Riwayat peminjaman user ini (5 transaksi terakhir)
        $stmt = $pdo->prepare("SELECT t.*, b.judul 
                               FROM transaksi t 
                               JOIN buku b ON t.id_buku = b.id 
                               WHERE t.id_user = :user_id 
                               ORDER BY t.id DESC LIMIT 5");
        $stmt->execute(['user_id' => $user_id]);
        $recent_loans = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Gagal memuat data: " . $e->getMessage() . "</div>";
}
?>

<div class="page-title">
    <h1>Selamat Datang, <?= htmlspecialchars($nama_lengkap) ?>!</h1>
    <p>Anda masuk sebagai <strong><?= htmlspecialchars(strtoupper($role)) ?></strong>. Berikut adalah ringkasan perpustakaan hari ini.</p>
</div>

<!-- Grid Metrics -->
<div class="metrics-grid" style="margin-top: 30px;">
    <?php if ($role === 'admin'): ?>
        <div class="metric-card">
            <div class="metric-info">
                <h3><?= $count_buku ?></h3>
                <p>Total Buku</p>
            </div>
            <div class="metric-icon">
                <i class="fa-solid fa-book"></i>
            </div>
        </div>
        
        <div class="metric-card">
            <div class="metric-info">
                <h3><?= $count_kategori ?></h3>
                <p>Kategori</p>
            </div>
            <div class="metric-icon">
                <i class="fa-solid fa-tags"></i>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-info">
                <h3><?= $count_anggota ?></h3>
                <p>Anggota Aktif</p>
            </div>
            <div class="metric-icon">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-info">
                <h3><?= $count_pinjam ?></h3>
                <p>Sedang Dipinjam</p>
            </div>
            <div class="metric-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
        </div>
    <?php else: ?>
        <div class="metric-card">
            <div class="metric-info">
                <h3><?= $count_pinjam ?></h3>
                <p>Buku Sedang Dipinjam</p>
            </div>
            <div class="metric-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-info">
                <h3><?= $count_kembali ?></h3>
                <p>Buku Telah Dikembalikan</p>
            </div>
            <div class="metric-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Recent Transactions Card -->
<div class="data-card">
    <div class="card-header">
        <h2><i class="fa-solid fa-history" style="color: var(--accent-primary); margin-right: 8px;"></i> Aktivitas Peminjaman Terbaru</h2>
    </div>
    
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No</th>
                    <?php if ($role === 'admin'): ?>
                        <th>Nama Anggota</th>
                    <?php endif; ?>
                    <th>Judul Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Batas Pengembalian</th>
                    <th>Status</th>
                    <th>Denda</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recent_loans) > 0): ?>
                    <?php $no = 1; foreach ($recent_loans as $loan): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <?php if ($role === 'admin'): ?>
                                <td><?= htmlspecialchars($loan['nama_lengkap']) ?></td>
                            <?php endif; ?>
                            <td><strong><?= htmlspecialchars($loan['judul']) ?></strong></td>
                            <td><?= date('d-m-Y', strtotime($loan['tanggal_pinjam'])) ?></td>
                            <td><?= date('d-m-Y', strtotime($loan['tanggal_kembali'])) ?></td>
                            <td>
                                <?php if ($loan['status'] === 'dipinjam'): ?>
                                    <span class="badge badge-warning">Dipinjam</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Kembali</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($loan['denda'] > 0): ?>
                                    <span style="color: var(--danger); font-weight: 600;">Rp <?= number_format($loan['denda'], 0, ',', '.') ?></span>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= ($role === 'admin') ? 7 : 6 ?>" style="text-align: center; color: var(--text-muted); padding: 30px 0;">
                            Tidak ada aktivitas peminjaman terbaru.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once 'footer.php';
?>
