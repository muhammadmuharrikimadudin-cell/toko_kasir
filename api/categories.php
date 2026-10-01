<?php
declare(strict_types=1);

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->query('SELECT c.id, c.name, COUNT(p.id) AS total_products FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name ASC');
    jsonResponse(['data' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($data['name'] ?? '');
    
    if ($name === '') {
        jsonResponse(['error' => 'Nama kategori tidak boleh kosong'], 400);
    }
    
    // Check if exists
    $chk = $db->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
    $chk->execute([':name' => $name]);
    if ($chk->fetch()) {
        jsonResponse(['error' => 'Kategori sudah ada'], 400);
    }
    
    $stmt = $db->prepare('INSERT INTO categories (name) VALUES (:name)');
    $stmt->execute([':name' => $name]);
    jsonResponse(['success' => true, 'id' => $db->lastInsertId()]);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'ID tidak valid'], 400);
    }
    
    // Check if used
    $chk = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
    $chk->execute([':id' => $id]);
    if ($chk->fetchColumn() > 0) {
        jsonResponse(['error' => 'Tidak bisa dihapus, kategori sedang digunakan oleh produk'], 400);
    }
    
    $stmt = $db->prepare('DELETE FROM categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
