<?php
require 'config.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $given = strtoupper(trim($_POST['code'] ?? ''));
    if (empty($_SESSION['captcha']) || !hash_equals($_SESSION['captcha'], $given)) {
        $error = 'The verification code is wrong. Try again.';
    } else {
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([trim($_POST['username'] ?? '')]);
        $u = $st->fetch();
        if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role']];
            header('Location: ' . ($u['role'] === 'admin' ? 'dashboard.php' : 'cashier.php'));
            exit;
        }
        $error = 'Wrong username or password.';
    }
}
$chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
$code = '';
for ($i = 0; $i < 4; $i++) { $code .= $chars[random_int(0, strlen($chars) - 1)]; }
$_SESSION['captcha'] = $code;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log in | <?= e(STORE_NAME) ?></title>
  <link rel="stylesheet" href="login.css">
</head>
<body>
<div class="split">
  <section class="left">
    <div class="tag">
      <span class="logo"><?= e(mb_substr(STORE_NAME, 0, 1)) ?></span>
      <strong><?= e(STORE_NAME) ?></strong>
    </div>
    <svg class="art" viewBox="0 0 560 520" role="img" aria-label="Illustration of a grocery bag and a shopper pushing a cart">
      <circle cx="500" cy="440" r="26" fill="#d5e6dd"/><circle cx="430" cy="490" r="16" fill="#d5e6dd"/><circle cx="40" cy="60" r="22" fill="#d5e6dd"/>
      <path d="M90 140C60 60 170 20 260 50C360 10 470 60 480 160C520 250 470 340 400 380C330 450 200 440 130 390C40 330 30 230 90 140Z" fill="#5f9e8a"/>
      <path d="M150 170C150 100 240 80 300 100C380 90 430 150 420 220C440 300 360 350 290 340C210 360 140 310 150 240Z" fill="#a9cfc0" opacity=".65"/>
      <circle cx="80" cy="120" r="30" fill="#fff" opacity=".35"/><circle cx="445" cy="125" r="28" fill="#fff" opacity=".35"/>
      <circle cx="470" cy="235" r="16" fill="#fff" opacity=".35"/><circle cx="62" cy="235" r="18" fill="#fff" opacity=".35"/>
      <rect x="236" y="120" width="22" height="104" rx="11" fill="#d99a4a" transform="rotate(-12 247 172)"/>
      <ellipse cx="205" cy="188" rx="36" ry="13" fill="#7fb3c8" transform="rotate(-25 205 188)"/>
      <rect x="325" y="168" width="16" height="70" rx="6" fill="#9b2f2f"/><rect x="329" y="154" width="8" height="18" rx="3" fill="#7a2323"/>
      <ellipse cx="300" cy="206" rx="22" ry="14" fill="#4c9a52"/>
      <rect x="210" y="212" width="130" height="122" fill="#e0b878"/>
      <path d="M210 212l-14 20v102h14z" fill="#c99a52"/>
      <ellipse cx="280" cy="342" rx="150" ry="18" fill="#7fb0a0"/>
      <rect x="148" y="244" width="34" height="64" fill="#fff"/><rect x="148" y="262" width="34" height="14" fill="#4aa3df"/>
      <rect x="186" y="256" width="22" height="52" rx="5" fill="#f59e0b"/>
      <circle cx="376" cy="294" r="36" fill="#2f7d46"/>
      <path d="M376 258v72M352 270l48 48M352 318l48-48" stroke="#58a868" stroke-width="5" opacity=".7"/>
      <path d="M262 340l50-26 6 26z" fill="#f4cf4a"/>
      <path d="M330 330c20 6 46-6 52-24-14 14-34 20-52 12z" fill="#f2c21b"/>
      <circle cx="326" cy="338" r="12" fill="#d63a3a"/>
      <circle cx="122" cy="352" r="12" fill="#2b2f4a"/><circle cx="124" cy="357" r="10" fill="#f2c4a0"/>
      <path d="M116 424L102 488M130 424L154 480" stroke="#26356e" stroke-width="12" stroke-linecap="round"/>
      <ellipse cx="100" cy="492" rx="10" ry="4" fill="#8a4b2f"/><ellipse cx="158" cy="484" rx="10" ry="4" fill="#8a4b2f"/>
      <rect x="106" y="368" width="28" height="60" rx="9" fill="#3b82c4"/>
      <path d="M130 382L176 402" stroke="#f2c4a0" stroke-width="8" stroke-linecap="round"/>
      <rect x="182" y="376" width="14" height="20" fill="#d63a3a"/><rect x="198" y="380" width="14" height="16" fill="#4c9a52"/><rect x="214" y="374" width="14" height="22" fill="#f59e0b"/><rect x="230" y="382" width="14" height="14" fill="#e8e0d0"/>
      <polygon points="172,396 258,396 246,450 186,450" fill="#e8eaed" stroke="#8a9199" stroke-width="3"/>
      <path d="M172 396L162 384" stroke="#c0392b" stroke-width="5" stroke-linecap="round"/>
      <path d="M190 450L188 472M242 450L244 472" stroke="#8a9199" stroke-width="3"/>
      <circle cx="188" cy="478" r="8" fill="#3a3f47"/><circle cx="244" cy="478" r="8" fill="#3a3f47"/>
    </svg>
    <footer class="foot">&copy; <?= date('Y') ?> <?= e(STORE_NAME) ?> &middot; Inventory and Sales Web System</footer>
  </section>

  <section class="right">
    <form method="post" class="box">
      <h1>Welcome to <?= e(STORE_NAME) ?></h1>
      <?php if ($error): ?><p class="err"><?= e($error) ?></p><?php endif; ?>
      <div class="grp">
        <label for="u">Username</label>
        <div class="field"><span class="ic"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7z"/></svg></span><input id="u" name="username" required autofocus></div>
      </div>
      <div class="grp">
        <label for="p">Password</label>
        <div class="field"><span class="ic"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 10V8a5 5 0 0110 0v2h1a1 1 0 011 1v9a1 1 0 01-1 1H6a1 1 0 01-1-1v-9a1 1 0 011-1zm2 0h6V8a3 3 0 00-6 0z"/></svg></span><input id="p" type="password" name="password" required></div>
      </div>
      <div class="grp">
        <label for="c">Code</label>
        <div class="coderow">
          <div class="field"><span class="ic"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l8 3v6c0 5-3.4 9.4-8 11-4.6-1.6-8-6-8-11V5z"/><path d="M8.5 12l2.5 2.5 4.5-5" fill="none" stroke="#0e3b2e" stroke-width="2"/></svg></span><input id="c" name="code" maxlength="4" autocomplete="off" required></div>
          <a class="code" href="login.php" title="Click to get a new code"><?= e($code) ?></a>
        </div>
      </div>
      <button class="btn">Log in</button>
    </form>
  </section>
</div>
<script src="cursor-trail.js"></script>
</body>
</html>