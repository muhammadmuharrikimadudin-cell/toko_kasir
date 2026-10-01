<?php
declare(strict_types=1);

// api/upload.php — Upload Gambar Produk
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(data: ['error' => 'Unauthorized'], statusCode: 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(data: ['error' => 'Method not allowed'], statusCode: 405);
}

// Validasi file ada
if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['gambar']['error'] ?? -1;
    $errMsg  = match ($errCode) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran file terlalu besar (maks 5MB)',
        UPLOAD_ERR_NO_FILE  => 'Tidak ada file yang diupload',
        default             => 'Gagal upload file (kode: ' . $errCode . ')',
    };
    jsonResponse(data: ['error' => $errMsg], statusCode: 400);
}

$file     = $_FILES['gambar'];
$maxSize  = 5 * 1024 * 1024; // 5MB
$allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

// Validasi ukuran
if ($file['size'] > $maxSize) {
    jsonResponse(data: ['error' => 'Ukuran file melebihi 5MB'], statusCode: 400);
}

// Validasi tipe MIME
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowed, true)) {
    jsonResponse(data: ['error' => 'Tipe file tidak didukung. Gunakan JPG, PNG, WEBP, atau GIF'], statusCode: 400);
}

// Buat folder uploads jika belum ada
$uploadDir = dirname(__DIR__) . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generate nama file unik
$ext      = match ($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    default      => 'jpg',
};
$filename = 'prod_' . uniqid() . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    jsonResponse(data: ['error' => 'Gagal menyimpan file ke server'], statusCode: 500);
}

// Kembalikan URL relatif yang bisa diakses browser
$baseUrl  = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST']
            . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/api');
$imageUrl = $baseUrl . '/uploads/' . $filename;

jsonResponse(['success' => true, 'url' => $imageUrl, 'filename' => $filename]);
