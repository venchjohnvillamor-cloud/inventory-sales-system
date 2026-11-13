<?php
session_start();

// Change these to your store's details.
const STORE_NAME = 'Goshen Mini Mart';
const STORE_TAGLINE = 'Groceries, drinks, and school supplies';
// Copy this file to config.php and fill in your own database details.
const DB_HOST = 'localhost';
const DB_NAME = 'inventory_sales_db';
const DB_USER = 'your_db_username';
const DB_PASS = 'your_db_password';
$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function require_role($role) {
    if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
        header('Location: login.php');
        exit;
    }
}

function page_top($title) {
    $u = $_SESSION['user'] ?? null;
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' | ' . e(STORE_NAME) . '</title>';
    echo '<link rel="stylesheet" href="style.css"></head><body>';
    echo '<header class="bar"><strong>' . e(STORE_NAME) . '</strong>';
    if ($u) {
        echo '<div class="userbox"><span class="who">' . e($u['name']) . ' <em>' . e($u['role']) . '</em></span>' . logout_link('logout') . '</div>';
    }
    echo '</header><main>';
}

function page_bottom() { echo '</main><script src="cursor-trail.js"></script></body></html>'; }

date_default_timezone_set('Asia/Manila');

function logout_link($class) {
    return '<a class="' . $class . '" href="logout.php">'
         . '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>'
         . '<span>Log out</span></a>';
}

function peso($n) { return '₱' . number_format((float)$n, 2); }

function admin_top($title, $active) {
    $u = $_SESSION['user'];
    $nav = ['dashboard' => ['Dashboard', 'dashboard.php'], 'categories' => ['Categories', 'categories.php'],
            'suppliers' => ['Suppliers', 'suppliers.php'], 'products' => ['Products', 'inventory.php'],
            'stockin' => ['Stock-in', 'stockin.php'], 'adjustments' => ['Adjustments', 'adjustments.php'],
            'sales' => ['Sales', 'sales.php']];
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'categories' => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
        'suppliers' => '<path d="M2 6h11v10H2z"/><path d="M13 9h5l3 3v4h-8z"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'products' => '<path d="M21 8l-9-5-9 5 9 5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'stockin' => '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4h6v3H9z"/><path d="M9 12h6M9 16h6"/>',
        'adjustments' => '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
        'sales' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
    ];
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' | ' . e(STORE_NAME) . '</title><link rel="stylesheet" href="admin.css?v=' . @filemtime(__DIR__ . '/admin.css') . '"></head><body>';
    echo '<div class="shell"><aside class="side"><div class="brand"><span class="logo">' . e(mb_substr(STORE_NAME, 0, 1)) . '</span>';
    echo '<div><strong>' . e(STORE_NAME) . '</strong><small>' . e(STORE_TAGLINE) . '</small></div></div><nav><p class="wk">Workspace</p>';
    foreach ($nav as $k => $n) {
        echo '<a href="' . $n[1] . '"' . ($k === $active ? ' class="on"' : '') . '>';
        echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $icons[$k] . '</svg>';
        echo '<span>' . $n[0] . '</span>' . ($k === $active ? '<i class="chev">&rsaquo;</i>' : '') . '</a>';
    }
    echo '</nav><div class="me"><span class="av">' . e(mb_strtoupper(mb_substr($u['name'], 0, 1))) . '</span>';
    echo '<div><strong>' . e($u['name']) . '</strong><small>Admin</small></div></div>' . logout_link('logout') . '</aside>';
    echo '<div class="content"><div class="top">' . e(STORE_NAME) . '</div><main>';
}

function admin_bottom() { echo '</main></div></div><script src="cursor-trail.js"></script></body></html>'; }