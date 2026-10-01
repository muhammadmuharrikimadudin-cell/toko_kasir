<?php
declare(strict_types=1);

// api/transactions.php — POST: simpan transaksi POS | GET: list transaksi

// Pastikan output selalu JSON bersih, tidak ada HTML error bocor
ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

// ---- Helper: selalu keluarkan JSON bersih ----
function safeJson(array $data, int $code = 200): never
{
    ob_clean();
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ---- Auth ----
if (empty($_SESSION['user_id'])) {
    safeJson(['error' => 'Unauthorized'], 401);
}

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// -----------------------------------------------
// GET — List transaksi dengan pagination & search
// -----------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $limit  = max(1, (int)($_GET['limit'] ?? 20));
    $page   = max(1, (int)($_GET['page']  ?? 1));
    $offset = ($page - 1) * $limit;

    $where = $search !== '' ? 'WHERE t.invoice_number LIKE :search' : '';

    $stmt = $db->prepare(<<<SQL
        SELECT
            t.id,
            t.invoice_number                 AS kode_transaksi,
            t.grand_total                    AS total_bayar,
            t.grand_total                    AS total,
            t.payment_method                 AS metode_bayar,
            'Complete'                       AS status,
            t.created_at,
            (SELECT GROUP_CONCAT(CONCAT(sd.product_name, ' x', sd.qty) SEPARATOR ', ')
               FROM sale_details sd
              WHERE sd.sale_id = t.id)       AS product_nama,
            NULL                             AS product_foto
        FROM sales t
        {$where}
        ORDER BY t.created_at DESC
        LIMIT :limit OFFSET :offset
    SQL);

    if ($search !== '') {
        $stmt->bindValue(':search', "%{$search}%");
    }
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cntStmt = $db->prepare(
        $search !== ''
            ? 'SELECT COUNT(*) FROM sales WHERE invoice_number LIKE :search'
            : 'SELECT COUNT(*) FROM sales'
    );
    if ($search !== '') {
        $cntStmt->bindValue(':search', "%{$search}%");
    }
    $cntStmt->execute();
    $total = (int)$cntStmt->fetchColumn();

    safeJson([
        'data'  => $rows,
        'total' => $total,
        'pages' => (int)ceil($total / max(1, $limit)),
    ]);
}

// -----------------------------------------------
// POST — Simpan transaksi baru (atomik)
// -----------------------------------------------
if ($method !== 'POST') {
    safeJson(['error' => 'Method not allowed'], 405);
}

// ---- Hanya admin yang boleh menyimpan transaksi ----
if (($_SESSION['role'] ?? '') !== 'admin') {
    safeJson(['error' => 'Hanya admin yang dapat menyimpan transaksi.'], 403);
}

$input       = json_decode(file_get_contents('php://input'), true) ?? [];
$items       = $input['items'] ?? [];
$diskon      = (float)($input['diskon'] ?? 0);
$metodeBayar = trim($input['metode_bayar'] ?? 'Cash');
$cashTendered = (float)($input['cash_tendered'] ?? 0);
$userId      = (int)$_SESSION['user_id'];

if (empty($items) || !is_array($items)) {
    safeJson(['error' => 'Keranjang belanja kosong'], 422);
}

// Map frontend value → DB enum (lowercase)
$methodMap = ['Cash' => 'cash', 'QRIS' => 'qris', 'Transfer' => 'transfer'];
$pmEnum    = $methodMap[$metodeBayar] ?? 'cash';

