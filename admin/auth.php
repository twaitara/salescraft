<?php
/**
 * Minimal session password gate for the admin area.
 * Include at the top of every admin page: require __DIR__ . '/auth.php';
 */
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

session_start();

// Handle login submit.
if (($_POST['_action'] ?? '') === 'login') {
    $ok = hash_equals((string) $CONFIG['admin']['password'], (string) ($_POST['password'] ?? ''));
    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['sc_admin'] = true;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    $loginError = 'Wrong password.';
}

// Handle logout.
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: index.php');
    exit;
}

// Gate.
if (empty($_SESSION['sc_admin'])) {
    $err = $loginError ?? '';
    ?><!doctype html>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SalesCraft Admin</title>
    <style>
      body{font-family:Inter,system-ui,Segoe UI,sans-serif;background:#f4f6fb;display:grid;place-items:center;height:100vh;margin:0}
      form{background:#fff;padding:32px;border-radius:16px;box-shadow:0 8px 24px rgba(16,24,40,.08);width:320px}
      h1{font-size:18px;margin:0 0 18px}
      input{width:100%;padding:12px 14px;border:1px solid #e6e9f2;border-radius:10px;font-size:15px;box-sizing:border-box}
      button{width:100%;margin-top:12px;padding:12px;border:0;border-radius:10px;background:#4f46e5;color:#fff;font-weight:600;font-size:15px;cursor:pointer}
      .err{color:#ef4444;font-size:13px;margin-top:10px}
    </style>
    <form method="post">
      <h1>SalesCraft — Admin</h1>
      <input type="password" name="password" placeholder="Admin password" autofocus>
      <input type="hidden" name="_action" value="login">
      <button type="submit">Sign in</button>
      <?php if ($err): ?><div class="err"><?= sc_e($err) ?></div><?php endif; ?>
    </form>
    <?php
    exit;
}
