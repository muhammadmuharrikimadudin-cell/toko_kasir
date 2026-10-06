<?php
declare(strict_types=1);

// api/products.php — CRUD Produk + Restock
// Skema: products (id, category_id, barcode, name, buy_price, sell_price, stock, min_stock)
// Endpoint restock: POST api/products.php?action=restock

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(data: ['error' => 'Unauthorized'], statusCode: 401);
}

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = inputString($_GET, 'action');

// Routing: POST dengan action=restock ditangani secara khusus
if ($method === 'POST' && $action === 'restock') {
    handleRestock($db);
}

match ($method) {
    'GET'    => handleGet($db),
    'POST'   => handlePost($db),
    'PUT'    => handlePut($db),
    'DELETE' => handleDelete($db),
    default  => jsonResponse(data: ['error' => 'Method not allowed'], statusCode: 405),
};

// ---- Handlers ----

function handleGet(PDO $db): never
{
    $action = inputString($_GET, 'action');
    if ($action === 'fifo_details') {
        $productId = inputInt($_GET, 'id');
        $stmt = $db->prepare('
            SELECT pd.id, p.created_at, pd.buy_price, pd.qty, pd.remaining_qty 
            FROM purchase_details pd 
            JOIN purchases p ON pd.purchase_id = p.id 
            WHERE pd.product_id = :product_id 
            ORDER BY p.created_at ASC, pd.id ASC
        ');
        $stmt->execute([':product_id' => $productId]);
        jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    $search = inputString($_GET, 'search');
    $limit  = max(1, inputInt($_GET, 'limit', 50));
    $page   = max(1, inputInt($_GET, 'page', 1));
    $offset = ($page - 1) * $limit;

    if ($search !== '') {
        $stmt = $db->prepare(<<<SQL
            SELECT p.id,
                   p.barcode AS kode,
                   p.name    AS nama,
                   p.buy_price  AS harga_beli,
                   p.sell_price AS harga_jual,
                   p.stock   AS stok,
                   p.min_stock,
                   c.name    AS kategori,
                   c.id      AS category_id,
                   p.imageUrl
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.name LIKE :search1 OR p.barcode LIKE :search2
            ORDER BY c.name ASC, p.name ASC
            LIMIT :limit OFFSET :offset
        SQL);
        $stmt->bindValue(':search1', "%{$search}%");
        $stmt->bindValue(':search2', "%{$search}%");
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    } else {
        $stmt = $db->prepare(<<<SQL
            SELECT p.id,
                   p.barcode AS kode,
                   p.name    AS nama,
                   p.buy_price  AS harga_beli,
                   p.sell_price AS harga_jual,
                   p.stock   AS stok,
                   p.min_stock,
                   c.name    AS kategori,
                   p.imageUrl
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY c.name ASC, p.name ASC
            LIMIT :limit OFFSET :offset
        SQL);
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }

    $stmt->execute();
    jsonResponse(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function handlePost(PDO $db): never
{
    $data = json_decode(file_get_contents('php://input'), associative: true) ?? [];

    // Cari category_id berdasarkan nama atau pakai default 8 (Lainnya)
    $catId = inputInt($data, 'category_id', 0);
    if ($catId === 0) {
        $catName = inputString($data, 'kategori', 'Lainnya');
        $catStmt = $db->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $catStmt->execute([':name' => $catName]);
        $catId = (int)($catStmt->fetchColumn() ?: 0);
        if ($catId === 0) {
            // insert kategori baru
            $db->prepare('INSERT INTO categories (name) VALUES (:name)')->execute([':name' => $catName]);
            $catId = (int)$db->lastInsertId();
        }
    }

    // Generate barcode jika kosong
    $barcode = inputString($data, 'kode');
    if ($barcode === '') {
        $barcode = 'PRD' . strtoupper(substr(uniqid(), -6));
    }

    $buyPrice  = inputFloat($data, 'harga_beli');
    $initStock = inputInt($data, 'stok');

    $stmt = $db->prepare(<<<SQL
        INSERT INTO products (barcode, name, category_id, buy_price, sell_price, stock, min_stock, imageUrl)
        VALUES (:barcode, :name, :category_id, :buy_price, :sell_price, :stock, :min_stock, :imageUrl)
    SQL);

    $stmt->execute([
        ':barcode'     => $barcode,
        ':name'        => inputString($data, 'nama'),
        ':category_id' => $catId,
        ':buy_price'   => $buyPrice,
        ':sell_price'  => inputFloat($data, 'harga_jual'),
        ':stock'       => $initStock,
        ':min_stock'   => inputInt($data, 'min_stock', 5),
        ':imageUrl'    => inputString($data, 'imageUrl') ?: null,
    ]);

    $newProductId = (int)$db->lastInsertId();

    // Catat restock awal ke stock_logs jika stok awal > 0
    if ($initStock > 0 && $buyPrice > 0) {
        $db->prepare(
            'INSERT INTO stock_logs (product_id, type, qty, buy_price, notes) VALUES (:pid, \'in\', :qty, :bp, :notes)'
        )->execute([
            ':pid'   => $newProductId,
            ':qty'   => $initStock,
            ':bp'    => $buyPrice,
            ':notes' => 'Stok awal produk baru',
        ]);
        
        $purchaseNo = 'PRC' . strtoupper(substr(uniqid(), -8));
        $totalAmount = $initStock * $buyPrice;
        $db->prepare(
            'INSERT INTO purchases (purchase_number, total_amount, notes) VALUES (:no, :total, :notes)'
        )->execute([
            ':no'    => $purchaseNo,
            ':total' => $totalAmount,
            ':notes' => 'Stok awal: ' . inputString($data, 'nama'),
        ]);
        $purchaseId = (int)$db->lastInsertId();
        
        $db->prepare(
            'INSERT INTO purchase_details (purchase_id, product_id, qty, buy_price, subtotal, remaining_qty) VALUES (:pid, :prod_id, :qty, :buy_price, :subtotal, :remaining_qty)'
        )->execute([
            ':pid'           => $purchaseId,
            ':prod_id'       => $newProductId,
            ':qty'           => $initStock,
            ':buy_price'     => $buyPrice,
            ':subtotal'      => $totalAmount,
            ':remaining_qty' => $initStock,
        ]);
    }

    jsonResponse(['success' => true, 'id' => $newProductId], 201);
}

function handlePut(PDO $db): never
{
    $data = json_decode(file_get_contents('php://input'), associative: true) ?? [];
    
    $id = inputInt($data, 'id');
    if ($id <= 0) {
        jsonResponse(data: ['error' => 'ID tidak valid'], statusCode: 400);
    }

    $catId = inputInt($data, 'category_id', 0);
    if ($catId === 0) {
        $catName = inputString($data, 'kategori', 'Lainnya');
        $catStmt = $db->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $catStmt->execute([':name' => $catName]);
        $catId = (int)($catStmt->fetchColumn() ?: 0);
        if ($catId === 0) {
            $db->prepare('INSERT INTO categories (name) VALUES (:name)')->execute([':name' => $catName]);
            $catId = (int)$db->lastInsertId();
        }
    }

    $newBuyPrice = inputFloat($data, 'harga_beli');
    $newStock    = inputInt($data, 'stok');

    // Ambil data lama untuk menghitung delta restock dan update gambar
    $stmtOld = $db->prepare('SELECT stock, buy_price, imageUrl FROM products WHERE id = :id');
    $stmtOld->execute([':id' => $id]);
    $oldRow      = $stmtOld->fetch(PDO::FETCH_ASSOC) ?: ['stock' => 0, 'buy_price' => 0, 'imageUrl' => null];
    $oldStock    = (int)$oldRow['stock'];
    $oldImage    = $oldRow['imageUrl'];
    $stockDelta  = $newStock - $oldStock; // positif = restock masuk

    $newImage = inputString($data, 'imageUrl') ?: null;
    if ($newImage !== null && $oldImage !== null && $newImage !== $oldImage) {
        if ($oldImage !== 'default.png' && $oldImage !== 'default.jpg') {
            $filepath = __DIR__ . '/../uploads/' . $oldImage;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }

    $stmt = $db->prepare(<<<SQL
        UPDATE products 
        SET name        = :name,
            barcode     = :barcode,
            category_id = :category_id,
            buy_price   = :buy_price,
            sell_price  = :sell_price,
            stock       = :stock,
            min_stock   = :min_stock,
            imageUrl    = :imageUrl
        WHERE id = :id
    SQL);

    $stmt->execute([
        ':id'          => $id,
        ':name'        => inputString($data, 'nama'),
        ':barcode'     => inputString($data, 'kode') ?: null,
        ':category_id' => $catId,
        ':buy_price'   => $newBuyPrice,
        ':sell_price'  => inputFloat($data, 'harga_jual'),
        ':stock'       => $newStock,
        ':min_stock'   => inputInt($data, 'min_stock', 5),
        ':imageUrl'    => $newImage,
    ]);

    // Catat ke stock_logs jika ada penambahan stok (restock)
    if ($stockDelta > 0 && $newBuyPrice > 0) {
        $effectiveBuyPrice = $newBuyPrice > 0 ? $newBuyPrice : (float)$oldRow['buy_price'];
        $db->prepare(
            'INSERT INTO stock_logs (product_id, type, qty, buy_price, notes) VALUES (:pid, \'in\', :qty, :bp, :notes)'
        )->execute([
            ':pid'   => $id,
            ':qty'   => $stockDelta,
            ':bp'    => $effectiveBuyPrice,
            ':notes' => 'Restock / update stok',
        ]);
        
        $purchaseNo = 'PRC' . strtoupper(substr(uniqid(), -8));
        $totalAmount = $stockDelta * $effectiveBuyPrice;
        $db->prepare(
            'INSERT INTO purchases (purchase_number, total_amount, notes) VALUES (:no, :total, :notes)'
        )->execute([
            ':no'    => $purchaseNo,
            ':total' => $totalAmount,
            ':notes' => 'Update stok manual: ' . inputString($data, 'nama'),
        ]);
        $purchaseId = (int)$db->lastInsertId();
        
        $db->prepare(
            'INSERT INTO purchase_details (purchase_id, product_id, qty, buy_price, subtotal, remaining_qty) VALUES (:pid, :prod_id, :qty, :buy_price, :subtotal, :remaining_qty)'
        )->execute([
            ':pid'           => $purchaseId,
            ':prod_id'       => $id,
            ':qty'           => $stockDelta,
            ':buy_price'     => $effectiveBuyPrice,
            ':subtotal'      => $totalAmount,
            ':remaining_qty' => $stockDelta,
        ]);
    }

    jsonResponse(['success' => true]);
}

function handleDelete(PDO $db): never
{
    $id = inputInt($_GET, 'id');
    if ($id <= 0) {
        jsonResponse(data: ['error' => 'ID tidak valid'], statusCode: 400);
    }

    $stmtImg = $db->prepare('SELECT imageUrl FROM products WHERE id = :id');
    $stmtImg->execute([':id' => $id]);
    $imageName = $stmtImg->fetchColumn();

    if ($imageName && $imageName !== 'default.png' && $imageName !== 'default.jpg') {
        $filepath = __DIR__ . '/../uploads/' . $imageName;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    $stmt = $db->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    jsonResponse(['success' => true]);
}

// ================================================================
// RESTOCK — POST api/products.php?action=restock
// Body JSON: { id, jumlah, harga_beli, catatan }
// ================================================================
function handleRestock(PDO $db): never
{
    $data = json_decode(file_get_contents('php://input'), associative: true) ?? [];

    $id        = inputInt($data,   'id');
    $jumlah    = inputInt($data,   'jumlah');
    $hargaBeli = inputFloat($data, 'harga_beli');
    $catatan   = inputString($data,'catatan', 'Restock stok');

    if ($id <= 0) {
        jsonResponse(data: ['error' => 'ID produk tidak valid'], statusCode: 400);
    }
    if ($jumlah < 1) {
        jsonResponse(data: ['error' => 'Jumlah restock minimal 1'], statusCode: 400);
    }
    if ($hargaBeli <= 0) {
        jsonResponse(data: ['error' => 'Harga beli harus lebih dari 0'], statusCode: 400);
    }

    // Cek produk ada
    $stmtChk = $db->prepare('SELECT id, name, buy_price FROM products WHERE id = :id');
    $stmtChk->execute([':id' => $id]);
    $product = $stmtChk->fetch(PDO::FETCH_ASSOC);
    if (!$product) {
        jsonResponse(data: ['error' => 'Produk tidak ditemukan'], statusCode: 404);
    }

    $totalAmount = $jumlah * $hargaBeli;

    $db->beginTransaction();
    try {
        // 1. Tambah stok & update harga beli produk
        $db->prepare(<<<SQL
            UPDATE products
            SET stock     = stock + :jumlah,
                buy_price = :harga_beli
            WHERE id = :id
        SQL)->execute([
            ':jumlah'     => $jumlah,
            ':harga_beli' => $hargaBeli,
            ':id'         => $id,
        ]);

        // 2. Catat ke stock_logs (type='in') — dipakai dashboard pengeluaran
        $db->prepare(
            'INSERT INTO stock_logs (product_id, type, qty, buy_price, notes) VALUES (:pid, \'in\', :qty, :bp, :notes)'
        )->execute([
            ':pid'   => $id,
            ':qty'   => $jumlah,
            ':bp'    => $hargaBeli,
            ':notes' => $catatan,
        ]);

        // 3. Catat ke purchases (ringkasan transaksi pengadaan)
        $purchaseNo = 'PRC' . strtoupper(substr(uniqid(), -8));
        $db->prepare(
            'INSERT INTO purchases (purchase_number, total_amount, notes) VALUES (:no, :total, :notes)'
        )->execute([
            ':no'    => $purchaseNo,
            ':total' => $totalAmount,
            ':notes' => "Restock: {$product['name']} × {$jumlah} unit @ Rp" . number_format($hargaBeli, 0, ',', '.') . ($catatan !== 'Restock stok' ? " | {$catatan}" : ''),
        ]);
        $purchaseId = (int)$db->lastInsertId();

        // 4. Catat ke purchase_details
        $db->prepare(
            'INSERT INTO purchase_details (purchase_id, product_id, qty, buy_price, subtotal, remaining_qty) VALUES (:pid, :prod_id, :qty, :buy_price, :subtotal, :remaining_qty)'
        )->execute([
            ':pid'           => $purchaseId,
            ':prod_id'       => $id,
            ':qty'           => $jumlah,
            ':buy_price'     => $hargaBeli,
            ':subtotal'      => $totalAmount,
            ':remaining_qty' => $jumlah,
        ]);

        $db->commit();

        jsonResponse([
            'success'        => true,
            'purchase_number'=> $purchaseNo,
            'product_name'   => $product['name'],
            'jumlah'         => $jumlah,
            'harga_beli'     => $hargaBeli,
            'total_amount'   => $totalAmount,
        ]);

    } catch (Throwable $e) {
        $db->rollBack();
        jsonResponse(data: ['error' => 'Restock gagal: ' . $e->getMessage()], statusCode: 500);
    }
}
