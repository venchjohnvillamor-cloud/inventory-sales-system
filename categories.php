<?php
require 'config.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    try {
        if ($a === 'add' && $name !== '') {
            $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$name]);
        } elseif ($a === 'rename' && $name !== '') {
            $st = $pdo->prepare('SELECT name FROM categories WHERE id = ?');
            $st->execute([$id]);
            $old = $st->fetchColumn();
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?')->execute([$name, $id]);
            $pdo->prepare('UPDATE items SET category = ? WHERE category = ?')->execute([$name, $old]);
            $pdo->commit();
        } elseif ($a === 'delete') {
            $st = $pdo->prepare('SELECT COUNT(*) FROM items WHERE category = (SELECT name FROM categories WHERE id = ?)');
            $st->execute([$id]);
            if ($st->fetchColumn() > 0) {
                $_SESSION['flash'] = 'This category still has items. Move them to another category first.';
            } else {
                $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            }
        }
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $_SESSION['flash'] = 'That category name already exists.';
    }
    header('Location: categories.php');
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
$rows = $pdo->query('SELECT c.id, c.name, COUNT(i.id) AS n FROM categories c
                     LEFT JOIN items i ON i.category = c.name GROUP BY c.id, c.name ORDER BY c.name')->fetchAll();
admin_top('Categories', 'categories');
?>
<h1>Categories</h1>
<p class="sub">Group your products. Rename a category and its items follow automatically.</p>
<?php if ($flash): ?><p class="note"><?= e($flash) ?></p><?php endif; ?>
<form method="post" class="addform">
  <input type="hidden" name="action" value="add">
  <label>New category <input name="name" required></label>
  <button class="primary">Add category</button>
</form>
<table>
  <tr><th>Category</th><th>Items</th><th></th></tr>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><input form="r<?= (int)$r['id'] ?>" name="name" value="<?= e($r['name']) ?>" required></td>
    <td><?= (int)$r['n'] ?></td>
    <td>
      <form id="r<?= (int)$r['id'] ?>" method="post" class="inline">
        <input type="hidden" name="action" value="rename"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button>Save name</button>
      </form>
      <form method="post" class="inline" onsubmit="return confirm('Delete this category?');">
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="plain">Delete</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="3">No categories yet. Add your first one above.</td></tr><?php endif; ?>
</table>
<?php admin_bottom();
