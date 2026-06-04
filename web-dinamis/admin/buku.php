<?php
/**
 * CRUD Kelola Buku (Akses: Admin & Anggota)
 */
require_once 'header.php';

$success = '';
$error = '';

// Inisialisasi variabel untuk Form Tambah/Edit (Hanya Admin)
$edit_mode = false;
$edit_id = '';
$judul = '';
$penulis = '';
$penerbit = '';
$tahun_terbit = date('Y');
$jumlah = 1;
$id_kategori = '';

// Proses Aksi Anggota: Pinjam Buku Instan
if ($role === 'anggota' && isset($_GET['action']) && $_GET['action'] === 'pinjam') {
    $id_buku = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];
    
    try {
        // Cek stok buku terlebih dahulu
        $stmt_check = $pdo->prepare("SELECT judul, jumlah FROM buku WHERE id = :id");
        $stmt_check->execute(['id' => $id_buku]);
        $buku = $stmt_check->fetch();
        
        if (!$buku) {
            $error = 'Buku tidak ditemukan!';
        } elseif ($buku['jumlah'] <= 0) {
            $error = 'Stok buku "' . htmlspecialchars($buku['judul']) . '" sedang habis!';
        } else {
            // Tanggal pinjam dan batas kembali (7 hari dari sekarang)
            $tanggal_pinjam = date('Y-m-d');
            $tanggal_kembali = date('Y-m-d', strtotime('+7 days'));
            
            $pdo->beginTransaction();
            
            // 1. Catat Transaksi
            $stmt_insert = $pdo->prepare("INSERT INTO transaksi (id_user, id_buku, tanggal_pinjam, tanggal_kembali, status, denda) VALUES (:id_user, :id_buku, :tgl_pinjam, :tgl_kembali, 'dipinjam', 0)");
            $stmt_insert->execute([
                'id_user' => $user_id,
                'id_buku' => $id_buku,
                'tgl_pinjam' => $tanggal_pinjam,
                'tgl_kembali' => $tanggal_kembali
            ]);
            
            // 2. Kurangi stok buku
            $stmt_update_stok = $pdo->prepare("UPDATE buku SET jumlah = jumlah - 1 WHERE id = :id");
            $stmt_update_stok->execute(['id' => $id_buku]);
            
            $pdo->commit();
            $success = 'Berhasil meminjam buku "' . htmlspecialchars($buku['judul']) . '"! Silakan periksa halaman "Peminjaman Saya".';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = 'Gagal melakukan peminjaman: ' . $e->getMessage();
    }
}

