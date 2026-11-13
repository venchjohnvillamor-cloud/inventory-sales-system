<?php
require 'config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($act === 'add') {
        $pdo->prepare('INSERT INTO items (name, category, price, quantity, reorder_level) VALUES (?, ?, ?, ?, ?)')
            ->execute([trim($_POST['name']), trim($_POST['category']), (float)$_POST['price'],
                       (int)$_POST['quantity'], (int)$_POST['reorder_level']]);
    } elseif ($act === 'save') {
        $pdo->prepare('UPDATE items SET price = ?, reorder_level = ?, quantity = GREATEST(0, quantity + ?) WHERE id = ?')
            ->execute([(float)$_POST['price'], (int)$_POST['reorder_level'], (int)$_POST['restock'], $id]);
    } elseif ($act === 'delete') {
        try {
            $pdo->prepare('DELETE FROM items WHERE id = ?')->execute([$id]);
        } catch (PDOException $ex) {
            $_SESSION['flash'] = 'This item has sales records, so it cannot be deleted.';
        }
    }
    header('Location: inventory.php');
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$items = $pdo->query('SELECT * FROM items ORDER BY name')->fetchAll();
$cats = $pdo->query('SELECT name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
admin_top('Products', 'products');
?>
<h1>Products</h1>
<?php if ($flash): ?><p class="note"><?= e($flash) ?></p><?php endif; ?>
<h2>Add item</h2>
<form method="post" class="addform">
  <input type="hidden" name="action" value="add">
  <label>Item name <input name="name" required></label>
  <label>Category <select name="category"><option value="">Uncategorized</option><?php foreach ($cats as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></label>
  <label>Price (₱) <input type="number" name="price" step="0.01" min="0" required></label>
  <label>Stock <input type="number" name="quantity" min="0" required></label>
  <label>Reorder level <input type="number" name="reorder_level" min="0" value="10" required></label>
  <button class="primary">Add item</button>
</form>

<h2>All items</h2>
<table>
  <tr><th>Item</th><th>Category</th><th>Stock</th><th>Price</th><th>Reorder level</th><th>Add stock</th><th></th></tr>
  <?php foreach ($items as $i): $f = 'f' . (int)$i['id']; ?>
  <tr>
    <td><?= e($i['name']) ?></td>
    <td><?= e($i['category']) ?></td>
    <td class="<?= $i['quantity'] <= $i['reorder_level'] ? 'low' : '' ?>"><?= (int)$i['quantity'] ?></td>
    <td><input form="<?= $f ?>" type="number" name="price" step="0.01" min="0" value="<?= e($i['price']) ?>"></td>
    <td><input form="<?= $f ?>" type="number" name="reorder_level" min="0" value="<?= (int)$i['reorder_level'] ?>"></td>
    <td><input form="<?= $f ?>" type="number" name="restock" value="0"></td>
    <td>
      <form id="<?= $f ?>" method="post" class="inline">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
        <button>Save changes</button>
      </form>
      <form method="post" class="inline" onsubmit="return confirm('Delete this item?');">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= (int)$i['id'] ?>">
        <button class="plain">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php admin_bottom();
