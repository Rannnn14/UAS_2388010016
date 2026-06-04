<?php
/**
 * Halaman Registrasi Anggota Baru
 */
require_once '../config/koneksi.php';

// Jika sudah login, langsung alihkan ke dashboard
if (isset($_SESSION['role'])) {
    header("Location: ../admin/index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username     = trim($_POST['username']);
    $password     = trim($_POST['password']);
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email        = trim($_POST['email']);
    $role         = 'anggota'; // Pendaftaran mandiri otomatis menjadi Anggota

    if (empty($username) || empty($password) || empty($nama_lengkap) || empty($email)) {
        $error = 'Semua field wajib diisi!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus 6 karakter!';
    } else {
        try {
            // Cek apakah username sudah digunakan
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Username sudah digunakan oleh akun lain!';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Masukkan data ke database
                $insert = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, email, role) VALUES (:username, :password, :nama_lengkap, :email, :role)");
                $insert->execute([
                    'username'     => $username,
                    'password'     => $hashedPassword,
                    'nama_lengkap' => $nama_lengkap,
                    'email'        => $email,
                    'role'         => $role
                ]);

                $success = 'Pendaftaran berhasil! Silakan login.';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Anggota - E-Perpustakaan</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- FontAwesome untuk ikon gratis -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Glowing background elements -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Daftar Anggota</h1>
                <p>Bergabunglah untuk meminjam buku favorit Anda</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-input" placeholder="Masukkan nama lengkap Anda" required autocomplete="off" value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="contoh@domain.com" required autocomplete="off" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Pilih username" required autocomplete="off" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Pilih password (min. 6 karakter)" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Daftar Sekarang <i class="fa-solid fa-user-plus"></i>
                </button>
            </form>

            <div class="auth-footer">
                Sudah punya akun? <a href="login.php">Masuk di sini</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
