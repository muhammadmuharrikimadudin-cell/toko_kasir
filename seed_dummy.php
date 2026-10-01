<?php
require 'config/database.php';
$db = getDB();

try {
    $salesCount = $db->query("SELECT COUNT(*) FROM sales")->fetchColumn();
    if ($salesCount > 0) {
        echo "Sales already exist ({$salesCount} records). Skipping.\n";
        exit;
    }

    $prods = $db->query("SELECT id, name, buy_price, sell_price, stock FROM products ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $pm = [];
    foreach ($prods as $p) { $pm[$p['id']] = $p; }

    function insertSale(PDO $db, array $pm, string $inv, string $method, string $ts, array $items): void
    {
        $subtotal = 0;
        foreach ($items as $it) {
            if (!isset($pm[$it[0]])) continue;
            $subtotal += $pm[$it[0]]['sell_price'] * $it[1];
        }
        $disc  = isset($it[2]) ? (float)$it[2] : 0;
        $grand = $subtotal - ($subtotal * $disc / 100);

        $stmt = $db->prepare("INSERT INTO sales (invoice_number, user_id, subtotal, discount_amount, grand_total, pay_amount, change_amount, payment_method, created_at)
            VALUES (?,1,?,?,?,?,0,?,?)");
        $stmt->execute([$inv, $subtotal, $subtotal - $grand, $grand, $grand, $method, $ts]);
        $saleId = (int)$db->lastInsertId();

        $stmtD = $db->prepare("INSERT INTO sale_details (sale_id, product_id, product_name, buy_price, sell_price, qty, discount_amount, subtotal) VALUES (?,?,?,?,?,?,0,?)");
        $stmtL = $db->prepare("INSERT INTO stock_logs (product_id, type, qty, notes, created_at) VALUES (?,'out',?,?,?)");
        $stmtS = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

        foreach ($items as $it) {
            [$pid, $qty] = $it;
            if (!isset($pm[$pid]) || $qty <= 0) continue;
            $p   = $pm[$pid];
            $sub = $p['sell_price'] * $qty;
            $stmtD->execute([$saleId, $pid, $p['name'], $p['buy_price'], $p['sell_price'], $qty, $sub]);
            $stmtL->execute([$pid, $qty, "Penjualan {$inv}", $ts]);
            $stmtS->execute([$qty, $pid, $qty]);
        }
        echo "Inserted $inv\n";
    }

    insertSale($db, $pm, 'INV20260920001', 'qris',     '2026-09-20 09:15:00', [[1,5],[4,3]]);
    insertSale($db, $pm, 'INV20260920002', 'cash',     '2026-09-20 11:30:00', [[1,1],[4,1]]);
    insertSale($db, $pm, 'INV20260921001', 'qris',     '2026-09-21 10:00:00', [[5,1],[6,1]]);
    insertSale($db, $pm, 'INV20260922001', 'transfer', '2026-09-22 14:20:00', [[2,1],[11,3]]);
    insertSale($db, $pm, 'INV20260923001', 'qris',     '2026-09-23 08:45:00', [[4,5],[1,2]]);
    insertSale($db, $pm, 'INV20260924001', 'cash',     '2026-09-24 16:00:00', [[6,2],[11,4]]);
    insertSale($db, $pm, 'INV20260925001', 'qris',     '2026-09-25 13:30:00', [[3,1],[1,5]]);
    insertSale($db, $pm, 'INV20260926001', 'cash',     '2026-09-26 09:00:00', [[2,1],[6,2]]);
    insertSale($db, $pm, 'INV20260926002', 'qris',     '2026-09-26 15:45:00', [[12,1],[3,2]]);
    insertSale($db, $pm, 'INV20260927001', 'cash',     '2026-09-27 09:15:00', [[1,3],[4,5]]);
    insertSale($db, $pm, 'INV20260927002', 'qris',     '2026-09-27 11:00:00', [[11,5],[6,1]]);
    insertSale($db, $pm, 'INV20260927003', 'transfer', '2026-09-27 14:30:00', [[6,5],[12,1]]);

    echo "Done!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
