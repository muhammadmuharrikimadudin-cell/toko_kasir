<?php
declare(strict_types=1);

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(data: ['error' => 'Unauthorized'], statusCode: 401);
}

$db = getDB();

$page   = (int)($_GET['page'] ?? 1);
$limit  = (int)($_GET['limit'] ?? 10);
$search = trim($_GET['search'] ?? '');

$filter = trim($_GET['filter'] ?? '');

$offset = ($page - 1) * $limit;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(pr.purchase_number LIKE :search OR pr.notes LIKE :search)";
    $params[':search'] = "%{$search}%";
}

if ($filter === 'today') {
    $where[] = "DATE(pr.created_at) = CURDATE()";
} elseif ($filter === 'month') {
    $where[] = "YEAR(pr.created_at) = YEAR(CURDATE()) AND MONTH(pr.created_at) = MONTH(CURDATE())";
} elseif ($filter === 'year') {
    $where[] = "YEAR(pr.created_at) = YEAR(CURDATE())";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM purchases pr {$whereSql}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = <<<SQL
    SELECT
        pr.id,
        pr.purchase_number,
        pr.total_amount,
        pr.notes,
        pr.created_at
    FROM purchases pr
    {$whereSql}
    ORDER BY pr.created_at DESC
    LIMIT :limit OFFSET :offset
SQL;

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

jsonResponse([
    'data'  => $data,
    'total' => $total,
    'page'  => $page,
    'pages' => ceil($total / $limit) ?: 1,
]);
