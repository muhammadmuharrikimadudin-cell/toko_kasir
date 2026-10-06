<?php
declare(strict_types=1);
require_once 'config/database.php';

$id = trim($_GET['id'] ?? '');
if ($id === '') { http_response_code(400); die('ID Transaksi tidak ditemukan.'); }

$db = getDB();

// Cari berdasarkan invoice_number (string) ATAU id (integer)
$stmt = $db->prepare('
    SELECT s.*, u.name AS kasir_name
    FROM   sales s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE  s.invoice_number = :inv_id
       OR  s.id             = :num_id
    LIMIT 1
');
$stmt->execute([
    ':inv_id' => $id,
    ':num_id' => is_numeric($id) ? (int)$id : 0,
]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$trx) { http_response_code(404); die('Transaksi tidak ditemukan.'); }

$stmtItems = $db->prepare('SELECT * FROM sale_details WHERE sale_id = :sale_id ORDER BY id ASC');
$stmtItems->execute([':sale_id' => $trx['id']]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// Helper: format angka tanpa simbol mata uang
if (!function_exists('fmt')) {
    function fmt(mixed $n): string {
        return number_format((float)($n ?? 0), 0, ',', '.');
    }
}

// Kalkulasi ringkasan
$subtotal      = (float)($trx['subtotal']        ?? 0);
$grandTotal    = (float)($trx['grand_total']      ?? 0);
$discountPct   = (float)($trx['discount_amount']  ?? 0); // kolom disimpan sebagai persen
$diskonNominal = $subtotal * ($discountPct / 100);
$payAmount     = (float)($trx['pay_amount']       ?? 0);
$changeAmount  = (float)($trx['change_amount']    ?? 0);
$totalQty      = array_sum(array_column($items, 'qty'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= htmlspecialchars($trx['invoice_number']) ?></title>
<style>
  /* ── Halaman & font ── */
  @page {
    size: 58mm auto;
    margin: 0;
  }
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 11px;
    color: #000;
    background: #fff;
    width: 58mm;
  }

  /* ── Wrapper ── */
  .receipt {
    width: 100%;
    padding: 6px 8px 12px;
  }

  /* ── Header ── */
  .header { text-align: center; margin-bottom: 4px; }
  .header h1 { font-size: 15px; font-weight: bold; letter-spacing: 1px; }
  .header p  { font-size: 9px; }

  /* ── Garis pemisah ── */
  .sep-solid  { border-top: 1px solid #000; margin: 4px 0; }
  .sep-dashed { border-top: 1px dashed #000; margin: 4px 0; }

  /* ── Info transaksi ── */
  .info-table { width: 100%; font-size: 10px; }
  .info-table td:first-child { width: 42px; white-space: nowrap; }

  /* ── Baris item ── */
  .item-name { font-weight: bold; font-size: 11px; }
  .item-detail {
    display: flex;
    justify-content: space-between;
    font-size: 10px;
    margin-top: 1px;
  }

  /* ── Ringkasan ── */
  .summary { width: 100%; font-size: 10px; border-collapse: collapse; }
  .summary td { padding: 1px 0; }
  .summary td:last-child { text-align: right; white-space: nowrap; }
  .summary .bold td { font-weight: bold; font-size: 12px; }
  .summary .total-row td { font-size: 13px; font-weight: bold; }

  /* ── Footer ── */
  .footer { text-align: center; font-size: 9px; margin-top: 6px; }

  /* ── Print: hilangkan semua batas browser ── */
  @media print {
    html, body { width: 58mm; }
    .receipt { padding: 0 4px 12px; }
  }
</style>
</head>
<body>

<div class="receipt">

  <!-- HEADER -->
  <div class="header">
    <h1>KASIR TOKO</h1>
    <p>Struk Pembelian</p>
  </div>

  <div class="sep-solid"></div>

  <!-- INFO TRANSAKSI -->
  <table class="info-table">
    <tr><td>No</td><td>: <?= htmlspecialchars($trx['invoice_number']) ?></td></tr>
    <tr><td>Waktu</td><td>: <?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td></tr>
    <tr><td>Kasir</td><td>: <?= htmlspecialchars($trx['kasir_name'] ?? '-') ?></td></tr>
    <tr><td>Metode</td><td>: <?= htmlspecialchars($trx['payment_method']) ?></td></tr>
  </table>

  <div class="sep-dashed"></div>

  <!-- RINCIAN BARANG -->
  <?php foreach ($items as $item):
    $itemSubtotal = (float)$item['qty'] * (float)$item['sell_price'];
  ?>
  <div style="margin-bottom:4px;">
    <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
    <div class="item-detail">
      <span><?= (int)$item['qty'] ?> x Rp <?= fmt($item['sell_price']) ?></span>
      <span>Rp <?= fmt($itemSubtotal) ?></span>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="sep-dashed"></div>

  <!-- RINGKASAN PEMBAYARAN -->
  <table class="summary">
    <tr>
      <td>Total Item</td>
      <td><?= (int)$totalQty ?> pcs</td>
    </tr>
    <tr>
      <td>Subtotal</td>
      <td>Rp <?= fmt($subtotal) ?></td>
    </tr>
    <?php if ($discountPct > 0): ?>
    <tr>
      <td>Diskon (<?= rtrim(rtrim(number_format($discountPct, 2, ',', '.'), '0'), ',') ?>%)</td>
      <td>- Rp <?= fmt($diskonNominal) ?></td>
    </tr>
    <?php endif; ?>
  </table>

  <div class="sep-solid"></div>

  <table class="summary">
    <tr class="total-row">
      <td>TOTAL</td>
      <td>Rp <?= fmt($grandTotal) ?></td>
    </tr>
  </table>

  <?php if ($trx['payment_method'] === 'Cash'): ?>
  <div class="sep-dashed"></div>
  <table class="summary">
    <tr>
      <td>Tunai</td>
      <td>Rp <?= fmt($payAmount) ?></td>
    </tr>
    <tr class="bold">
      <td>Kembali</td>
      <td>Rp <?= fmt($changeAmount) ?></td>
    </tr>
  </table>
  <?php endif; ?>

  <div class="sep-dashed"></div>

  <!-- FOOTER -->
  <div class="footer">
    <p>*** Terima Kasih ***</p>
    <p>Selamat Berbelanja Kembali</p>
  </div>

</div><!-- /receipt -->

<script>
  window.addEventListener('load', function () {
    window.print();
  });
  window.onafterprint = function () {
    window.close();
  };
</script>

</body>
</html>
