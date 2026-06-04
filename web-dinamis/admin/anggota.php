<?php
/**
 * CRUD Kelola Anggota (Eksklusif Admin)
 */
require_once 'header.php';

// Proteksi level admin
if ($role !== 'admin') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

// Inisialisasi variabel Form
$edit_mode = false;
$edit_id = '';
$username_val = '';
$nama_lengkap_val = '';
$email_val = '';

// Proses Simpan / Tambah / Edit Anggota
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $username_input     = trim($_POST['username']);
        $nama_lengkap_input = trim($_POST['nama_lengkap']);
        $email_input        = trim($_POST['email']);
        $password_input     = trim($_POST['password']);
        
        if (empty($username_input) || empty($nama_lengkap_input) || empty($email_input)) {
            $error = 'Nama lengkap, email, dan username wajib diisi!';
        } else {
            try {
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    // 1. Edit Data Anggota
                    $id = intval($_POST['id']);
                    
                    // Cek username unik untuk id yang berbeda
                    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND id != :id");
                    $stmt_check->execute(['username' => $username_input, 'id' => $id]);
                    
                    if ($stmt_check->fetchColumn() > 0) {
                        $error = 'Username sudah digunakan oleh akun lain!';
                    } else {
                        // Update dasar
                        if (!empty($password_input)) {
                            // Update dengan password baru
                            if (strlen($password_input) < 6) {
                                $error = 'Password minimal harus 6 karakter!';
                            } else {
                                $hashedPassword = password_hash($password_input, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare("UPDATE users SET username = :username, password = :password, nama_lengkap = :nama, email = :email WHERE id = :id");
                                $stmt->execute([
                                    'username' => $username_input,
                                    'password' => $hashedPassword,
                                    'nama'     => $nama_lengkap_input,
                                    'email'    => $email_input,
                                    'id'       => $id
                                ]);
                                $success = 'Data anggota dan password berhasil diperbarui!';
                            }
                        } else {
                            // Update tanpa mengubah password
                            $stmt = $pdo->prepare("UPDATE users SET username = :username, nama_lengkap = :nama, email = :email WHERE id = :id");
                            $stmt->execute([
                                'username' => $username_input,
                                'nama'     => $nama_lengkap_input,
                                'email'    => $email_input,
                                'id'       => $id
                            ]);
                            $success = 'Data anggota berhasil diperbarui!';
                        }
                    }
                } else {
                    // 2. Tambah Anggota Baru
                    if (empty($password_input)) {
                        $error = 'Password wajib diisi untuk anggota baru!';
                    } elseif (strlen($password_input) < 6) {
                        $error = 'Password minimal harus 6 karakter!';
                    } else {
                        // Cek username unik
                        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                        $stmt_check->execute(['username' => $username_input]);
                        
                        if ($stmt_check->fetchColumn() > 0) {
                            $error = 'Username sudah terdaftar!';
                        } else {
                            $hashedPassword = password_hash($password_input, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, role) VALUES (:username, :password, :nama, :email, 'anggota')");
                            $stmt->execute([
                                'username' => $username_input,
                                'password' => $hashedPassword,
                                'nama'     => $nama_lengkap_input,
                                'email'    => $email_input
                            ]);
                            $success = 'Anggota baru berhasil ditambahkan!';
                            
                            // Reset input jika berhasil
                            $username_val = $nama_lengkap_val = $email_val = '';
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan database: ' . $e->getMessage();
            }
        }
    }
}

// Proses Hapus Anggota
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role = 'anggota'");
        $stmt->execute(['id' => $delete_id]);
        $success = 'Anggota berhasil dihapus dari sistem!';
    } catch (PDOException $e) {
        $error = 'Gagal menghapus anggota: ' . $e->getMessage();
    }
}

