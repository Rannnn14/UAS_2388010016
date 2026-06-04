<?php
/**
 * Halaman Reset Password Perpustakaan
 */
require_once '../config/koneksi.php';

// Jika sudah login, langsung alihkan ke dashboard admin/anggota
if (isset($_SESSION['role'])) {
    header("Location: ../admin/index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_new_password']);

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua kolom wajib diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password baru tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal harus terdiri dari 6 karakter!';
    } else {
        try {
            // Cari user berdasarkan username dan email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // Update password baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $update_stmt->execute(['password' => $hashed_password, 'id' => $user['id']]);
                
                $success = 'Password berhasil direset! Silakan masuk menggunakan password baru.';
            } else {
                $error = 'Username atau Email tidak terdaftar/tidak cocok!';
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
    <title>Reset Password - E-Perpustakaan</title>
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
                <h1><i class="fa-solid fa-key"></i> Reset Password</h1>
                <p>Masukkan username dan email terdaftar untuk menyetel ulang password</p>
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
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" placeholder="Username Anda" required autocomplete="off" value="<?= isset($username) && empty($success) ? htmlspecialchars($username) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Terdaftar</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="email@contoh.com" required autocomplete="off" value="<?= isset($email) && empty($success) ? htmlspecialchars($email) : '' ?>">
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Masukkan password baru" required>
                </div>

                <div class="form-group">
                    <label for="confirm_new_password" class="form-label">Konfirmasi Password Baru</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-input" placeholder="Ulangi password baru" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Reset Password <i class="fa-solid fa-shield-halved"></i>
                </button>
            </form>

            <div class="auth-footer">
                Ingat password Anda? <a href="login.php">Masuk Sekarang</a>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