// Proses CRUD Buku (Hanya Admin)
if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $judul        = trim($_POST['judul']);
        $penulis      = trim($_POST['penulis']);
        $penerbit     = trim($_POST['penerbit']);
        $tahun_terbit = intval($_POST['tahun_terbit']);
        $jumlah       = intval($_POST['jumlah']);
        $id_kategori  = !empty($_POST['id_kategori']) ? intval($_POST['id_kategori']) : null;
        
        if (empty($judul) || empty($penulis) || empty($penerbit)) {
            $error = 'Judul, penulis, dan penerbit wajib diisi!';
        } else {
            // Upload Cover Image
            $cover_name = null;
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['cover']['tmp_name'];
                $file_name = $_FILES['cover']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Batasi tipe file gambar
                $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($file_ext, $allowed_exts)) {
                    // Buat folder upload jika belum ada
                    $upload_dir = '../assets/images/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $cover_name = uniqid() . '.' . $file_ext;
                    move_uploaded_file($file_tmp, $upload_dir . $cover_name);
                } else {
                    $error = 'Format cover tidak didukung! Gunakan format JPG, PNG, atau WEBP.';
                }
            }
            
            if (empty($error)) {
                try {
                    if (isset($_POST['id']) && !empty($_POST['id'])) {
                        // Proses Edit Buku
                        $id = intval($_POST['id']);
                        
                        // Cek cover lama jika cover baru tidak diupload
                        if ($cover_name === null) {
                            $stmt_old_cover = $pdo->prepare("SELECT cover_image FROM buku WHERE id = :id");
                            $stmt_old_cover->execute(['id' => $id]);
                            $cover_name = $stmt_old_cover->fetchColumn();
                        }
                        
                        $stmt = $pdo->prepare("UPDATE buku SET id_kategori = :id_kategori, judul = :judul, penulis = :penulis, penerbit = :penerbit, tahun_terbit = :tahun_terbit, jumlah = :jumlah, cover_image = :cover WHERE id = :id");
                        $stmt->execute([
                            'id_kategori'  => $id_kategori,
                            'judul'        => $judul,
                            'penulis'      => $penulis,
                            'penerbit'     => $penerbit,
                            'tahun_terbit' => $tahun_terbit,
                            'jumlah'       => $jumlah,
                            'cover'        => $cover_name,
                            'id'           => $id
                        ]);
                        $success = 'Data buku berhasil diperbarui!';
                    } else {
                        // Proses Tambah Buku Baru
                        $stmt = $pdo->prepare("INSERT INTO buku (id_kategori, judul, penulis, penerbit, tahun_terbit, jumlah, cover_image) VALUES (:id_kategori, :judul, :penulis, :penerbit, :tahun_terbit, :jumlah, :cover)");
                        $stmt->execute([
                            'id_kategori'  => $id_kategori,
                            'judul'        => $judul,
                            'penulis'      => $penulis,
                            'penerbit'     => $penerbit,
                            'tahun_terbit' => $tahun_terbit,
                            'jumlah'       => $jumlah,
                            'cover'        => $cover_name
                        ]);
                        $success = 'Buku baru berhasil ditambahkan!';
                    }
                    
                    // Reset Form
                    $judul = $penulis = $penerbit = $id_kategori = '';
                    $tahun_terbit = date('Y');
                    $jumlah = 1;
                } catch (PDOException $e) {
                    $error = 'Gagal menyimpan data buku: ' . $e->getMessage();
                }
            }
        }
    }
}

// Proses Hapus Buku (Hanya Admin)
if ($role === 'admin' && isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        // Hapus file gambar cover terlebih dahulu
        $stmt_cover = $pdo->prepare("SELECT cover_image FROM buku WHERE id = :id");
        $stmt_cover->execute(['id' => $delete_id]);
        $old_cover = $stmt_cover->fetchColumn();
        
        if ($old_cover && file_exists('../assets/images/' . $old_cover)) {
            unlink('../assets/images/' . $old_cover);
        }
        
        $stmt = $pdo->prepare("DELETE FROM buku WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        $success = 'Data buku berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus buku (mungkin buku memiliki riwayat peminjaman): ' . $e->getMessage();
    }
}

// Ambil Data Edit Buku (Hanya Admin)
if ($role === 'admin' && isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM buku WHERE id = :id");
        $stmt->execute(['id' => $edit_id]);
        $buku_edit = $stmt->fetch();
        if ($buku_edit) {
            $judul        = $buku_edit['judul'];
            $penulis      = $buku_edit['penulis'];
            $penerbit     = $buku_edit['penerbit'];
            $tahun_terbit = $buku_edit['tahun_terbit'];
            $jumlah       = $buku_edit['jumlah'];
            $id_kategori  = $buku_edit['id_kategori'];
        } else {
            $error = 'Buku tidak ditemukan!';
            $edit_mode = false;
        }
    } catch (PDOException $e) {
        $error = 'Gagal memuat data edit: ' . $e->getMessage();
    }
}

// Ambil data kategori untuk pilihan form dropdown
$kategori_list = [];
try {
    $kategori_list = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal memuat kategori pilihan: ' . $e->getMessage();
}

