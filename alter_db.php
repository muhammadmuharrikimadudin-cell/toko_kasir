<?php
require_once 'config/database.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE products ADD COLUMN imageUrl VARCHAR(500) NULL");
    echo "Success: Column imageUrl added to products table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Info: Column imageUrl already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
