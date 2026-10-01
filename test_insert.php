<?php
require 'config/database.php';
$db = getDB();

$s = $db->prepare('INSERT INTO sales (invoice_number, user_id, subtotal, discount_amount, grand_total, pay_amount, change_amount, payment_method) VALUES (:inv, :uid, :sub, :disc, :grand, :pay, 0, :method)');
$ok = $s->execute([
    ':inv'    => 'TESTFIX' . time(),
    ':uid'    => 1,
    ':sub'    => 17000,
    ':disc'   => 0,
    ':grand'  => 17000,
    ':pay'    => 17000,
    ':method' => 'cash',
]);
echo $ok ? "INSERT sales: OK\n" : "INSERT sales: FAIL\n";
$saleId = (int)$db->lastInsertId();
echo "SaleId: $saleId\n";

$s2 = $db->prepare('INSERT INTO sale_details (sale_id, product_id, product_name, buy_price, sell_price, qty, discount_amount, subtotal) VALUES (:sale_id, :prod_id, :prod_name, :buy_price, :sell_price, :qty, 0, :subtotal)');
$ok2 = $s2->execute([
    ':sale_id'    => $saleId,
    ':prod_id'    => 1,
    ':prod_name'  => 'Indomie Goreng',
    ':buy_price'  => 2500,
    ':sell_price' => 3500,
    ':qty'        => 2,
    ':subtotal'   => 7000,
]);
echo $ok2 ? "INSERT sale_details: OK\n" : "INSERT sale_details: FAIL\n";

// Cleanup
$db->exec("DELETE FROM sale_details WHERE sale_id = $saleId");
$db->exec("DELETE FROM sales WHERE id = $saleId");
echo "Cleanup done.\n";
