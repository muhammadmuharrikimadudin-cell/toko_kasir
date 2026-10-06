<?php
declare(strict_types=1);

// api/dashboard.php — Statistik & chart data untuk Dashboard
// - Total Pengeluaran = SUM(total_amount) dari tabel purchases (setiap restock)
// - Grafik Pengeluaran = purchases per bulan/tahun
// - Laba Kotor        = SUM(grand_total) dari sales
// - Laba Bersih       = Laba Kotor − HPP barang terjual (qty × buy_price di sale_details)

header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

if (empty($_SESSION['user_id'])) {
    jsonResponse(data: ['error' => 'Unauthorized'], statusCode: 401);
}

$db = getDB();

// -----------------------------------------------
// Tahun yang dipilih (default = tahun sekarang)
// -----------------------------------------------
$selectedYear = (int)($_GET['year'] ?? date('Y'));
if ($selectedYear < 2000 || $selectedYear > 2100) {
    $selectedYear = (int)date('Y');
}

// ================================================================
// 1. PENJUALAN HARI INI — Laba Kotor & HPP
// ================================================================
$stmtToday = $db->prepare(<<<SQL
    SELECT
        COALESCE(SUM(s.grand_total), 0)                                        AS gross,
        COALESCE(SUM(
            sd.qty * COALESCE(NULLIF(sd.buy_price, 0), p.buy_price, 0)
        ), 0)                                                                    AS hpp
    FROM sales s
    JOIN sale_details sd ON sd.sale_id = s.id
    JOIN products     p  ON p.id       = sd.product_id
    WHERE DATE(s.created_at) = CURDATE()
SQL);
$stmtToday->execute();
$todayRow   = $stmtToday->fetch(PDO::FETCH_ASSOC) ?: ['gross' => 0, 'hpp' => 0];
$todayGross = (float)$todayRow['gross'];
$todayHpp   = (float)$todayRow['hpp'];
$todayNet   = $todayGross - $todayHpp;  // Laba Bersih hari ini

// ================================================================
// 2. PENGELUARAN RESTOCK HARI INI — dari stock_logs type='in'
// ================================================================
$stmtTodaySpend = $db->prepare(<<<SQL
    SELECT COALESCE(SUM(sl.qty * sl.buy_price), 0) AS spending
    FROM stock_logs sl
    WHERE sl.type = 'in'
      AND sl.buy_price > 0
      AND DATE(sl.created_at) = CURDATE()
SQL);
$stmtTodaySpend->execute();
$todaySpending = (float)($stmtTodaySpend->fetchColumn() ?: 0);

// ================================================================
// 3. STOCK ALERT (produk stok <= min_stock)
// ================================================================
$stmtAlert  = $db->query('SELECT COUNT(*) FROM products WHERE stock <= min_stock');
$stockAlert = (int)$stmtAlert->fetchColumn();

$stmtLowStockList = $db->query('SELECT barcode, name, stock, min_stock FROM products WHERE stock <= min_stock ORDER BY stock ASC');
$lowStockList = $stmtLowStockList->fetchAll(PDO::FETCH_ASSOC);

// ================================================================
// 4. TRANSAKSI TERAKHIR (5 terbaru)
// ================================================================
$stmtLatest = $db->prepare(<<<SQL
    SELECT
        s.invoice_number   AS kode_transaksi,
        s.created_at,
        s.grand_total      AS total_bayar,
        'Complete'         AS status,
        s.payment_method   AS metode_bayar
    FROM sales s
    ORDER BY s.created_at DESC
    LIMIT 5
SQL);
$stmtLatest->execute();
$latestTransactions = $stmtLatest->fetchAll(PDO::FETCH_ASSOC);

// ================================================================
// 5. PRODUK POPULER (top 5 qty terjual)
// ================================================================
$stmtPopular = $db->prepare(<<<SQL
    SELECT
        p.barcode        AS kode,
        p.name           AS nama,
        p.stock          AS stok,
        SUM(sd.qty)      AS total_sold
    FROM sale_details sd
    JOIN products p ON sd.product_id = p.id
    GROUP BY p.id, p.barcode, p.name, p.stock
    ORDER BY total_sold DESC
    LIMIT 5
SQL);
$stmtPopular->execute();
$popularProducts = $stmtPopular->fetchAll(PDO::FETCH_ASSOC);

// ================================================================
// 6. STOCK LOG (5 terbaru)
// ================================================================
$stmtLog = $db->prepare(<<<SQL
    SELECT
        sl.created_at,
        p.name     AS nama,
        sl.qty,
        CASE sl.type WHEN 'in' THEN 'Masuk' ELSE 'Keluar' END AS tipe,
        sl.notes   AS keterangan,
        NULL       AS foto
    FROM stock_logs sl
    JOIN products p ON sl.product_id = p.id
    ORDER BY sl.created_at DESC
    LIMIT 5
SQL);
$stmtLog->execute();
$stockLog = $stmtLog->fetchAll(PDO::FETCH_ASSOC);

