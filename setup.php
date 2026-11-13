<?php
// Run once in the browser, then delete this file.
require 'config.php';
if ($pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) {
    exit('Setup already done. Delete setup.php.');
}
$st = $pdo->prepare('INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)');
$st->execute(['Store Admin', 'admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
$st->execute(['Cashier One', 'cashier', password_hash('cashier123', PASSWORD_DEFAULT), 'cashier']);
echo 'Done. Log in as admin / admin123 or cashier / cashier123, then delete setup.php.';
