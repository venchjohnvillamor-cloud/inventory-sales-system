<?php
require 'config.php';
require_role('cashier');
$_SESSION['cart'] = $_SESSION['cart'] ?? [];
$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');
$qs = http_build_query(array_filter(['q' => $q, 'cat' => $cat], 'strlen'));
$action = 'cashier.php' . ($qs ? '?' . $qs : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = (int)($_POST['item_id'] ?? 0);
    if ($act === 'add') {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
    } elseif ($act === 'inc' || $act === 'dec') {
        $new = ($_SESSION['cart'][$id] ?? 0) + ($act === 'inc' ? 1 : -1);
        if ($new <= 0) { unset($_SESSION['cart'][$id]); } else { $_SESSION['cart'][$id] = $new; }
    } elseif ($act === 'remove') {
        unset($_SESSION['cart'][$id]);
    } elseif ($act === 'checkout' && $_SESSION['cart']) {
        try {
            $pdo->beginTransaction();
            $total = 0;
            $lines = [];
            foreach ($_SESSION['cart'] as $iid => $qty) {
                $st = $pdo->prepare('SELECT id, name, price FROM items WHERE id = ?');
                $st->execute([$iid]);
                $it = $st->fetch();
                if (!$it) { throw new Exception('An item is no longer available.'); }
                $up = $pdo->prepare('UPDATE items SET quantity = quantity - ? WHERE id = ? AND quantity >= ?');
                $up->execute([$qty, $iid, $qty]);
                if ($up->rowCount() === 0) { throw new Exception('Not enough stock for ' . $it['name'] . '.'); }
                $total += $it['price'] * $qty;
                $lines[] = [$iid, $qty, $it['price']];
            }
            $pdo->prepare('INSERT INTO sales (user_id, total_amount) VALUES (?, ?)')
                ->execute([$_SESSION['user']['id'], $total]);
            $sid = $pdo->lastInsertId();
            $li = $pdo->prepare('INSERT INTO sale_items (sale_id, item_id, quantity, price) VALUES (?, ?, ?, ?)');
            foreach ($lines as $l) { $li->execute([$sid, $l[0], $l[1], $l[2]]); }
            $pdo->commit();
            $_SESSION['cart'] = [];
            $_SESSION['flash'] = 'Sale saved. Total: ₱' . number_format($total, 2);
            $receipt = (int)$sid;
        } catch (Exception $ex) {
            $pdo->rollBack();
            $_SESSION['flash'] = $ex->getMessage();
        }
    }
    header('Location: ' . (isset($receipt) ? 'receipt.php?id=' . $receipt : $action));
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$sql = 'SELECT * FROM items WHERE name LIKE ?';
$args = ['%' . $q . '%'];
if ($cat !== '') { $sql .= ' AND category = ?'; $args[] = $cat; }
$st = $pdo->prepare($sql . ' ORDER BY name');
$st->execute($args);
$items = $st->fetchAll();
$cats = $pdo->query("SELECT DISTINCT category FROM items WHERE category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$all = $pdo->query('SELECT id, name, price FROM items')->fetchAll(PDO::FETCH_UNIQUE);
$total = 0;
$count = 0;
$u = $_SESSION['user'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cashier | <?= e(STORE_NAME) ?></title>
  <link rel="stylesheet" href="cashier.css">
</head>
<body>
<header class="top">
  <div class="brand"><span class="logo"><?= e(mb_substr(STORE_NAME, 0, 1)) ?></span>
    <div><strong><?= e(STORE_NAME) ?></strong><small>Cashier</small></div></div>
  <div class="me">
    <span class="av"><?= e(mb_strtoupper(mb_substr($u['name'], 0, 1))) ?></span>
    <span class="who"><?= e($u['name']) ?></span>
    <a class="logout" href="logout.php"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg><span>Log out</span></a>
  </div>
</header>

<main class="pos">
  <section class="catalog">
    <?php if ($flash): ?><p class="note"><?= e($flash) ?></p><?php endif; ?>
    <form method="get" class="search">
      <?php if ($cat !== ''): ?><input type="hidden" name="cat" value="<?= e($cat) ?>"><?php endif; ?>
      <input name="q" value="<?= e($q) ?>" placeholder="Search item name" aria-label="Search item name">
      <button class="primary">Search</button>
    </form>
    <nav class="chips" aria-label="Categories">
      <a href="cashier.php<?= $q !== '' ? '?q=' . urlencode($q) : '' ?>" class="<?= $cat === '' ? 'on' : '' ?>">All</a>
      <?php foreach ($cats as $c): ?>
      <a href="cashier.php?<?= e(http_build_query(array_filter(['q' => $q, 'cat' => $c], 'strlen'))) ?>" class="<?= $cat === $c ? 'on' : '' ?>"><?= e($c) ?></a>
      <?php endforeach; ?>
    </nav>

    <div class="grid">
      <?php foreach ($items as $i):
          $out = $i['quantity'] <= 0;
          $low = !$out && $i['quantity'] <= $i['reorder_level']; ?>
      <div class="item<?= $out ? ' out' : '' ?>">
        <div>
          <strong><?= e($i['name']) ?></strong>
          <small><?= e($i['category'] ?: 'Uncategorized') ?></small>
        </div>
        <div class="price"><?= peso($i['price']) ?></div>
        <span class="badge <?= $out ? 'r' : ($low ? 'a' : 'g') ?>">
          <?= $out ? 'Out of stock' : ($low ? 'Only ' . (int)$i['quantity'] . ' left' : (int)$i['quantity'] . ' in stock') ?>
        </span>
        <?php if (!$out): ?>
        <form method="post" action="<?= e($action) ?>" class="add">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="item_id" value="<?= (int)$i['id'] ?>">
          <input type="number" name="qty" value="1" min="1" max="<?= (int)$i['quantity'] ?>" aria-label="Quantity">
          <button class="primary">Add</button>
        </form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php if (!$items): ?><p class="empty">No items match your search.</p><?php endif; ?>
    </div>
  </section>

  <aside class="cart">
    <h2>Current sale</h2>
    <?php if (!$_SESSION['cart']): ?>
      <p class="empty">The cart is empty.<br>Add an item from the list.</p>
    <?php else: foreach ($_SESSION['cart'] as $iid => $qty):
        if (!isset($all[$iid])) { continue; }
        $sub = $all[$iid]['price'] * $qty;
        $total += $sub;
        $count += $qty; ?>
      <div class="line">
        <div class="ln"><strong><?= e($all[$iid]['name']) ?></strong><small><?= peso($all[$iid]['price']) ?> each</small></div>
        <div class="qty">
          <form method="post" action="<?= e($action) ?>"><input type="hidden" name="action" value="dec"><input type="hidden" name="item_id" value="<?= (int)$iid ?>"><button aria-label="Decrease quantity">&minus;</button></form>
          <span><?= (int)$qty ?></span>
          <form method="post" action="<?= e($action) ?>"><input type="hidden" name="action" value="inc"><input type="hidden" name="item_id" value="<?= (int)$iid ?>"><button aria-label="Increase quantity">+</button></form>
        </div>
        <strong class="sub"><?= peso($sub) ?></strong>
        <form method="post" action="<?= e($action) ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="item_id" value="<?= (int)$iid ?>"><button class="x" aria-label="Remove item">&times;</button></form>
      </div>
    <?php endforeach; ?>
      <div class="sum"><span>Items: <?= (int)$count ?></span></div>
      <div class="total"><span>Total</span><strong><?= peso($total) ?></strong></div>
      <form method="post" action="<?= e($action) ?>">
        <input type="hidden" name="action" value="checkout">
        <button class="primary big">Complete sale</button>
      </form>
    <?php endif; ?>
  </aside>
</main>
<script src="cursor-trail.js"></script>
</body>
</html>