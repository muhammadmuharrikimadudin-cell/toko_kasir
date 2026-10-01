<?php
declare(strict_types=1);
// login.php — Halaman Login — PHP 8.3
session_start();
if (!empty($_SESSION['user_id'])) {
    $r = $_SESSION['role'] ?? '';
    if ($r === 'cashier') header('Location: kasir.php');
    elseif ($r === 'karyawan') header('Location: employee.php');
    else header('Location: dashboard.php');
    exit;
}

require_once 'config/database.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password && isset($_POST['role'])) {
        $role = $_POST['role'];
        $db   = getDB();
        $stmt = $db->prepare('SELECT id, name, password, role, status FROM users WHERE username = :u AND role = :r LIMIT 1');
        $stmt->execute([':u' => $username, ':r' => $role]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                $error = 'Akun Anda sedang menunggu persetujuan (approval) dari Admin.';
            } elseif ($user['status'] === 'rejected') {
                $error = 'Pendaftaran akun Anda ditolak oleh Admin.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['role']    = $user['role'];
                
                $r = $user['role'];
                if ($r === 'cashier') header('Location: kasir.php');
                elseif ($r === 'karyawan') header('Location: employee.php');
                else header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Username atau password salah.';
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
    <meta name="description" content="Login ke aplikasi Kasir Toko Point of Sale">
    <title>Login — Kasir Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="login-bg">

<div class="flex min-h-screen w-full">
    <!-- Left Decorative Panel -->
    <div class="hidden lg:flex flex-1 items-center justify-center relative overflow-hidden">
        <!-- Floating circles decoration -->
        <div class="absolute w-64 h-64 rounded-full bg-white/10 -top-20 -left-20"></div>
        <div class="absolute w-40 h-40 rounded-full bg-white/10 bottom-20 right-10"></div>
        <div class="absolute w-20 h-20 rounded-full bg-white/15 top-1/2 left-1/4"></div>
        <div class="text-center z-10 px-12">
            <div class="w-24 h-24 rounded-2xl bg-white/20 flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="text-white text-4xl font-bold mb-3">Kasir Toko</h1>
            <p class="text-white/80 text-lg">Point of Sale Modern<br>untuk bisnis Anda</p>
            <div class="mt-8 flex gap-4 justify-center">
                <div class="text-center">
                    <p class="text-white font-bold text-2xl">100%</p>
                    <p class="text-white/70 text-sm">Realtime</p>
                </div>
                <div class="w-px bg-white/30"></div>
                <div class="text-center">
                    <p class="text-white font-bold text-2xl">Fast</p>
                    <p class="text-white/70 text-sm">Transaksi</p>
                </div>
                <div class="w-px bg-white/30"></div>
                <div class="text-center">
                    <p class="text-white font-bold text-2xl">Easy</p>
                    <p class="text-white/70 text-sm">To Use</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Form -->
    <div class="flex-1 flex items-center justify-center p-8">
        <div class="login-card w-full max-w-sm">
            <div class="text-center mb-8">
                <p class="text-white/80 text-xs uppercase tracking-widest mb-1">CASHIER APPLICATION</p>
                <h2 class="text-white text-3xl font-bold">CASHIER APPLICATION</h2>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-500/30 border border-red-300/50 text-white rounded-xl px-4 py-3 text-sm mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" autocomplete="off">
                <div class="mb-4">
                    <label class="text-white/90 text-xs font-semibold uppercase tracking-wider block mb-1">USERNAME</label>
                    <input
                        type="text"
                        name="username"
                        id="login-username"
                        class="login-input"
                        placeholder="Username . . ."
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                        autocomplete="username"
                    >
                </div>
                <div class="mb-4">
                    <label class="text-white/90 text-xs font-semibold uppercase tracking-wider block mb-1">ROLE (HAK AKSES)</label>
                    <select name="role" class="login-input" required style="color:#1f2937;">
                        <option value="admin">Admin</option>
                        <option value="cashier">Cashier</option>
                        <option value="karyawan">Karyawan</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="text-white/90 text-xs font-semibold uppercase tracking-wider block mb-1">PASSWORD</label>
                    <input
                        type="password"
                        name="password"
                        id="login-password"
                        class="login-input"
                        placeholder="Password . . ."
                        required
                        autocomplete="current-password"
                    >
                </div>
                <button type="submit" id="btn-login" class="login-btn mt-6">LOGIN</button>
            </form>

            <p class="text-white/70 text-sm text-center mt-5">
                Belum punya akun?
                <a href="register.php" class="text-white font-semibold hover:underline">Daftar</a>
            </p>
            <p class="text-white/50 text-xs text-center mt-3">
                Demo: admin / password
            </p>
        </div>
    </div>
</div>

</body>
</html>
