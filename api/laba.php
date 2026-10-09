<?php
declare(strict_types=1);

// api/laba.php — Riwayat Laba Kotor & Laba Bersih per transaksi

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$db = getDB();

$page   = max(1, (int)($_GET['page']   ?? 1));
$limit  = max(1, (int)($_GET['limit']  ?? 15));
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? ''); // 'today' | 'month' | 'year' | ''

$offset = ($page - 1) * $limit;

// ---- Build WHERE ----
$where  = [];
$params = [];

if ($search !== '') {
    $where[]           = '(s.invoice_number LIKE :search)';
    $params[':search'] = "%{$search}%";
}

if ($filter === 'today') {
    $where[] = 'DATE(s.created_at) = CURDATE()';
} elseif ($filter === 'month') {
    $where[] = 'YEAR(s.created_at) = YEAR(CURDATE()) AND MONTH(s.created_at) = MONTH(CURDATE())';
} elseif ($filter === 'year') {
    $where[] = 'YEAR(s.created_at) = YEAR(CURDATE())';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ---- Count ----
$cntStmt = $db->prepare("
    SELECT COUNT(DISTINCT s.id)
    FROM sales s
    {$whereSql}
");
$cntStmt->execute($params);
$total = (int)$cntStmt->fetchColumn();

// ---- Main query ----
$sql = <<<SQL
    SELECT
        s.id,
        s.invoice_number                                                    AS kode_transaksi,
        s.created_at,
        s.grand_total                                                       AS laba_kotor,
        s.payment_method                                                    AS metode_bayar,
        COALESCE(SUM(
            sd.qty * COALESCE(NULLIF(sd.buy_price, 0), p.buy_price, 0)
        ), 0)                                                               AS hpp,
        (s.grand_total - COALESCE(SUM(
            sd.qty * COALESCE(NULLIF(sd.buy_price, 0), p.buy_price, 0)
        ), 0))                                                              AS laba_bersih,
        GROUP_CONCAT(CONCAT(sd.product_name, ' ×', sd.qty) SEPARATOR ', ') AS produk
    FROM sales s
    LEFT JOIN sale_details sd ON sd.sale_id = s.id
    LEFT JOIN products     p  ON p.id       = sd.product_id
    {$whereSql}
    GROUP BY s.id, s.invoice_number, s.created_at, s.grand_total, s.payment_method
    ORDER BY s.created_at DESC
    LIMIT :limit OFFSET :offset
SQL;

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- Summary totals ----
$sumSql = <<<SQL
    SELECT
        COALESCE(SUM(t.grand_total), 0) AS total_gross,
        COALESCE(SUM(t.hpp), 0)         AS total_hpp
    FROM (
        SELECT 
            s.grand_total,
            COALESCE(SUM(
                sd.qty * COALESCE(NULLIF(sd.buy_price, 0), p.buy_price, 0)
            ), 0) AS hpp
        FROM sales s
        LEFT JOIN sale_details sd ON sd.sale_id = s.id
        LEFT JOIN products     p  ON p.id       = sd.product_id
        {$whereSql}
        GROUP BY s.id, s.grand_total
    ) t
SQL;
$sumStmt = $db->prepare($sumSql);
foreach ($params as $k => $v) {
    $sumStmt->bindValue($k, $v);
}
$sumStmt->execute();
$sum = $sumStmt->fetch(PDO::FETCH_ASSOC);
$totalGross = (float)($sum['total_gross'] ?? 0);
$totalHpp   = (float)($sum['total_hpp']   ?? 0);

jsonResponse([
    'data'        => $data,
    'total'       => $total,
    'pages'       => (int)ceil($total / max(1, $limit)),
    'page'        => $page,
    'summary'     => [
        'total_gross'  => $totalGross,
        'total_hpp'    => $totalHpp,
        'total_net'    => $totalGross - $totalHpp,
    ],
]);