try {
    $db->beginTransaction();

    // 1. Hitung subtotal
    $subtotal = 0.0;
    foreach ($items as $item) {
        $price     = (float)($item['harga_jual'] ?? $item['sell_price'] ?? 0);
        $qty       = max(1, (int)($item['qty'] ?? 1));
        $subtotal += $price * $qty;
    }
    $discountAmount = $subtotal * $diskon / 100;
    $grandTotal     = $subtotal - $discountAmount;
    
    $payAmount = $grandTotal;
    $changeAmount = 0;
    if ($pmEnum === 'cash' && $cashTendered >= $grandTotal) {
        $payAmount = $cashTendered;
        $changeAmount = $cashTendered - $grandTotal;
    }

    $invoiceNumber  = 'TRX-' . date('YmdHis') . '-' . rand(100, 999);

    // 2. Insert header ke `sales`
    // Kolom: invoice_number, user_id, subtotal, discount_amount,
    //        grand_total, pay_amount, change_amount, payment_method
    $stmtSale = $db->prepare(<<<SQL
        INSERT INTO sales
            (invoice_number, user_id, subtotal, discount_amount,
             grand_total, pay_amount, change_amount, payment_method)
        VALUES
            (:invoice_number, :user_id, :subtotal, :discount_amount,
             :grand_total, :pay_amount, :change_amount, :payment_method)
    SQL);
    $stmtSale->execute([
        ':invoice_number'  => $invoiceNumber,
        ':user_id'         => $userId,
        ':subtotal'        => $subtotal,
        ':discount_amount' => $discountAmount,
        ':grand_total'     => $grandTotal,
        ':pay_amount'      => $payAmount,
        ':change_amount'   => $changeAmount,
        ':payment_method'  => $pmEnum,
    ]);
    $saleId = (int)$db->lastInsertId();

    // 3. Insert detail ke `sale_details`
    // Kolom nyata: sale_id, product_id, product_name, buy_price, sell_price, qty, discount_amount, subtotal
    $stmtDetail = $db->prepare(<<<SQL
        INSERT INTO sale_details
            (sale_id, product_id, product_name, buy_price, sell_price, qty, discount_amount, subtotal)
        VALUES
            (:sale_id, :product_id, :product_name, :buy_price, :sell_price, :qty, :discount_amount, :subtotal)
    SQL);

    // Cek stok menggunakan SELECT (pengurangan aktual & logging ditangani trigger trg_after_insert_sale_details)
    $stmtCheckStock = $db->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');


    foreach ($items as $item) {
        $productId  = (int)($item['id'] ?? 0);
        $prodName   = (string)($item['nama'] ?? $item['name'] ?? '');
        $sellPrice  = (float)($item['harga_jual'] ?? $item['sell_price'] ?? 0);
        $buyPrice   = (float)($item['harga_beli'] ?? $item['buy_price'] ?? 0);
        $qty        = max(1, (int)($item['qty'] ?? 1));
        $itemSub    = $sellPrice * $qty;

        if ($productId <= 0) {
            throw new RuntimeException("ID produk tidak valid: " . json_encode($item));
        }

        // 1. Cek ketersediaan stok
        $stmtCheckStock->execute([':id' => $productId]);
        $currentStock = (int)$stmtCheckStock->fetchColumn();
        if ($currentStock < $qty) {
            throw new RuntimeException("Stok tidak cukup untuk produk: {$prodName}");
        }

        // 2. Insert detail (Trigger DB akan otomatis mengurangi stok & mencatat ke stock_logs)
        $stmtDetail->execute([
            ':sale_id'         => $saleId,
            ':product_id'      => $productId,
            ':product_name'    => $prodName,
            ':buy_price'       => $buyPrice,
            ':sell_price'      => $sellPrice,
            ':qty'             => $qty,
            ':discount_amount' => 0,
            ':subtotal'        => $itemSub,
        ]);
    }

    $db->commit();

    // Ambil nama kasir dari DB
    $userStmt = $db->prepare('SELECT name FROM users WHERE id = :id');
    $userStmt->execute([':id' => $userId]);
    $kasirName = (string)($userStmt->fetchColumn() ?: 'Kasir');

    safeJson([
        'success'        => true,
        'kode_transaksi' => $invoiceNumber,
        'total'          => $subtotal,
        'diskon'         => $diskon,
        'total_bayar'    => $grandTotal,
        'metode_bayar'   => $metodeBayar,
        'pay_amount'     => $payAmount,
        'change_amount'  => $changeAmount,
        'created_at'     => date('d-m-Y, H:i:s'),
        'kasir'          => $kasirName,
    ], 201);

} catch (RuntimeException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    safeJson(['error' => $e->getMessage()], 400);

} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    safeJson(['error' => 'Transaksi gagal: ' . $e->getMessage()], 500);
}