// Ambil Data Edit Anggota
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'anggota'");
        $stmt->execute(['id' => $edit_id]);
        $user_edit = $stmt->fetch();
        if ($user_edit) {
            $username_val     = $user_edit['username'];
            $nama_lengkap_val = $user_edit['nama_lengkap'];
            $email_val        = $user_edit['email'];
        } else {
            $error = 'Data anggota tidak ditemukan!';
            $edit_mode = false;
        }
    } catch (PDOException $e) {
        $error = 'Gagal memuat data anggota: ' . $e->getMessage();
    }
}

// Ambil semua daftar anggota untuk tabel
try {
    $all_anggota = $pdo->query("SELECT * FROM users WHERE role = 'anggota' ORDER BY nama_lengkap ASC")->fetchAll();
} catch (PDOException $e) {
    $error = 'Gagal memuat data anggota: ' . $e->getMessage();
}
?>

<div class="page-title">
    <h1>Kelola Anggota Perpustakaan</h1>
    <p>Manajemen pendaftaran dan hak akses akun Anggota Perpustakaan.</p>
</div>

<div class="grid-form" style="margin-top: 30px;">
    <!-- Sisi Form Tambah / Edit -->
    <div class="data-card" style="height: fit-content;">
        <h2><i class="fa-solid <?= $edit_mode ? 'fa-user-pen' : 'fa-user-plus' ?>" style="color: var(--accent-primary); margin-right: 8px;"></i> <?= $edit_mode ? 'Edit Data Anggota' : 'Registrasi Anggota Baru' ?></h2>
        
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

        <form action="anggota.php" method="POST" style="margin-top: 20px;">
            <input type="hidden" name="action" value="save">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?= $edit_id ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-input" placeholder="Nama lengkap anggota" value="<?= htmlspecialchars($nama_lengkap_val) ?>" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-input" placeholder="contoh@domain.com" value="<?= htmlspecialchars($email_val) ?>" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-input" placeholder="Username login" value="<?= htmlspecialchars($username_val) ?>" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password <?= $edit_mode ? '(Kosongkan jika tidak diubah)' : '' ?></label>
                <input type="password" id="password" name="password" class="form-input" placeholder="<?= $edit_mode ? 'Masukkan password baru' : 'Masukkan password login' ?>" <?= $edit_mode ? '' : 'required' ?>>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary">
                    <?= $edit_mode ? 'Perbarui Anggota' : 'Daftarkan' ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="anggota.php" class="btn btn-secondary">Batal</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Sisi Tabel Daftar Anggota -->
    <div class="data-card">
        <div class="card-header">
            <h2>Daftar Anggota</h2>
        </div>

        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Lengkap</th>
                        <th>Username / Email</th>
                        <th>Terdaftar</th>
                        <th style="width: 150px; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($all_anggota) > 0): ?>
                        <?php $no = 1; foreach ($all_anggota as $ang): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="avatar" style="width:32px; height:32px; font-size: 0.8rem;"><?= strtoupper(substr($ang['nama_lengkap'], 0, 2)) ?></div>
                                        <strong><?= htmlspecialchars($ang['nama_lengkap']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span style="display: block; font-weight: 500;">@<?= htmlspecialchars($ang['username']) ?></span>
                                    <span style="font-size:0.8rem; color: var(--text-secondary);"><?= htmlspecialchars($ang['email']) ?></span>
                                </td>
                                <td style="font-size:0.85rem; color: var(--text-secondary);"><?= date('d-m-Y H:i', strtotime($ang['created_at'])) ?></td>
                                <td style="text-align: center;">
                                    <div class="action-buttons" style="justify-content: center;">
                                        <a href="anggota.php?edit=<?= $ang['id'] ?>" class="btn-icon edit" title="Edit Anggota">
                                            <i class="fa-solid fa-user-gear"></i>
                                        </a>
                                        <a href="anggota.php?delete=<?= $ang['id'] ?>" class="btn-icon delete btn-delete-confirm" data-message="Apakah Anda yakin ingin menghapus akun anggota '<?= htmlspecialchars($ang['nama_lengkap']) ?>'?" title="Hapus Anggota">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px 0;">
                                Belum ada anggota yang terdaftar.
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
