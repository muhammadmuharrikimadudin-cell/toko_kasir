<?php
declare(strict_types=1);

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// Pastikan hanya admin yang bisa melakukan reset (keamanan dasar)
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Akses ditolak.']);
    exit;
}

try {
    $db = getDB();
    
    // 3. Matikan foreign key check
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // 1. Jalankan TRUNCATE pada tabel yang diminta
    // (Abaikan error jika tabel tidak ada di environment tertentu dengan menggunakan try-catch di dalam)
    $tablesToTruncate = ['transaction_details', 'transactions', 'product_lots'];
    foreach ($tablesToTruncate as $table) {
        try {
            $db->exec("TRUNCATE TABLE {$table}");
        } catch (PDOException $e) {
            // Lanjutkan jika tabel tidak eksis, atau lempar error jika ingin strict
        }
    }
    
    // 2. Setel nilai stok seluruh produk menjadi 0
    $db->exec('UPDATE products SET stock = 0');
    
    // Nyalakan kembali foreign key check
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    // 4. Kirimkan response JSON sukses
    echo json_encode([
        'success' => true,
        'message' => 'Database berhasil di-reset!'
    ]);

} catch (Exception $e) {
    // Pastikan constraint dinyalakan lagi jika terjadi error fatal
    if (isset($db)) {
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal melakukan reset: ' . $e->getMessage()
    ]);
}
