<?php
/**
 * Halaman Login Perpustakaan
 */
require_once '../config/koneksi.php';

// Jika sudah login, langsung alihkan ke dashboard admin/anggota
if (isset($_SESSION['role'])) {
    header("Location: ../admin/index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        try {
            // Cari user berdasarkan username
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            // Verifikasi user dan password
            if ($user && password_verify($password, $user['password'])) {
                // Set sesi
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap']  = $user['nama_lengkap'];
                $_SESSION['role']          = $user['role'];

                // Alihkan ke dashboard
                header("Location: ../admin/index.php");
                exit();
            } else {
                $error = 'Username atau password salah!';
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
    <title>Login - E-Perpustakaan</title>
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
                <h1><i class="fa-solid fa-book-open"></i> E-Perpus</h1>
                <p>Silakan masuk ke akun perpustakaan Anda</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Masukkan username" required autofocus autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Masuk <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>

            <div class="auth-footer">
                Belum punya akun? <a href="register.php">Daftar Sekarang</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
