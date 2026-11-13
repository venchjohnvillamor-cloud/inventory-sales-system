<?php
require 'config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $f = [trim($_POST['name'] ?? ''), trim($_POST['contact'] ?? ''), trim($_POST['address'] ?? '')];
    if ($a === 'add' && $f[0] !== '') {
        $pdo->prepare('INSERT INTO suppliers (name, contact, address) VALUES (?, ?, ?)')->execute($f);
    } elseif ($a === 'save' && $f[0] !== '') {
        $pdo->prepare('UPDATE suppliers SET name = ?, contact = ?, address = ? WHERE id = ?')->execute(array_merge($f, [$id]));
    } elseif ($a === 'delete') {
        try {
            $pdo->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
        } catch (PDOException $ex) {
            $_SESSION['flash'] = 'This supplier has stock-in records, so it cannot be deleted.';
        }
    }
    header('Location: suppliers.php');
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$rows = $pdo->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();
admin_top('Suppliers', 'suppliers');
?>
<h1>Suppliers</h1>
<p class="sub">The people and companies you buy your stock from.</p>
<?php if ($flash): ?><p class="note"><?= e($flash) ?></p><?php endif; ?>
<form method="post" class="addform">
  <input type="hidden" name="action" value="add">
  <label>Supplier name <input name="name" required></label>
  <label>Contact <input name="contact"></label>
  <label>Address <input name="address"></label>
  <button class="primary">Add supplier</button>
</form>
<table>
  <tr><th>Supplier</th><th>Contact</th><th>Address</th><th></th></tr>
  <?php foreach ($rows as $r): $f = 's' . (int)$r['id']; ?>
  <tr>
    <td><input form="<?= $f ?>" name="name" value="<?= e($r['name']) ?>" required></td>
    <td><input form="<?= $f ?>" name="contact" value="<?= e($r['contact']) ?>"></td>
    <td><input form="<?= $f ?>" name="address" value="<?= e($r['address']) ?>"></td>
    <td>
      <form id="<?= $f ?>" method="post" class="inline">
        <input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button>Save changes</button>
      </form>
      <form method="post" class="inline" onsubmit="return confirm('Delete this supplier?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="plain">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="4">No suppliers yet. Add your first one above.</td></tr><?php endif; ?>
</table>
<?php admin_bottom();
