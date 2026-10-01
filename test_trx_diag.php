<?php
require 'config/database.php';
$db = getDB();

// Simulasi exact payload dari kasir.php
$items = [
    ['id' => 1, 'nama' => 'Indomie Goreng', 'harga_jual' => 3500, 'harga_beli' => 2500, 'stok' => 150, 'qty' => 1],
    ['id' => 5, 'nama' => 'Facial Wash Garnier', 'harga_jual' => 36500, 'harga_beli' => 30000, 'stok' => 25, 'qty' => 1],
];
$diskon = 0;
$metodeBayar = 'Cash';
$userId = 1;

$methodMap = ['Cash' => 'cash', 'QRIS' => 'qris', 'Transfer' => 'transfer'];
$pmEnum    = $methodMap[$metodeBayar] ?? 'cash';

$subtotal = 0.0;
foreach ($items as $item) {
    $subtotal += (float)($item['harga_jual'] ?? 0) * (int)($item['qty'] ?? 1);
}
$discountAmount = $subtotal * $diskon / 100;
$grandTotal     = $subtotal - $discountAmount;
$invoiceNumber  = 'TRX-TEST-' . rand(100, 999);

echo "subtotal=$subtotal grandTotal=$grandTotal invoice=$invoiceNumber pmEnum=$pmEnum\n\n";

try {
    $db->beginTransaction();

    // Test INSERT sales
    $stmtSale = $db->prepare(<<<SQL
        INSERT INTO sales
            (invoice_number, user_id, subtotal, discount_amount,
             grand_total, pay_amount, change_amount, payment_method)
        VALUES
            (:invoice_number, :user_id, :subtotal, :discount_amount,
             :grand_total, :pay_amount, :change_amount, :payment_method)
    SQL);
    $r = $stmtSale->execute([
        ':invoice_number'  => $invoiceNumber,
        ':user_id'         => $userId,
        ':subtotal'        => $subtotal,
        ':discount_amount' => $discountAmount,
        ':grand_total'     => $grandTotal,
        ':pay_amount'      => $grandTotal,
        ':change_amount'   => 0,
        ':payment_method'  => $pmEnum,
    ]);
    echo "INSERT sales: " . ($r ? "OK" : "FAIL") . "\n";
    $saleId = (int)$db->lastInsertId();
    echo "saleId=$saleId\n\n";

    // Test INSERT sale_details
    $stmtDetail = $db->prepare(<<<SQL
        INSERT INTO sale_details
            (sale_id, product_id, product_name, buy_price, sell_price, qty, discount_amount, subtotal)
        VALUES
            (:sale_id, :product_id, :product_name, :buy_price, :sell_price, :qty, :discount_amount, :subtotal)
    SQL);

    // Test UPDATE stock
    $stmtStock = $db->prepare(
        'UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty'
    );

    foreach ($items as $item) {
        $productId = (int)$item['id'];
        $prodName  = $item['nama'];
        $sellPrice = (float)$item['harga_jual'];
        $buyPrice  = (float)$item['harga_beli'];
        $qty       = (int)$item['qty'];
        $itemSub   = $sellPrice * $qty;

        $r2 = $stmtDetail->execute([
            ':sale_id'         => $saleId,
            ':product_id'      => $productId,
            ':product_name'    => $prodName,
            ':buy_price'       => $buyPrice,
            ':sell_price'      => $sellPrice,
            ':qty'             => $qty,
            ':discount_amount' => 0,
            ':subtotal'        => $itemSub,
        ]);
        echo "INSERT sale_details[$productId]: " . ($r2 ? "OK" : "FAIL") . "\n";

        $r3 = $stmtStock->execute([':qty' => $qty, ':id' => $productId]);
        $rows = $stmtStock->rowCount();
        echo "UPDATE stock[$productId]: rows=$rows\n";
        if ($rows === 0) {
            // Check current stock
            $cur = $db->query("SELECT stock FROM products WHERE id=$productId")->fetchColumn();
            echo "  -> current stock=$cur, wanted qty=$qty\n";
        }
    }

    $db->rollBack(); // rollback test
    echo "\nTest done (rollback). No data was saved.\n";

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "In: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
