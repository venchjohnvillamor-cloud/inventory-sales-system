<?php
require 'config.php';
require_role('admin');

$ranges = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month'];
$r = $_GET['range'] ?? 'today';
if (!isset($ranges[$r])) { $r = 'today'; }
$starts = ['today' => date('Y-m-d'), '7d' => date('Y-m-d', strtotime('-6 days')),
           '30d' => date('Y-m-d', strtotime('-29 days')), 'month' => date('Y-m-01')];
$from = $starts[$r] . ' 00:00:00';

if (isset($_GET['export'])) {
    $st = $pdo->prepare('SELECT s.id, s.sale_date, u.name AS cashier, i.name AS item, si.quantity, si.price,
                                si.quantity * si.price AS line_total
                         FROM sales s JOIN users u ON u.id = s.user_id
                         JOIN sale_items si ON si.sale_id = s.id JOIN items i ON i.id = si.item_id
                         WHERE s.sale_date >= ? ORDER BY s.sale_date');
    $st->execute([$from]);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales-' . $r . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Receipt', 'Date', 'Cashier', 'Item', 'Qty', 'Price', 'Line total']);
    foreach ($st->fetchAll() as $row) { fputcsv($out, $row); }
    exit;
}

function q($pdo, $sql, $from) { $st = $pdo->prepare($sql); $st->execute([$from]); return $st; }
$sum = q($pdo, 'SELECT COUNT(*) AS receipts, COALESCE(SUM(total_amount), 0) AS revenue FROM sales WHERE sale_date >= ?', $from)->fetch();
$units = (int)q($pdo, 'SELECT COALESCE(SUM(si.quantity), 0) FROM sale_items si JOIN sales s ON s.id = si.sale_id WHERE s.sale_date >= ?', $from)->fetchColumn();
$trend = q($pdo, 'SELECT DATE(sale_date) AS d, SUM(total_amount) AS t FROM sales WHERE sale_date >= ? GROUP BY DATE(sale_date) ORDER BY d', $from)->fetchAll();
$cats = q($pdo, 'SELECT COALESCE(NULLIF(i.category, ""), "Uncategorized") AS cat, SUM(si.quantity * si.price) AS t
                 FROM sale_items si JOIN items i ON i.id = si.item_id JOIN sales s ON s.id = si.sale_id
                 WHERE s.sale_date >= ? GROUP BY cat ORDER BY t DESC', $from)->fetchAll();
$fast = q($pdo, 'SELECT i.name, SUM(si.quantity) AS sold FROM sale_items si JOIN items i ON i.id = si.item_id
                 JOIN sales s ON s.id = si.sale_id WHERE s.sale_date >= ?
                 GROUP BY i.id, i.name ORDER BY sold DESC LIMIT 5', $from)->fetchAll();
$items = $pdo->query('SELECT name, quantity, reorder_level FROM items ORDER BY quantity, name')->fetchAll();

$total = count($items);
$out = $low = 0;
foreach ($items as $i) {
    if ($i['quantity'] <= 0) { $out++; } elseif ($i['quantity'] <= $i['reorder_level']) { $low++; }
}
$ok = $total - $low - $out;
$pct = $total ? round($ok / $total * 100, 1) : 0;
$w = function ($n) use ($total) { return $total ? round($n / $total * 100, 1) : 0; };
$maxTrend = $trend ? max(array_column($trend, 't')) : 1;
$maxCat = $cats ? max(array_column($cats, 't')) : 1;

admin_top('Dashboard', 'dashboard');
?>
<div class="crumbs">Dashboard / Admin dashboard</div>
<div class="head">
  <div>
    <div class="eyebrow">Store analytics</div>
    <h1>Admin dashboard</h1>
    <p class="sub">A live view of store performance and inventory health.</p>
  </div>
  <form method="get" class="filters">
    <label>Date range
      <select name="range">
        <?php foreach ($ranges as $k => $label): ?>
        <option value="<?= $k ?>"<?= $k === $r ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <button class="primary">Apply</button>
    <a class="btn" href="?range=<?= e($r) ?>&amp;export=1">Export report</a>
  </form>
</div>

<div class="grid">
  <div class="col">
    <section class="card">
      <h2>Sales overview <span class="chip"><?= e($ranges[$r]) ?></span></h2>
      <?php if (!$sum['receipts']): ?>
        <div class="empty"><strong>No completed sales</strong><span>Completed sales will appear in these numbers.</span></div>
      <?php else: ?>
        <div class="tiles">
          <div><small>Revenue</small><strong><?= peso($sum['revenue']) ?></strong></div>
          <div><small>Receipts</small><strong><?= (int)$sum['receipts'] ?></strong></div>
          <div><small>Items sold</small><strong><?= $units ?></strong></div>
          <div><small>Average sale</small><strong><?= peso($sum['revenue'] / $sum['receipts']) ?></strong></div>
        </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Revenue trend <span class="chip">Daily</span></h2>
      <?php if (!$trend): ?>
        <div class="empty"><strong>No sales in this period</strong><span>The trend will appear when sales are completed.</span></div>
      <?php else: ?>
        <div class="bars">
          <?php foreach ($trend as $t): ?>
          <div class="barcol">
            <span class="amt"><?= peso($t['t']) ?></span>
            <div class="track"><div class="fill" style="height:<?= max(4, round($t['t'] / $maxTrend * 100)) ?>%"></div></div>
            <span class="lbl"><?= e(date('M j', strtotime($t['d']))) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Stock health</h2>
      <div class="seg">
        <i class="g" style="width:<?= $w($ok) ?>%"></i><i class="a" style="width:<?= $w($low) ?>%"></i><i class="r" style="width:<?= $w($out) ?>%"></i>
      </div>
      <div class="legend"><span><b class="dot g"></b>In stock <?= $ok ?></span><span><b class="dot a"></b>Low stock <?= $low ?></span><span><b class="dot r"></b>Out of stock <?= $out ?></span></div>
      <h3>Lowest-stock products</h3>
      <table>
        <tr><th>Item</th><th>In stock</th><th>Reorder at</th></tr>
        <?php foreach (array_slice($items, 0, 5) as $i): ?>
        <tr>
          <td><?= e($i['name']) ?></td>
          <td class="<?= $i['quantity'] <= $i['reorder_level'] ? 'low' : '' ?>"><?= (int)$i['quantity'] ?></td>
          <td><?= (int)$i['reorder_level'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$items): ?><tr><td colspan="3">No products yet. Add one on the Products page.</td></tr><?php endif; ?>
      </table>
    </section>
  </div>

  <div class="col">
    <section class="card">
      <h2>Inventory health</h2>
      <div class="donut">
        <svg viewBox="0 0 140 140" aria-hidden="true">
          <circle cx="70" cy="70" r="54" fill="none" stroke="#e2e8f0" stroke-width="16"/>
          <circle cx="70" cy="70" r="54" fill="none" stroke="#059669" stroke-width="16"
                  stroke-dasharray="<?= round($pct / 100 * 339.29, 1) ?> 339.29" transform="rotate(-90 70 70)"/>
        </svg>
        <div class="mid"><strong><?= $pct ?>%</strong><small>In stock</small></div>
      </div>
      <div class="two">
        <div class="warn"><small>Low stock</small><strong><?= $low ?></strong></div>
        <div class="bad"><small>Out of stock</small><strong><?= $out ?></strong></div>
      </div>
    </section>

    <section class="card">
      <h2>Revenue by category</h2>
      <?php if (!$cats): ?>
        <div class="empty"><strong>No category revenue</strong><span>Completed sales are grouped by product category.</span></div>
      <?php else: foreach ($cats as $c): ?>
        <div class="rowbar">
          <div><span><?= e($c['cat']) ?></span><strong><?= peso($c['t']) ?></strong></div>
          <div class="meter"><i style="width:<?= round($c['t'] / $maxCat * 100) ?>%"></i></div>
        </div>
      <?php endforeach; endif; ?>
    </section>

    <section class="card">
      <h2>Fast-moving items <span class="chip"><?= e($ranges[$r]) ?></span></h2>
      <?php if (!$fast): ?>
        <div class="empty"><strong>No sales in this period</strong><span>Top sellers will appear here.</span></div>
      <?php else: ?>
        <table>
          <tr><th>Item</th><th>Units sold</th></tr>
          <?php foreach ($fast as $f): ?><tr><td><?= e($f['name']) ?></td><td><?= (int)$f['sold'] ?></td></tr><?php endforeach; ?>
        </table>
      <?php endif; ?>
    </section>
  </div>
</div>
<?php admin_bottom();
