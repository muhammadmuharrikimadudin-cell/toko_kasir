<?php
require_once 'config/database.php';
$db = getDB();

$productId = 8;
$qty = 1;

try {
    $db->beginTransaction();
    
    $stmtStock = $db->prepare('UPDATE products SET stock = stock - :qty_set WHERE id = :id AND stock >= :qty_check');
    $stmtStock->execute([':qty_set' => $qty, ':qty_check' => $qty, ':id' => $productId]);
    
    $rowCount = $stmtStock->rowCount();
    
    echo "Row count: " . $rowCount . "\n";
    
    $db->rollBack();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
