<?php
require 'config.php';
require_role('admin');
$rows = $pdo->query(
    'SELECT s.id, s.sale_date, s.total_amount, u.name AS cashier,
            GROUP_CONCAT(CONCAT(i.name, " x", si.quantity) SEPARATOR ", ") AS item_list
     FROM sales s JOIN users u ON u.id = s.user_id
     JOIN sale_items si ON si.sale_id = s.id JOIN items i ON i.id = si.item_id
     GROUP BY s.id, s.sale_date, s.total_amount, u.name
     ORDER BY s.id DESC LIMIT 50'
)->fetchAll();
admin_top('Sales', 'sales');
?>
<h1>Sales</h1>
<p class="sub">The 50 most recent completed sales.</p>
<table>
  <tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Items</th><th>Total</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><a href="receipt.php?id=<?= (int)$r['id'] ?>">#<?= (int)$r['id'] ?></a></td>
    <td><?= e(date('M j, Y g:i A', strtotime($r['sale_date']))) ?></td>
    <td><?= e($r['cashier']) ?></td>
    <td><?= e($r['item_list']) ?></td>
    <td><?= peso($r['total_amount']) ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="5">No sales yet. Completed sales from the cashier will appear here.</td></tr><?php endif; ?>
</table>
<?php admin_bottom();
