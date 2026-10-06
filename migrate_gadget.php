<?php
require 'config/database.php';
$db = getDB();

try {
    // 1. Add columns for IMEI / Serial Number
    try { $db->exec("ALTER TABLE products ADD COLUMN imei_sn VARCHAR(255) NULL"); } catch (Exception $e) {}
    try { $db->exec("ALTER TABLE sale_details ADD COLUMN imei_sn VARCHAR(255) NULL"); } catch (Exception $e) {}
    
    // 2. Clear old toserba data (products, categories, sales, etc.)
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    try { $db->exec("TRUNCATE TABLE sale_details"); } catch (Exception $e) {}
    try { $db->exec("TRUNCATE TABLE sales"); } catch (Exception $e) {}
    try { $db->exec("TRUNCATE TABLE stock_logs"); } catch (Exception $e) {}
    try { $db->exec("TRUNCATE TABLE purchase_details"); } catch (Exception $e) {}
    try { $db->exec("TRUNCATE TABLE purchases"); } catch (Exception $e) {}
    try { $db->exec("TRUNCATE TABLE products"); } catch (Exception $e) {}
    try { $db->exec("TRUNCATE TABLE categories"); } catch (Exception $e) {}
    $db->exec("SET FOREIGN_KEY_CHECKS=1");

    // 3. Insert New Categories
    $newCats = [
        'Smartphone', 
        'Aksesori & Audio', 
        'Charger & Powerbank', 
        'Case & Screen Protector', 
        'Kartu & Paket Data', 
        'Sparepart'
    ];
    $stmtCat = $db->prepare("INSERT INTO categories (name) VALUES (?)");
    $catIds = [];
    foreach ($newCats as $c) {
        $stmtCat->execute([$c]);
        $catIds[$c] = $db->lastInsertId();
    }

    // 4. Insert Sample Gadget Product
    $stmtProd = $db->prepare("INSERT INTO products (category_id, barcode, name, buy_price, sell_price, stock, min_stock, imei_sn) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // iPhone 15 Pro
    $stmtProd->execute([
        $catIds['Smartphone'], 
        'IP15P-128', 
        'iPhone 15 Pro 128GB', 
        16000000, 
        18500000, 
        10, 
        2, 
        '123456789012345'
    ]);
    
    // AirPods Pro
    $stmtProd->execute([
        $catIds['Aksesori & Audio'], 
        'AIRPODS-P2', 
        'AirPods Pro (2nd Gen)', 
        3500000, 
        4200000, 
        25, 
        5, 
        ''
    ]);

    // Anker Powerbank
    $stmtProd->execute([
        $catIds['Charger & Powerbank'], 
        'ANKER-PB-10K', 
        'Anker PowerCore 10000mAh', 
        400000, 
        550000, 
        50, 
        10, 
        ''
    ]);

    echo "Migration and seeding successful!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