// ================================================================
// 7. CHART HARIAN — 14 hari terakhir (omzet penjualan)
// ================================================================
$stmtChart = $db->prepare(<<<SQL
    SELECT DATE(created_at) AS tgl,
           COALESCE(SUM(grand_total), 0) AS total
    FROM sales
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY tgl ASC
SQL);
$stmtChart->execute();
$chartRaw = $stmtChart->fetchAll(PDO::FETCH_ASSOC);

$chartData = [];
for ($i = 13; $i >= 0; $i--) {
    $date             = date('Y-m-d', strtotime("-{$i} days"));
    $chartData[$date] = 0.0;
}
foreach ($chartRaw as $row) {
    $chartData[$row['tgl']] = (float)$row['total'];
}

// ================================================================
// 8. CHART TAHUNAN — Pengeluaran Restock per bulan (dari purchases)
// ================================================================
$stmtYearlySpend = $db->prepare(<<<SQL
    SELECT MONTH(pr.created_at)          AS bulan,
           COALESCE(SUM(pr.total_amount), 0) AS total_spend
    FROM purchases pr
    WHERE YEAR(pr.created_at) = :year
    GROUP BY MONTH(pr.created_at)
SQL);
$stmtYearlySpend->execute([':year' => $selectedYear]);
$yearlySpendRaw = $stmtYearlySpend->fetchAll(PDO::FETCH_KEY_PAIR);
$yearlySpendData = array_map(
    fn(int $m): float => (float)($yearlySpendRaw[$m] ?? 0),
    range(1, 12)
);

// ================================================================
// 9. CHART BULANAN — Omzet penjualan per bulan
// ================================================================
$stmtMonthlySales = $db->prepare(<<<SQL
    SELECT MONTH(created_at) AS bulan,
           COALESCE(SUM(grand_total), 0) AS total
    FROM sales
    WHERE YEAR(created_at) = :year
    GROUP BY MONTH(created_at)
SQL);
$stmtMonthlySales->execute([':year' => $selectedYear]);
$monthlySalesRaw  = $stmtMonthlySales->fetchAll(PDO::FETCH_KEY_PAIR);
$monthlySalesData = array_map(
    fn(int $m): float => (float)($monthlySalesRaw[$m] ?? 0),
    range(1, 12)
);

// ================================================================
// 9b. CHART TAHUNAN — Omzet penjualan 5 tahun terakhir
// ================================================================
$stmtYearlySalesList = $db->prepare(<<<SQL
    SELECT YEAR(created_at) AS tahun,
           COALESCE(SUM(grand_total), 0) AS total
    FROM sales
    WHERE YEAR(created_at) >= :start_year AND YEAR(created_at) <= :end_year
    GROUP BY YEAR(created_at)
    ORDER BY tahun ASC
SQL);
$startYear = $selectedYear - 4;
$stmtYearlySalesList->execute([':start_year' => $startYear, ':end_year' => $selectedYear]);
$yearlySalesRawList = $stmtYearlySalesList->fetchAll(PDO::FETCH_KEY_PAIR);
$yearlySalesDataList = [];
$yearlySalesLabels = [];
for ($y = $startYear; $y <= $selectedYear; $y++) {
    $yearlySalesLabels[] = (string)$y;
    $yearlySalesDataList[] = (float)($yearlySalesRawList[$y] ?? 0);
}

// ================================================================
// 10. INVENTORY STATS
// ================================================================
$stmtInv = $db->query(<<<SQL
    SELECT 
        (SELECT COUNT(*) FROM products) AS total_item,
        (SELECT COUNT(*) FROM categories) AS total_category
SQL);
$invStats = $stmtInv->fetch(PDO::FETCH_ASSOC) ?: ['total_item' => 0, 'total_category' => 0];

// ================================================================
// 11. TOTAL PENGELUARAN RESTOCK SEPANJANG MASA (all-time)
//     Sumber: SUM(total_amount) dari tabel purchases
// ================================================================
$stmtTotalSpend = $db->query(<<<SQL
    SELECT COALESCE(SUM(pr.total_amount), 0)
    FROM purchases pr
SQL);
$totalSpending = (float)($stmtTotalSpend->fetchColumn() ?: 0);

// ================================================================
// 12. TOTAL PENGELUARAN RESTOCK TAHUN INI (dari purchases)
// ================================================================
$stmtYearSpend = $db->prepare(<<<SQL
    SELECT COALESCE(SUM(pr.total_amount), 0) AS yearly_spend
    FROM purchases pr
    WHERE YEAR(pr.created_at) = :year
SQL);
$stmtYearSpend->execute([':year' => $selectedYear]);
$yearlySpending = (float)($stmtYearSpend->fetchColumn() ?: 0);

