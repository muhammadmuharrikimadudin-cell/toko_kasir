<?php
require_once 'config/database.php';
$db = getDB();

$productId = 8;
$qty = 1;

try {
    $db->beginTransaction();
    
    $stmtCheck = $db->prepare('SELECT stock FROM products WHERE id = :id FOR UPDATE');
    $stmtCheck->execute([':id' => $productId]);
    $currentStock = (int)$stmtCheck->fetchColumn();
    
    echo "Current stock: $currentStock\n";
    if ($currentStock < $qty) {
        throw new Exception("Stock not sufficient");
    }
    
    // insert into sale_details
    // Note: this test would actually insert a real detail, which I shouldn't do unless I rollback.
    $db->prepare('INSERT INTO sale_details (sale_id, product_id, product_name, buy_price, sell_price, qty, discount_amount, subtotal) VALUES (1, :pid, "Test", 0, 0, :qty, 0, 0)')->execute([':pid' => $productId, ':qty' => $qty]);
    
    $db->rollBack(); // Rollback so it doesn't affect DB
    echo "Test passed!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
