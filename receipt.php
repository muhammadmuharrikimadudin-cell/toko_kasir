<?php
declare(strict_types=1);
require_once 'config/database.php';

$id = trim($_GET['id'] ?? '');
if ($id === '') { http_response_code(400); die('ID Transaksi tidak ditemukan.'); }

$db = getDB();

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

if (!function_exists('fmt')) {
    function fmt(mixed $n): string {
        return number_format((float)($n ?? 0), 0, ',', '.');
    }
}

$subtotal       = (float)($trx['subtotal']       ?? 0);
$grandTotal     = (float)($trx['grand_total']     ?? 0);
$discountAmount = (float)($trx['discount_amount'] ?? 0); // Diskon tersimpan sebagai nominal Rupiah

// Hitung persentase diskon
$discountPct    = ($subtotal > 0) ? ($discountAmount / $subtotal) * 100 : 0;

$payAmount      = (float)($trx['pay_amount']      ?? 0);
$changeAmount   = (float)($trx['change_amount']   ?? 0);
$totalQty       = array_sum(array_column($items, 'qty'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk <?= htmlspecialchars($trx['invoice_number']) ?></title>
<style>
  @page {
    size: 58mm auto;
    margin: 0;
  }
  * { 
    box-sizing: border-box; 
    margin: 0; 
    padding: 0; 
    word-wrap: break-word; 
  }
  body {
    font-family: 'Courier New', Courier, monospace;
    font-size: 10px;
    line-height: 1.2;
    color: #000;
    background: #fff;
    width: 58mm;
  }

  /* Area Cetak Fisik AUQOZ 58D */
  .receipt {
    width: 46mm !important;
    margin: 0 auto;
    padding: 2px 0 10px 0;
  }

  .header { text-align: center; margin-bottom: 2px; }
  .header h1 { font-size: 13px; font-weight: bold; }
  .header p  { font-size: 9px; }

  .sep { border-top: 1px dashed #000; margin: 3px 0; }
  .sep-solid { border-top: 1px solid #000; margin: 3px 0; }

  /* Tabel Informasi */
  .info-table { width: 100%; font-size: 9px; border-collapse: collapse; }
  .info-table td { vertical-align: top; padding: 1px 0; }
  .info-table td.label { width: 38px; }
  .info-table td.colon { width: 8px; text-align: center; }

  /* Item Produk */
  .item-table { width: 100%; font-size: 9px; border-collapse: collapse; margin-bottom: 3px; }
  .item-table td { vertical-align: top; }
  .item-name { font-weight: bold; font-size: 10px; padding-bottom: 1px; }

  /* Ringkasan & Pembayaran */
  .summary-table { width: 100%; font-size: 9px; border-collapse: collapse; }
  .summary-table td { padding: 1px 0; }
  .summary-table td.right { text-align: right; white-space: nowrap; }
  .summary-table .bold td { font-weight: bold; font-size: 10px; }
  .summary-table .total-row td { font-size: 11px; font-weight: bold; }

  .footer { text-align: center; font-size: 9px; margin-top: 4px; }

  @media print {
    @page {
      size: 58mm auto;
      margin: 0;
    }
    html, body { 
      width: 58mm !important; 
      height: auto !important;
      overflow: visible !important;
      margin: 0;
      padding: 0;
    }
    .receipt { 
      width: 46mm !important; 
      margin: 0 auto !important; 
    }
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
    <tr>
      <td class="label">No</td>
      <td class="colon">:</td>
      <td style="font-size: 8.5px; font-weight: bold;"><?= htmlspecialchars($trx['invoice_number']) ?></td>
    </tr>
    <tr>
      <td class="label">Waktu</td>
      <td class="colon">:</td>
      <td><?= date('d/m/Y H:i', strtotime($trx['created_at'])) ?></td>
    </tr>
    <tr>
      <td class="label">Kasir</td>
      <td class="colon">:</td>
      <td><?= htmlspecialchars($trx['kasir_name'] ?? '-') ?></td>
    </tr>
    <tr>
      <td class="label">Metode</td>
      <td class="colon">:</td>
      <td><?= htmlspecialchars($trx['payment_method']) ?></td>
    </tr>
  </table>

  <div class="sep"></div>

  <!-- RINCIAN BARANG -->
  <?php foreach ($items as $item):
    $itemSubtotal = (float)$item['qty'] * (float)$item['sell_price'];
  ?>
  <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
  <table class="item-table">
    <tr>
      <td style="width: 55%;"><?= (int)$item['qty'] ?> x Rp <?= fmt($item['sell_price']) ?></td>
      <td style="width: 45%; text-align: right;">Rp <?= fmt($itemSubtotal) ?></td>
    </tr>
  </table>
  <?php endforeach; ?>

  <div class="sep"></div>

  <!-- RINGKASAN PEMBAYARAN -->
  <table class="summary-table">
    <tr>
      <td>Total Item</td>
      <td class="right"><?= (int)$totalQty ?> pcs</td>
    </tr>
    <tr>
      <td>Subtotal</td>
      <td class="right">Rp <?= fmt($subtotal) ?></td>
    </tr>
    <?php if ($discountAmount > 0): ?>
    <tr>
      <td>Diskon (<?= rtrim(rtrim(number_format($discountPct, 1, ',', '.'), '0'), ',') ?>%)</td>
      <td class="right">- Rp <?= fmt($discountAmount) ?></td>
    </tr>
    <?php endif; ?>
  </table>

  <div class="sep-solid"></div>

  <table class="summary-table">
    <tr class="total-row">
      <td>TOTAL</td>
      <td class="right">Rp <?= fmt($grandTotal) ?></td>
    </tr>
  </table>

  <!-- BAYAR & KEMBALIAN -->
  <?php if (strtolower($trx['payment_method']) === 'cash'): ?>
  <div class="sep"></div>
  <table class="summary-table">
    <tr>
      <td>Bayar (Cash)</td>
      <td class="right">Rp <?= fmt($payAmount) ?></td>
    </tr>
    <tr class="bold">
      <td>Kembalian</td>
      <td class="right">Rp <?= fmt($changeAmount) ?></td>
    </tr>
  </table>
  <?php endif; ?>

  <div class="sep"></div>

  <!-- FOOTER -->
<div class="footer">
  <p>*** Terima Kasih ***</p>
  <p>Selamat Berbelanja Kembali</p>
</div>

<!-- DORONG KERTAS DENGAN SPASI INVISIBLE -->
<div style="font-size: 10px; line-height: 15px;">
  &nbsp;<br>
  &nbsp;<br>
  &nbsp;<br>
  &nbsp;<br>
</div>

</div><!-- /receipt -->

  </div>

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