// ================================================================
// 12b. RIWAYAT RESTOCK TERBARU (10 entries dari purchases)
// ================================================================
$stmtRecentPurchases = $db->query(<<<SQL
    SELECT
        pr.purchase_number,
        pr.total_amount,
        pr.notes,
        pr.created_at
    FROM purchases pr
    ORDER BY pr.created_at DESC
    LIMIT 10
SQL);
$recentPurchases = $stmtRecentPurchases->fetchAll(PDO::FETCH_ASSOC);

// ================================================================
// 13. CASHIER STATS — Tahunan (Laba Kotor & Laba Bersih dari penjualan)
// ================================================================
$stmtCashierYear = $db->prepare(<<<SQL
    SELECT
        COALESCE(SUM(CASE WHEN DATE(s.created_at) = CURDATE() THEN s.grand_total END), 0) AS today_gross,
        COALESCE(SUM(s.grand_total), 0)                                                   AS yearly_gross,
        COALESCE(SUM(
            sd.qty * COALESCE(NULLIF(sd.buy_price, 0), p.buy_price, 0)
        ), 0)                                                                              AS yearly_hpp
    FROM sales s
    JOIN sale_details sd ON sd.sale_id = s.id
    JOIN products     p  ON p.id       = sd.product_id
    WHERE YEAR(s.created_at) = :year
SQL);
$stmtCashierYear->execute([':year' => $selectedYear]);
$cashierRow   = $stmtCashierYear->fetch(PDO::FETCH_ASSOC) ?: [];
$yearlyGross  = (float)($cashierRow['yearly_gross'] ?? 0);
$yearlyHpp    = (float)($cashierRow['yearly_hpp']   ?? 0);

// ================================================================
// 13b. CASHIER STATS — Bulanan (Laba Kotor & Laba Bersih dari penjualan)
// ================================================================
$stmtCashierMonth = $db->prepare(<<<SQL
    SELECT
        COALESCE(SUM(s.grand_total), 0)                                                   AS monthly_gross,
        COALESCE(SUM(
            sd.qty * COALESCE(NULLIF(sd.buy_price, 0), p.buy_price, 0)
        ), 0)                                                                              AS monthly_hpp
    FROM sales s
    JOIN sale_details sd ON sd.sale_id = s.id
    JOIN products     p  ON p.id       = sd.product_id
    WHERE YEAR(s.created_at) = :year AND MONTH(s.created_at) = :month
SQL);
$stmtCashierMonth->execute([
    ':year'  => $selectedYear,
    ':month' => (int)date('m')
]);
$cashierMonthRow = $stmtCashierMonth->fetch(PDO::FETCH_ASSOC) ?: [];
$monthlyGross = (float)($cashierMonthRow['monthly_gross'] ?? 0);
$monthlyHpp   = (float)($cashierMonthRow['monthly_hpp']   ?? 0);
$monthlyNet   = $monthlyGross - $monthlyHpp;

$cashierStats = [
    'today_gross'   => (float)($cashierRow['today_gross'] ?? 0),
    'today_net'     => $todayNet,
    'monthly_gross' => $monthlyGross,
    'monthly_net'   => $monthlyNet,
    'yearly_gross'  => $yearlyGross,
    'yearly_hpp'    => $yearlyHpp,
    // Laba Bersih = Laba Kotor - HPP barang terjual
    'yearly_net'    => $yearlyGross - $yearlyHpp,
    // Total Pengeluaran Restock tahun ini
    'yearly_spend'  => $yearlySpending,
];

// ================================================================
// RESPONSE
// ================================================================
jsonResponse([
    'stats' => [
        'today_gross'    => $todayGross,
        'today_net'      => $todayNet,
        // Pengeluaran restock hari ini (dari stock_logs today)
        'today_spending' => $todaySpending,
        'stock_alert'    => $stockAlert,
        'inv'            => $invStats,
        // Total pengeluaran restock all-time (dari purchases)
        'total_received' => $totalSpending,
        // Total pengeluaran restock tahun ini
        'yearly_spending'=> $yearlySpending,
        'cashier'        => $cashierStats,
    ],
    'latest_transactions' => $latestTransactions,
    'popular_products'    => $popularProducts,
    'stock_log'           => $stockLog,
    'low_stock_list'      => $lowStockList,
    // Riwayat restock terbaru (untuk modal detail pengeluaran)
    'recent_purchases'    => $recentPurchases,
    'chart' => [
        'daily_sales' => [
            'labels' => array_keys($chartData),
            'data'   => array_values($chartData),
        ],
        // Pengeluaran Restock per bulan — sumber: purchases (untuk Tab Inventaris)
        'yearly_spend' => [
            'labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
            'data'   => $yearlySpendData,
        ],
        // Omzet penjualan per bulan (untuk Tab Kasir)
        'monthly_sales' => [
            'labels' => ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
            'data'   => $monthlySalesData,
        ],
        // Omzet penjualan 5 tahun terakhir
        'yearly_sales' => [
            'labels' => $yearlySalesLabels,
            'data'   => $yearlySalesDataList,
        ],
    ],
]);