<?php
declare(strict_types=1);
// settings.php — Modul 5: Pengaturan Toko — PHP 8.3
session_start();
require_once 'config/database.php';
requireLogin();

$pageTitle  = 'Settings';
$activePage = 'settings';

$db = getDB();
$setting = $db->query("SELECT * FROM shop_settings LIMIT 1")->fetch();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_shop') {
        $nama  = trim($_POST['nama_toko'] ?? '');
        $alamat= trim($_POST['alamat']    ?? '');
        $noHp  = trim($_POST['no_hp']     ?? '');

        if ($nama) {
            $stmt = $db->prepare("UPDATE shop_settings SET nama_toko=?, alamat=?, no_hp=? WHERE id=1");
            $stmt->execute([$nama, $alamat, $noHp]);
            $success = 'Pengaturan toko berhasil disimpan.';
            $setting = $db->query("SELECT * FROM shop_settings LIMIT 1")->fetch();
        } else {
            $error = 'Nama toko tidak boleh kosong.';
        }
    } elseif ($action === 'change_password') {
        $oldPass  = $_POST['old_password']  ?? '';
        $newPass  = $_POST['new_password']  ?? '';
        $confPass = $_POST['confirm_password'] ?? '';

        $user = getCurrentUser();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!password_verify($oldPass, $row['password'])) {
            $error = 'Password lama tidak sesuai.';
        } elseif ($newPass !== $confPass) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (strlen($newPass) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } else {
            $hashed = password_hash(password: $newPass, algo: PASSWORD_BCRYPT);
            $stmt2  = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt2->execute([$hashed, $user['id']]);
            $success = 'Password berhasil diubah.';
        }
    }
}

include 'templates/header.php';
include 'templates/sidebar.php';
$user = getCurrentUser();
?>

<!-- ============================================================
     SETTINGS PAGE
     ============================================================ -->
<div class="p-6 max-w-2xl">

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-800">Settings</h1>
        <p class="text-sm text-gray-500">Pengaturan toko dan akun</p>
    </div>

    <?php if ($success): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm mb-4 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm mb-4 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Shop Info -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-4">
        <h2 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-sm" style="background:#4DB9F2;">🏪</span>
            Informasi Toko
        </h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update_shop">
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Nama Toko</label>
                    <input type="text" name="nama_toko" id="nama_toko"
                        class="input-field"
                        value="<?= htmlspecialchars($setting['nama_toko'] ?? '') ?>"
                        placeholder="Nama toko Anda" required>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">No. Telepon</label>
                    <input type="text" name="no_hp" id="no_hp"
                        class="input-field"
                        value="<?= htmlspecialchars($setting['no_hp'] ?? '') ?>"
                        placeholder="08xxx">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Alamat</label>
                    <textarea name="alamat" id="shop_alamat" class="input-field" rows="3"
                        placeholder="Alamat toko..."><?= htmlspecialchars($setting['alamat'] ?? '') ?></textarea>
                </div>
            </div>
            <button type="submit" id="btn-save-shop"
                class="mt-5 px-6 py-2.5 rounded-xl text-white font-semibold text-sm transition-all hover:opacity-90"
                style="background:#4DB9F2;">
                Simpan Perubahan
            </button>
        </form>
    </div>

    <!-- Account Info -->
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-4">
        <h2 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-sm" style="background:#22c55e;">👤</span>
            Akun Saya
        </h2>
        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl mb-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg"
                 style="background:#4DB9F2;">
                <?= strtoupper(substr($user['name'], 0, 1)) ?>
            </div>
            <div>
                <p class="font-semibold text-gray-800"><?= htmlspecialchars($user['name']) ?></p>
                <p class="text-sm text-gray-400 capitalize"><?= htmlspecialchars($user['role']) ?></p>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <span class="w-8 h-8 rounded-xl flex items-center justify-center text-white text-sm" style="background:#f97316;">🔒</span>
            Ganti Password
        </h2>
        <form method="POST" action="">
            <input type="hidden" name="action" value="change_password">
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Password Lama</label>
                    <input type="password" name="old_password" id="old_password" class="input-field" placeholder="••••••••" required autocomplete="current-password">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Password Baru</label>
                    <input type="password" name="new_password" id="new_password" class="input-field" placeholder="Min. 6 karakter" required autocomplete="new-password">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="input-field" placeholder="Ulangi password baru" required autocomplete="new-password">
                </div>
            </div>
            <button type="submit" id="btn-change-pass"
                class="mt-5 px-6 py-2.5 rounded-xl text-white font-semibold text-sm transition-all hover:opacity-90"
                style="background:#f97316;">
                Ganti Password
            </button>
        </form>
    </div>

</div>

<?php include 'templates/footer.php'; ?>
