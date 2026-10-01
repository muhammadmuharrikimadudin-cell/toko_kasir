<?php
declare(strict_types=1);

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

checkRole(['admin']); // Only admin can access this API

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = inputString($_GET, 'action');

match ($method) {
    'GET'    => handleGet($db),
    'POST'   => handlePost($db, $action),
    'DELETE' => handleDelete($db),
    default  => jsonResponse(['error' => 'Method not allowed'], 405),
};

function handleGet(PDO $db): never {
    $stmt = $db->query("SELECT id, name, username, role, status, created_at FROM users ORDER BY created_at DESC");
    jsonResponse(['data' => $stmt->fetchAll()]);
}

function handlePost(PDO $db, string $action): never {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    
    if ($action === 'status') {
        $status = trim($input['status'] ?? '');
        if ($id <= 0 || !in_array($status, ['approved', 'rejected'])) {
            jsonResponse(['error' => 'Invalid data'], 400);
        }
        $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $status, ':id' => $id]);
        jsonResponse(['success' => true, 'message' => "Status user berhasil diubah menjadi $status"]);
    }
    $name = trim($input['name'] ?? '');
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    $role = trim($input['role'] ?? '');
    
    if (!$name || !$username || !$role) {
        jsonResponse(['error' => 'Nama, username, dan role harus diisi'], 400);
    }
    
    if ($id > 0) {
        // Update user
        if ($password) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET name = :name, username = :username, password = :password, role = :role WHERE id = :id");
            $stmt->execute([':name' => $name, ':username' => $username, ':password' => $hashed, ':role' => $role, ':id' => $id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = :name, username = :username, role = :role WHERE id = :id");
            $stmt->execute([':name' => $name, ':username' => $username, ':role' => $role, ':id' => $id]);
        }
        jsonResponse(['success' => true, 'message' => 'User updated']);
    } else {
        // Create user
        if (!$password) {
            jsonResponse(['error' => 'Password harus diisi untuk user baru'], 400);
        }
        $check = $db->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $check->execute([':u' => $username]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'Username sudah digunakan'], 400);
        }
        
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (name, username, password, role, status) VALUES (:name, :username, :password, :role, 'approved')");
        $stmt->execute([':name' => $name, ':username' => $username, ':password' => $hashed, ':role' => $role]);
        jsonResponse(['success' => true, 'message' => 'User created']);
    }
}

function handleDelete(PDO $db): never {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['error' => 'Invalid ID'], 400);
    }
    if ($id === (int)($_SESSION['user_id'] ?? 0)) {
        jsonResponse(['error' => 'Tidak dapat menghapus akun Anda sendiri'], 400);
    }
    
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    jsonResponse(['success' => true, 'message' => 'User deleted']);
}
