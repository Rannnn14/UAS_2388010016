<?php
/**
 * Modul Transaksi Peminjaman & Pengembalian Buku
 */
require_once 'header.php';

$success = '';
$error = '';

// Konfigurasi Tarif Denda per hari terlambat
$tarif_denda = 1000; // Rp 1.000,- per hari

// 1. PROSES AKSI: Kembalikan Buku (Akses: Admin & Anggota untuk memudahkan simulasi)
if (isset($_GET['action']) && $_GET['action'] === 'kembali') {
    $id_transaksi = intval($_GET['id']);
    
    try {
        // Ambil data transaksi terlebih dahulu
        $stmt_trans = $pdo->prepare("SELECT * FROM transaksi WHERE id = :id");
        $stmt_trans->execute(['id' => $id_transaksi]);
        $trans = $stmt_trans->fetch();
        
        if (!$trans) {
            $error = 'Transaksi tidak ditemukan!';
        } elseif ($trans['status'] === 'kembali') {
            $error = 'Buku ini sudah dikembalikan sebelumnya!';
        } else {
            // Cek otentikasi (Anggota hanya bisa mengembalikan transaksi miliknya sendiri)
            if ($role === 'anggota' && $trans['id_user'] != $_SESSION['user_id']) {
                $error = 'Anda tidak memiliki hak akses untuk mengembalikan peminjaman ini!';
            } else {
                // Hitung denda otomatis
                $tgl_kembali_seharusnya = new DateTime($trans['tanggal_kembali']);
                $tgl_kembali_sekarang = new DateTime(date('Y-m-d')); // tanggal hari ini
                
                $denda = 0;
                if ($tgl_kembali_sekarang > $tgl_kembali_seharusnya) {
                    $selisih = $tgl_kembali_sekarang->diff($tgl_kembali_seharusnya);
                    $hari_terlambat = $selisih->days;
                    $denda = $hari_terlambat * $tarif_denda;
                }
                
                $pdo->beginTransaction();
                
                // 1. Update Transaksi (Status Kembali & Simpan Denda)
                $stmt_update_trans = $pdo->prepare("UPDATE transaksi SET status = 'kembali', denda = :denda WHERE id = :id");
                $stmt_update_trans->execute([
                    'denda' => $denda,
                    'id'    => $id_transaksi
                ]);
                
                // 2. Tambah kembali stok buku
                $stmt_update_stok = $pdo->prepare("UPDATE buku SET jumlah = jumlah + 1 WHERE id = :id_buku");
                $stmt_update_stok->execute(['id_buku' => $trans['id_buku']]);
                
                $pdo->commit();
                
                $pesan_denda = ($denda > 0) ? " Terlambat dikembalikan. Denda yang dikenakan: Rp " . number_format($denda, 0, ',', '.') : " Tepat waktu (Tanpa Denda).";
                $success = 'Buku berhasil dikembalikan!' . $pesan_denda;
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Terjadi kesalahan sistem pengembalian: ' . $e->getMessage();
    }
}

// 2. PROSES AKSI: Tambah Transaksi Manual (Hanya Admin)
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_loan') {
    $id_user        = intval($_POST['id_user']);
    $id_buku        = intval($_POST['id_buku']);
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $durasi_pinjam  = intval($_POST['durasi_pinjam']);
    
    if (empty($id_user) || empty($id_buku) || empty($tanggal_pinjam) || empty($durasi_pinjam)) {
        $error = 'Semua field wajib diisi!';
    } else {
        try {
            // Hitung tanggal kembali seharusnya
            $tanggal_kembali = date('Y-m-d', strtotime($tanggal_pinjam . " + $durasi_pinjam days"));
            
            // Cek stok buku
            $stmt_check = $pdo->prepare("SELECT jumlah, judul FROM buku WHERE id = :id");
            $stmt_check->execute(['id' => $id_buku]);
            $buku = $stmt_check->fetch();
            
            if (!$buku) {
                $error = 'Buku tidak ditemukan!';
            } elseif ($buku['jumlah'] <= 0) {
                $error = 'Stok buku "' . htmlspecialchars($buku['judul']) . '" sedang kosong!';
            } else {
                $pdo->beginTransaction();
                
                // 1. Simpan Transaksi Peminjaman
                $stmt_insert = $pdo->prepare("INSERT INTO transaksi (id_user, id_buku, tanggal_pinjam, tanggal_kembali, status, denda) VALUES (:id_user, :id_buku, :tgl_pinjam, :tgl_kembali, 'dipinjam', 0)");
                $stmt_insert->execute([
                    'id_user' => $id_user,
                    'id_buku' => $id_buku,
                    'tgl_pinjam' => $tanggal_pinjam,
                    'tgl_kembali' => $tanggal_kembali
                ]);
                
                // 2. Kurangi stok buku
                $stmt_update_stok = $pdo->prepare("UPDATE buku SET jumlah = jumlah - 1 WHERE id = :id");
                $stmt_update_stok->execute(['id' => $id_buku]);
                
                $pdo->commit();
                $success = 'Transaksi peminjaman baru berhasil dicatat!';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal mencatat transaksi peminjaman: ' . $e->getMessage();
        }
    }
}

// 3. PROSES AKSI: Hapus Transaksi (Hanya Admin)
if ($role === 'admin' && isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        // Ambil data transaksi terlebih dahulu
        $stmt_get = $pdo->prepare("SELECT status, id_buku FROM transaksi WHERE id = :id");
        $stmt_get->execute(['id' => $delete_id]);
        $t_data = $stmt_get->fetch();
        
        $pdo->beginTransaction();
        
        // Jika dihapus dalam keadaan masih dipinjam, kembalikan stoknya
        if ($t_data && $t_data['status'] === 'dipinjam') {
            $stmt_restore_stok = $pdo->prepare("UPDATE buku SET jumlah = jumlah + 1 WHERE id = :id");
            $stmt_restore_stok->execute(['id' => $t_data['id_buku']]);
        }
        
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        
        $pdo->commit();
        $success = 'Riwayat transaksi berhasil dihapus!';
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Gagal menghapus riwayat transaksi: ' . $e->getMessage();
    }
}

// 4. AMBIL DATA UNTUK FORM DROPDOWN (Hanya Admin)
$users_list = [];
$books_list = [];
if ($role === 'admin') {
    try {
        $users_list = $pdo->query("SELECT id, nama_lengkap FROM users WHERE role = 'anggota' ORDER BY nama_lengkap ASC")->fetchAll();
        $books_list = $pdo->query("SELECT id, judul, jumlah FROM buku ORDER BY judul ASC")->fetchAll();
    } catch (PDOException $e) {
        $error = 'Gagal memuat daftar dropdown: ' . $e->getMessage();
    }
}

// 5. AMBIL SEMUA RIWAYAT TRANSAKSI (Filter berdasarkan role)
$trans_list = [];
try {
    if ($role === 'admin') {
        $trans_list = $pdo->query("SELECT t.*, u.nama_lengkap, u.username, b.judul 
                                   FROM transaksi t
                                   JOIN users u ON t.id_user = u.id
                                   JOIN buku b ON t.id_buku = b.id
                                   ORDER BY t.id DESC")->fetchAll();
    } else {
        $user_id = $_SESSION['user_id'];
        $stmt_user_t = $pdo->prepare("SELECT t.*, b.judul 
                                      FROM transaksi t
                                      JOIN buku b ON t.id_buku = b.id
                                      WHERE t.id_user = :user_id
                                      ORDER BY t.id DESC");
        $stmt_user_t->execute(['user_id' => $user_id]);
        $trans_list = $stmt_user_t->fetchAll();
    }
} catch (PDOException $e) {
    $error = 'Gagal mengambil data transaksi: ' . $e->getMessage();
}
?>

<div class="page-title">
    <h1>Laporan Transaksi Peminjaman Buku</h1>
    <p><?= ($role === 'admin') ? 'Kelola dan monitor transaksi peminjaman perpustakaan.' : 'Pantau riwayat buku yang Anda pinjam beserta jatuh tempo pengembalian.' ?></p>
</div>

<!-- Tampilan Pesan -->
<?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="margin-top: 20px;">
        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success" style="margin-top: 20px;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<!-- Formulir Input Transaksi Baru (Hanya Admin) -->
<?php if ($role === 'admin'): ?>
    <div class="data-card" style="margin-top: 30px;">
        <h2><i class="fa-solid fa-file-invoice" style="color: var(--accent-primary); margin-right: 8px;"></i> Catat Peminjaman Baru (Manual)</h2>
        
        <form action="transaksi.php" method="POST" class="grid-form" style="margin-top: 20px;">
            <input type="hidden" name="action" value="add_loan">

            <!-- Kiri -->
            <div>
                <div class="form-group">
                    <label for="id_user" class="form-label">Pilih Anggota</label>
                    <select id="id_user" name="id_user" class="form-select" required>
                        <option value="">-- Pilih Anggota --</option>
                        <?php foreach ($users_list as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama_lengkap']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_buku" class="form-label">Pilih Buku</label>
                    <select id="id_buku" name="id_buku" class="form-select" required>
                        <option value="">-- Pilih Buku --</option>
                        <?php foreach ($books_list as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ($b['jumlah'] <= 0) ? 'disabled' : '' ?>>
                                <?= htmlspecialchars($b['judul']) ?> (Stok: <?= $b['jumlah'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Kanan -->
            <div>
                <div class="form-group">
                    <label for="tanggal_pinjam" class="form-label">Tanggal Mulai Pinjam</label>
                    <input type="date" id="tanggal_pinjam" name="tanggal_pinjam" class="form-input" value="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="form-group">
                    <label for="durasi_pinjam" class="form-label">Durasi Peminjaman (Hari)</label>
                    <select id="durasi_pinjam" name="durasi_pinjam" class="form-select" required>
                        <option value="3">3 Hari</option>
                        <option value="7" selected>7 Hari (Standar)</option>
                        <option value="14">14 Hari</option>
                    </select>
                </div>
            </div>

            <!-- Submit -->
            <div style="grid-column: span 2; display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Daftarkan Peminjaman
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Tabel Riwayat Transaksi -->
<div class="data-card" style="margin-top: 40px;">
    <div class="card-header">
        <h2>Riwayat Seluruh Peminjaman</h2>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <?php if ($role === 'admin'): ?>
                        <th>Anggota</th>
                    <?php endif; ?>
                    <th>Judul Buku</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Status</th>
                    <th>Denda (Late fee)</th>
                    <th style="width: 180px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($trans_list) > 0): ?>
                    <?php $no = 1; foreach ($trans_list as $t): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <?php if ($role === 'admin'): ?>
                                <td>
                                    <strong><?= htmlspecialchars($t['nama_lengkap']) ?></strong>
                                    <span style="display:block; font-size:0.75rem; color:var(--text-secondary);">@<?= htmlspecialchars($t['username']) ?></span>
                                </td>
                            <?php endif; ?>
                            <td><strong><?= htmlspecialchars($t['judul']) ?></strong></td>
                            <td><?= date('d-m-Y', strtotime($t['tanggal_pinjam'])) ?></td>
                            <td><?= date('d-m-Y', strtotime($t['tanggal_kembali'])) ?></td>
                            <td>
                                <?php if ($t['status'] === 'dipinjam'): ?>
                                    <span class="badge badge-warning">Dipinjam</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Kembali</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['denda'] > 0): ?>
                                    <strong style="color: var(--danger);">Rp <?= number_format($t['denda'], 0, ',', '.') ?></strong>
                                <?php elseif ($t['status'] === 'dipinjam'): ?>
                                    <!-- Kalkulasi denda sementara real-time jika terlambat -->
                                    <?php
                                    $tgl_kembali = new DateTime($t['tanggal_kembali']);
                                    $hari_ini = new DateTime(date('Y-m-d'));
                                    if ($hari_ini > $tgl_kembali) {
                                        $diff = $hari_ini->diff($tgl_kembali);
                                        $temp_denda = $diff->days * $tarif_denda;
                                        echo "<span style='color:var(--danger); font-size:0.85rem; font-weight:600;'>Estimasi: Rp " . number_format($temp_denda, 0, ',', '.') . "</span>";
                                    } else {
                                        echo "<span style='color:var(--text-muted);'>-</span>";
                                    }
                                    ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="action-buttons" style="justify-content: center;">
                                    <?php if ($t['status'] === 'dipinjam'): ?>
                                        <a href="transaksi.php?action=kembali&id=<?= $t['id'] ?>" class="btn btn-success btn-delete-confirm" data-message="Apakah Anda yakin ingin mencatat pengembalian buku ini?" style="padding: 6px 12px; font-size: 0.8rem;">
                                            <i class="fa-solid fa-arrow-rotate-left"></i> Kembalikan
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.8rem; cursor: not-allowed; opacity: 0.5;" disabled>
                                            Selesai
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($role === 'admin'): ?>
                                        <a href="transaksi.php?delete=<?= $t['id'] ?>" class="btn-icon delete btn-delete-confirm" data-message="Apakah Anda yakin ingin menghapus data transaksi ini dari riwayat?" title="Hapus Riwayat">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= ($role === 'admin') ? 8 : 7 ?>" style="text-align: center; color: var(--text-muted); padding: 30px 0;">
                            Belum ada riwayat transaksi peminjaman.
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
