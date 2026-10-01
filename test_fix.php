<?php
require 'config/database.php';
$db = getDB();

// Test the exact queries from transactions.php

// Test stmtStock
$stmtStock = $db->prepare(
    'UPDATE products SET stock = stock - :qty_set WHERE id = :id AND stock >= :qty_check'
);
$r = $stmtStock->execute([':qty_set' => 1, ':qty_check' => 1, ':id' => 1]);
$rows = $stmtStock->rowCount();
echo "stmtStock rows=$rows\n";

// Restore
$db->exec("UPDATE products SET stock = stock + 1 WHERE id = 1");

// Test stmtLog
$stmtLog = $db->prepare(
    "INSERT INTO stock_logs (product_id, type, qty, notes) VALUES (:prod_id, 'out', :qty, :notes)"
);
$r2 = $stmtLog->execute([':prod_id' => 1, ':qty' => 1, ':notes' => 'Test log']);
echo "stmtLog: " . ($r2 ? "OK" : "FAIL") . "\n";

// Cleanup log
$db->exec("DELETE FROM stock_logs WHERE notes='Test log'");

echo "All OK!\n";
