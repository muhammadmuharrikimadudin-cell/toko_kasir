<?php
require 'config/database.php';
$db = getDB();
$db->exec('SET FOREIGN_KEY_CHECKS = 0');
$tables = [
    'sales', 
    'sale_details', 
    'purchases', 
    'purchase_details', 
    'stock_logs',
    'transactions',
    'transaction_details',
    'product_lots'
];
foreach ($tables as $table) {
    try {
        $db->exec("TRUNCATE TABLE {$table}");
        echo "Truncated {$table}\n";
    } catch (Exception $e) {
        // Table might not exist, ignore
    }
}
$db->exec('UPDATE products SET stock = 0');
echo "Reset product stock to 0\n";
$db->exec('SET FOREIGN_KEY_CHECKS = 1');
echo "Database cleared successfully!\n";
