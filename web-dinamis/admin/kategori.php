<?php
/**
 * CRUD Kategori Buku (Eksklusif Admin)
 */
require_once 'header.php';

// Proteksi level admin
if ($role !== 'admin') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

// Mode edit
$edit_mode = false;
$edit_id = '';
$edit_name = '';

// 1. Aksi Simpan / Tambah / Edit Kategori
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'save') {
            $nama_kategori = trim($_POST['nama_kategori']);
            
            if (empty($nama_kategori)) {
                $error = 'Nama kategori tidak boleh kosong!';
            } else {
                try {
                    if (isset($_POST['id']) && !empty($_POST['id'])) {
                        // Proses Edit
                        $id = intval($_POST['id']);
                        $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = :nama WHERE id = :id");
                        $stmt->execute(['nama' => $nama_kategori, 'id' => $id]);
                        $success = 'Kategori berhasil diperbarui!';
                    } else {
                        // Proses Tambah Baru
                        $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori) VALUES (:nama)");
                        $stmt->execute(['nama' => $nama_kategori]);
                        $success = 'Kategori baru berhasil ditambahkan!';
                    }
                } catch (PDOException $e) {
                    $error = 'Gagal menyimpan kategori: ' . $e->getMessage();
                }
            }
        }
    }
}

// 2. Aksi Hapus Kategori
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = :id");
        $stmt->execute(['id' => $delete_id]);
        $success = 'Kategori berhasil dihapus!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus kategori (mungkin kategori sedang digunakan oleh data buku): ' . $e->getMessage();
    }
}

// 3. Aksi Ambil Data Edit Kategori
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM kategori WHERE id = :id");
        $stmt->execute(['id' => $edit_id]);
        $kategori_edit = $stmt->fetch();
        if ($kategori_edit) {
            $edit_name = $kategori_edit['nama_kategori'];
        } else {
            $error = 'Data kategori tidak ditemukan!';
            $edit_mode = false;
        }
    } catch (PDOException $e) {
        $error = 'Gagal mengambil data: ' . $e->getMessage();
    }
}

// 4. Ambil Semua Kategori untuk Tampilan Tabel
try {
    $all_kategori = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal memuat kategori: ' . $e->getMessage();
}
?>

<div class="page-title">
    <h1>Kelola Kategori Buku</h1>
    <p>Kelola klasifikasi kategori buku di perpustakaan Anda.</p>
</div>

<!-- Layout Dua Kolom -->
<div class="grid-form" style="margin-top: 30px;">
    <!-- Kolom Form Tambah/Edit -->
    <div class="data-card" style="height: fit-content;">
        <h2><i class="fa-solid <?= $edit_mode ? 'fa-pen-to-square' : 'fa-plus' ?>" style="color: var(--accent-primary); margin-right: 8px;"></i> <?= $edit_mode ? 'Edit Kategori' : 'Tambah Kategori Baru' ?></h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-top: 15px;">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="margin-top: 15px;">
                <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form action="kategori.php" method="POST" style="margin-top: 20px;">
            <input type="hidden" name="action" value="save">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?= $edit_id ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nama_kategori" class="form-label">Nama Kategori</label>
                <input type="text" id="nama_kategori" name="nama_kategori" class="form-input" placeholder="Masukkan nama kategori (misal: Fiksi, Komputer)" value="<?= htmlspecialchars($edit_name) ?>" required autocomplete="off">
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <?= $edit_mode ? 'Perbarui' : 'Simpan' ?> Kategori
                </button>
                <?php if ($edit_mode): ?>
                    <a href="kategori.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Kolom Daftar Kategori -->
    <div class="data-card">
        <div class="card-header">
            <h2>Daftar Kategori</h2>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">No</th>
                        <th>Nama Kategori</th>
                        <th style="width: 150px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($all_kategori) > 0): ?>
                        <?php $no = 1; foreach ($all_kategori as $k): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><strong><?= htmlspecialchars($k['nama_kategori']) ?></strong></td>
                                <td style="text-align: center;">
                                    <div class="action-buttons" style="justify-content: center;">
                                        <a href="kategori.php?edit=<?= $k['id'] ?>" class="btn-icon edit" title="Edit Kategori">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <a href="kategori.php?delete=<?= $k['id'] ?>" class="btn-icon delete btn-delete-confirm" data-message="Apakah Anda yakin ingin menghapus kategori '<?= htmlspecialchars($k['nama_kategori']) ?>'?" title="Hapus Kategori">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 30px 0;">
                                Belum ada data kategori buku.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>