// Ambil semua buku dengan nama kategori
$all_buku = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
try {
    if (!empty($search)) {
        $stmt_buku = $pdo->prepare("SELECT b.*, k.nama_kategori 
                                    FROM buku b 
                                    LEFT JOIN kategori k ON b.id_kategori = k.id 
                                    WHERE b.judul LIKE :search OR b.penulis LIKE :search OR b.penerbit LIKE :search
                                    ORDER BY b.id DESC");
        $stmt_buku->execute(['search' => '%' . $search . '%']);
        $all_buku = $stmt_buku->fetchAll();
    } else {
        $all_buku = $pdo->query("SELECT b.*, k.nama_kategori 
                                 FROM buku b 
                                 LEFT JOIN kategori k ON b.id_kategori = k.id 
                                 ORDER BY b.id DESC")->fetchAll();
    }
} catch (PDOException $e) {
    $error = 'Gagal mengambil data buku: ' . $e->getMessage();
}
?>

<div class="page-title">
    <h1>Katalog Buku Perpustakaan</h1>
    <p><?= ($role === 'admin') ? 'Kelola inventori buku Anda.' : 'Cari dan pinjam buku favorit Anda di sini.' ?></p>
</div>

<!-- Tampilan Pesan Sukses / Gagal -->
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

<!-- Bagian Input Form Admin (Hanya jika admin dan sedang edit / ingin tambah) -->
<?php if ($role === 'admin'): ?>
    <div class="data-card" style="margin-top: 30px;">
        <h2><i class="fa-solid <?= $edit_mode ? 'fa-pen-to-square' : 'fa-circle-plus' ?>" style="color: var(--accent-primary); margin-right: 8px;"></i> <?= $edit_mode ? 'Perbarui Buku' : 'Tambah Buku Baru' ?></h2>
        
        <form action="buku.php" method="POST" enctype="multipart/form-data" class="grid-form" style="margin-top: 20px;">
            <input type="hidden" name="action" value="save">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?= $edit_id ?>">
            <?php endif; ?>

            <!-- Sisi Kiri Form -->
            <div>
                <div class="form-group">
                    <label for="judul" class="form-label">Judul Buku</label>
                    <input type="text" id="judul" name="judul" class="form-input" placeholder="Masukkan judul buku" value="<?= htmlspecialchars($judul) ?>" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="penulis" class="form-label">Penulis / Pengarang</label>
                    <input type="text" id="penulis" name="penulis" class="form-input" placeholder="Nama penulis" value="<?= htmlspecialchars($penulis) ?>" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="penerbit" class="form-label">Penerbit</label>
                    <input type="text" id="penerbit" name="penerbit" class="form-input" placeholder="Nama penerbit" value="<?= htmlspecialchars($penerbit) ?>" required autocomplete="off">
                </div>
            </div>

            <!-- Sisi Kanan Form -->
            <div>
                <div class="form-group">
                    <label for="id_kategori" class="form-label">Kategori Buku</label>
                    <select id="id_kategori" name="id_kategori" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori_list as $kat): ?>
                            <option value="<?= $kat['id'] ?>" <?= ($id_kategori == $kat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid-form" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div class="form-group">
                        <label for="tahun_terbit" class="form-label">Tahun Terbit</label>
                        <input type="number" id="tahun_terbit" name="tahun_terbit" class="form-input" min="1800" max="<?= date('Y') + 1 ?>" value="<?= htmlspecialchars($tahun_terbit) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="jumlah" class="form-label">Jumlah (Stok)</label>
                        <input type="number" id="jumlah" name="jumlah" class="form-input" min="0" value="<?= htmlspecialchars($jumlah) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cover" class="form-label">Cover Buku (Gambar)</label>
                    <input type="file" id="cover" name="cover" class="form-input" accept="image/*" style="padding: 8px 12px;">
                </div>
            </div>

            <!-- Tombol Submit -->
            <div style="grid-column: span 2; display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> <?= $edit_mode ? 'Simpan Perubahan' : 'Simpan Buku Baru' ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="buku.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
<?php endif; ?>

<!-- Bagian Katalog Buku -->
<div class="data-card" style="margin-top: 40px;">
    <div class="card-header" style="flex-wrap: wrap; gap: 15px;">
        <h2>Daftar Buku Tersedia</h2>
        
        <!-- Kolom Pencarian -->
        <form action="buku.php" method="GET" style="display: flex; gap: 8px;">
            <input type="text" name="search" class="form-input" placeholder="Cari buku, penulis..." value="<?= htmlspecialchars($search) ?>" style="max-width: 250px; padding: 8px 16px;">
            <button type="submit" class="btn btn-secondary" style="padding: 8px 16px;">
                <i class="fa-solid fa-magnifying-glass"></i>
            </button>
            <?php if (!empty($search)): ?>
                <a href="buku.php" class="btn btn-secondary" style="padding: 8px 16px;" title="Reset Pencarian">
                    <i class="fa-solid fa-circle-xmark"></i>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th>Detail Buku</th>
                    <th>Kategori</th>
                    <th>Penulis</th>
                    <th>Penerbit / Tahun</th>
                    <th style="width: 100px; text-align: center;">Stok</th>
                    <th style="width: 180px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($all_buku) > 0): ?>
                    <?php $no = 1; foreach ($all_buku as $b): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td>
                                <div class="book-title-cell">
                                    <?php if (!empty($b['cover_image']) && file_exists('../assets/images/' . $b['cover_image'])): ?>
                                        <img class="book-cover-mini" src="../assets/images/<?= htmlspecialchars($b['cover_image']) ?>" alt="Cover Buku">
                                    <?php else: ?>
                                        <!-- Fallback cover visually appealing icon -->
                                        <div class="book-cover-mini" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--bg-tertiary) 0%, rgba(99, 102, 241, 0.2) 100%);">
                                            <i class="fa-solid fa-book" style="color: var(--text-secondary); font-size: 1.2rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong style="display: block; font-size: 1rem; color: var(--text-primary);"><?= htmlspecialchars($b['judul']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(99,102,241,0.15); color:#a5b4fc;">
                                    <?= htmlspecialchars($b['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($b['penulis']) ?></td>
                            <td><?= htmlspecialchars($b['penerbit']) ?> (<?= htmlspecialchars($b['tahun_terbit']) ?>)</td>
                            <td style="text-align: center;">
                                <?php if ($b['jumlah'] > 0): ?>
                                    <strong style="color: var(--success);"><?= $b['jumlah'] ?></strong>
                                <?php else: ?>
                                    <span style="color: var(--danger); font-weight: bold;">Habis</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($role === 'admin'): ?>
                                    <div class="action-buttons" style="justify-content: center;">
                                        <a href="buku.php?edit=<?= $b['id'] ?>" class="btn-icon edit" title="Edit Buku">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </a>
                                        <a href="buku.php?delete=<?= $b['id'] ?>" class="btn-icon delete btn-delete-confirm" data-message="Apakah Anda yakin ingin menghapus buku '<?= htmlspecialchars($b['judul']) ?>'?" title="Hapus Buku">
                                            <i class="fa-solid fa-trash-can"></i> Hapus
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <?php if ($b['jumlah'] > 0): ?>
                                        <a href="buku.php?action=pinjam&id=<?= $b['id'] ?>" class="btn btn-success btn-delete-confirm" data-message="Apakah Anda ingin meminjam buku '<?= htmlspecialchars($b['judul']) ?>'?" style="padding: 6px 12px; font-size: 0.85rem;">
                                            <i class="fa-solid fa-hand-holding-hand"></i> Pinjam Buku
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem; cursor: not-allowed; opacity: 0.5;" disabled>
                                            Tidak Tersedia
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px 0;">
                            Tidak ada data buku yang sesuai.
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
