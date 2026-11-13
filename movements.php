<?php
// Shared by stockin.php and adjustments.php
require 'config.php';
require_role('admin');
$in = ($type ?? 'in') === 'in';
$page = $in ? 'stockin.php' : 'adjustments.php';
$reasons = ['Damaged', 'Expired', 'Lost', 'Count correction', 'Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $iid = (int)($_POST['item_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    $sup = $in ? ((int)($_POST['supplier_id'] ?? 0) ?: null) : null;
    $reason = $in ? null : (in_array($_POST['reason'] ?? '', $reasons, true) ? $_POST['reason'] : 'Other');
    if (($in && $qty <= 0) || (!$in && $qty === 0)) {
        $_SESSION['flash'] = $in ? 'Enter a quantity above zero.' : 'Enter a change other than zero.';
    } else {
        try {
            $pdo->beginTransaction();
            $up = $pdo->prepare('UPDATE items SET quantity = quantity + ? WHERE id = ? AND quantity + ? >= 0');
            $up->execute([$qty, $iid, $qty]);
            if ($up->rowCount() === 0) {
                throw new Exception('That change would make the stock negative, or the item was not found.');
            }
            $pdo->prepare('INSERT INTO stock_movements (item_id, type, quantity, supplier_id, reason, user_id) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$iid, $in ? 'in' : 'adjust', $qty, $sup, $reason, $_SESSION['user']['id']]);
            $pdo->commit();
            $_SESSION['flash'] = 'Saved. The stock was updated.';
        } catch (Exception $ex) {
            $pdo->rollBack();
            $_SESSION['flash'] = $ex->getMessage();
        }
    }
    header('Location: ' . $page);
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$items = $pdo->query('SELECT id, name, quantity FROM items ORDER BY name')->fetchAll();
$sups = $pdo->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();
$st = $pdo->prepare('SELECT m.created_at, i.name AS item, m.quantity, s.name AS supplier, m.reason, u.name AS staff
                     FROM stock_movements m JOIN items i ON i.id = m.item_id JOIN users u ON u.id = m.user_id
                     LEFT JOIN suppliers s ON s.id = m.supplier_id WHERE m.type = ? ORDER BY m.id DESC LIMIT 30');
$st->execute([$in ? 'in' : 'adjust']);
$rows = $st->fetchAll();
admin_top($in ? 'Stock-in' : 'Adjustments', $in ? 'stockin' : 'adjustments');
?>
<h1><?= $in ? 'Stock-in' : 'Adjustments' ?></h1>
<p class="sub"><?= $in ? 'Record a delivery. The stock goes up right away.' : 'Correct the stock for damaged, expired, lost, or miscounted items. Use a minus number to reduce it.' ?></p>
<?php if ($flash): ?><p class="note"><?= e($flash) ?></p><?php endif; ?>
<form method="post" class="addform">
  <label>Item
    <select name="item_id" required>
      <?php foreach ($items as $i): ?><option value="<?= (int)$i['id'] ?>"><?= e($i['name']) ?> (stock <?= (int)$i['quantity'] ?>)</option><?php endforeach; ?>
    </select>
  </label>
  <label><?= $in ? 'Quantity received' : 'Change (+ or -)' ?> <input type="number" name="quantity" required<?= $in ? ' min="1"' : '' ?>></label>
  <?php if ($in): ?>
  <label>Supplier
    <select name="supplier_id"><option value="">None</option>
      <?php foreach ($sups as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
    </select>
  </label>
  <?php else: ?>
  <label>Reason
    <select name="reason"><?php foreach ($reasons as $r): ?><option><?= e($r) ?></option><?php endforeach; ?></select>
  </label>
  <?php endif; ?>
  <button class="primary">Save</button>
</form>
<h2>Recent <?= $in ? 'stock-in' : 'adjustments' ?></h2>
<table>
  <tr><th>Date</th><th>Item</th><th><?= $in ? 'Added' : 'Change' ?></th><th><?= $in ? 'Supplier' : 'Reason' ?></th><th>By</th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e(date('M j, Y g:i A', strtotime($r['created_at']))) ?></td>
    <td><?= e($r['item']) ?></td>
    <td><?= ($r['quantity'] > 0 ? '+' : '') . (int)$r['quantity'] ?></td>
    <td><?= e($in ? ($r['supplier'] ?: 'None') : $r['reason']) ?></td>
    <td><?= e($r['staff']) ?></td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="5">No records yet.</td></tr><?php endif; ?>
</table>
<?php admin_bottom();
