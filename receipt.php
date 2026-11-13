<?php
require 'config.php';

// Change the Bible verse shown on the receipt here.
$verse = 'But my God shall supply all your need according to his riches in glory by Christ Jesus.';
$verseRef = 'Philippians 4:19 (KJV)';
if (empty($_SESSION['user'])) { header('Location: login.php'); exit; }

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT s.id, s.sale_date, s.total_amount, u.name AS cashier
                     FROM sales s JOIN users u ON u.id = s.user_id WHERE s.id = ?');
$st->execute([$id]);
$sale = $st->fetch();
if (!$sale) { http_response_code(404); exit('Receipt not found.'); }

$st = $pdo->prepare('SELECT i.name, si.quantity, si.price FROM sale_items si
                     JOIN items i ON i.id = si.item_id WHERE si.sale_id = ?');
$st->execute([$id]);
$lines = $st->fetchAll();
$count = array_sum(array_column($lines, 'quantity'));
$isAdmin = $_SESSION['user']['role'] === 'admin';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Receipt #<?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?> | <?= e(STORE_NAME) ?></title>
  <style>
    body { margin:0; background:#e9edf1; font-family:"Courier New", monospace; color:#111; }
    .actions { display:flex; gap:.6rem; justify-content:center; align-items:center; padding:1rem; font-family:"Segoe UI", system-ui, sans-serif; }
    .actions button, .actions a { padding:.55rem 1rem; border-radius:8px; border:1px solid #2563eb; font:inherit; font-weight:600; text-decoration:none; cursor:pointer; }
    .actions button { background:#2563eb; color:#fff; }
    .actions a { background:#fff; color:#2563eb; }
    :focus-visible { outline:3px solid #f0b429; outline-offset:2px; }
    .receipt { width:320px; max-width:calc(100% - 2rem); margin:0 auto 2rem; background:#fff; padding:1.3rem 1.2rem; box-shadow:0 2px 10px rgba(0,0,0,.15); }
    h1 { margin:0; font-size:1.2rem; text-align:center; }
    .c { text-align:center; margin:.2rem 0; }
    p { margin:.3rem 0; font-size:.85rem; }
    hr { border:0; border-top:1px dashed #555; margin:.7rem 0; }
    table { width:100%; border-collapse:collapse; font-size:.85rem; }
    th { text-align:left; border-bottom:1px solid #111; padding-bottom:.2rem; }
    td { padding:.25rem 0; vertical-align:top; }
    .r { text-align:right; }
    .verse { text-align:center; font-style:italic; font-size:.8rem; margin:.5rem 0 0; }
    .total { display:flex; justify-content:space-between; font-size:1.1rem; font-weight:700; margin:.3rem 0; }
    @media print {
      body { background:#fff; }
      .actions { display:none; }
      .receipt { box-shadow:none; margin:0; width:100%; max-width:none; padding:0; }
      @page { margin:6mm; }
    }
  </style>
</head>
<body>
  <div class="actions">
    <button onclick="window.print()" autofocus>Print receipt</button>
    <a href="<?= $isAdmin ? 'sales.php' : 'cashier.php' ?>"><?= $isAdmin ? 'Back to sales' : 'New sale' ?></a>
  </div>
  <div class="receipt">
    <h1><?= e(STORE_NAME) ?></h1>
    <p class="c"><?= e(STORE_TAGLINE) ?></p>
    <hr>
    <p>Receipt #<?= str_pad($sale['id'], 6, '0', STR_PAD_LEFT) ?><br>
       Date: <?= e(date('M j, Y g:i A', strtotime($sale['sale_date']))) ?><br>
       Cashier: <?= e($sale['cashier']) ?></p>
    <hr>
    <table>
      <tr><th>Item</th><th class="r">Qty</th><th class="r">Price</th><th class="r">Amount</th></tr>
      <?php foreach ($lines as $l): ?>
      <tr>
        <td><?= e($l['name']) ?></td>
        <td class="r"><?= (int)$l['quantity'] ?></td>
        <td class="r"><?= number_format($l['price'], 2) ?></td>
        <td class="r"><?= number_format($l['price'] * $l['quantity'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
    <hr>
    <div class="total"><span>TOTAL</span><span><?= peso($sale['total_amount']) ?></span></div>
    <p class="c">Items: <?= (int)$count ?></p>
    <hr>
    <p class="c">Thank you for shopping with us!</p>
    <p class="verse">&ldquo;<?= e($verse) ?>&rdquo;<br>&mdash; <?= e($verseRef) ?></p>
  </div>
</body>
</html>