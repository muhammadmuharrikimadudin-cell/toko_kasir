<?php
declare(strict_types=1);
// register.php — Halaman Registrasi — PHP 8.3
session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']     ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($nama && $username && $password) {
        $db   = getDB();
        $check = $db->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
        $check->execute([':u' => $username]);
        if ($check->fetch()) {
            $error = 'Username sudah digunakan.';
        } else {
            $hashed = password_hash(password: $password, algo: PASSWORD_BCRYPT);
            $stmt = $db->prepare("INSERT INTO users (name, username, password, role) VALUES (:nama, :u, :pass, 'cashier')");
            $stmt->execute([':nama' => $nama, ':u' => $username, ':pass' => $hashed]);
            $success = 'Akun berhasil dibuat dan menunggu persetujuan (approval) Admin!';
        }
    } else {
        $error = 'Harap isi semua field.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Registrasi akun baru Kasir Toko">
    <title>Registrasi — Kasir Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="login-bg">

<div class="flex min-h-screen w-full items-center justify-center p-8">
    <div class="login-card w-full max-w-sm">
        <div class="text-center mb-8">
            <p class="text-white/80 text-xs uppercase tracking-widest mb-1">CASHIER APPLICATION</p>
            <h2 class="text-white text-3xl font-bold">REGISTRATION</h2>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-500/30 border border-red-300/50 text-white rounded-xl px-4 py-3 text-sm mb-4">
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="bg-green-500/30 border border-green-300/50 text-white rounded-xl px-4 py-3 text-sm mb-4">
            <?= htmlspecialchars($success) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" autocomplete="off">
            <div class="mb-4">
                <label class="text-white/90 text-xs font-semibold uppercase tracking-wider block mb-1">Nama Lengkap</label>
                <input
                    type="text"
                    name="nama"
                    id="reg-nama"
                    class="login-input"
                    placeholder="masukkan namamu . . ."
                    value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>"
                    required
                >
            </div>
            <div class="mb-4">
                <label class="text-white/90 text-xs font-semibold uppercase tracking-wider block mb-1">USERNAME</label>
                <input
                    type="text"
                    name="username"
                    id="reg-username"
                    class="login-input"
                    placeholder="Username . . ."
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required
                    autocomplete="username"
                >
            </div>
            <div class="mb-2">
                <label class="text-white/90 text-xs font-semibold uppercase tracking-wider block mb-1">PASSWORD</label>
                <input
                    type="password"
                    name="password"
                    id="reg-password"
                    class="login-input"
                    placeholder="Password . . ."
                    required
                    autocomplete="new-password"
                >
            </div>
            <button type="submit" id="btn-register" class="login-btn mt-6">DAFTAR</button>
        </form>

        <p class="text-white/70 text-sm text-center mt-5">
            Sudah punya akun?
            <a href="login.php" class="text-white font-semibold hover:underline">Login</a>
        </p>
    </div>
</div>

</body>
</html>
