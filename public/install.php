<?php
/**
 * SalesCraft Scorecard — one-page web installer.
 * Upload the app, open this in a browser, fill the form. It checks requirements,
 * creates the database tables, seeds settings, and writes includes/config.php.
 * DELETE this file after a successful install.
 */
declare(strict_types=1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$CONFIG_PATH = __DIR__ . '/includes/config.php';
$already = file_exists($CONFIG_PATH);

/* ---------- requirement checks ---------- */
$reqs = [
    'PHP 7.4+'            => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO MySQL driver'    => extension_loaded('pdo_mysql'),
    'JSON'               => function_exists('json_encode'),
    'random_bytes()'      => function_exists('random_bytes'),
    'includes/ writable'  => is_writable(__DIR__ . '/includes') || is_writable(__DIR__),
];
$reqsOk = !in_array(false, $reqs, true);

$errors = [];
$done = false;

/* ---------- guess a sensible base URL ---------- */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$guessBase = $scheme . '://' . $host . $dir;

/* ---------- handle submit ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reqsOk && !$already) {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = (string) ($_POST['db_pass'] ?? '');
    $adminPw = (string) ($_POST['admin_pw'] ?? '');
    $baseUrl = rtrim(trim($_POST['base_url'] ?? ''), '/');
    $brand   = trim($_POST['brand'] ?? 'SalesCraft') ?: 'SalesCraft';
    $notify  = trim($_POST['notify'] ?? '');

    if ($dbName === '' || $dbUser === '') $errors[] = 'Database name and user are required.';
    if (strlen($adminPw) < 4) $errors[] = 'Choose an admin password (at least 4 characters).';
    if ($notify !== '' && !filter_var($notify, FILTER_VALIDATE_EMAIL)) $errors[] = 'The notification email is not valid.';

    $pdo = null;
    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $e) {
            $errors[] = 'Could not connect to the database: ' . $e->getMessage();
        }
    }

    if (!$errors && $pdo) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                client_name VARCHAR(150) NOT NULL, client_company VARCHAR(150) DEFAULT NULL,
                client_email VARCHAR(190) DEFAULT NULL, client_phone VARCHAR(40) DEFAULT NULL,
                total INT NOT NULL, max_score INT NOT NULL DEFAULT 200, percent DECIMAL(5,2) NOT NULL,
                band VARCHAR(40) NOT NULL, categories TEXT NOT NULL, answers TEXT NOT NULL,
                ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_created (created_at), INDEX idx_email (client_email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                skey VARCHAR(64) PRIMARY KEY, sval TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $pdo->exec("CREATE TABLE IF NOT EXISTS progress (
                email VARCHAR(190) PRIMARY KEY, answers TEXT NOT NULL, step INT NOT NULL DEFAULT 0,
                meta TEXT DEFAULT NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Seed a few starter settings.
            $seed = $pdo->prepare('INSERT INTO settings (skey, sval) VALUES (?, ?)
                                   ON DUPLICATE KEY UPDATE sval = VALUES(sval)');
            $seed->execute(['brand_name', $brand]);
            $seed->execute(['base_url', $baseUrl]);
            $seed->execute(['captcha_mode', 'builtin']);
            $seed->execute(['require_phone', '1']);
            $seed->execute(['smtp_from_name', $brand]);
            if ($notify !== '') $seed->execute(['consultant_email', $notify]);

            // Write config.php
            $tpl = "<?php\nreturn [\n"
                . "    'db' => [\n"
                . "        'host' => '" . addslashes($dbHost) . "',\n"
                . "        'name' => '" . addslashes($dbName) . "',\n"
                . "        'user' => '" . addslashes($dbUser) . "',\n"
                . "        'pass' => '" . addslashes($dbPass) . "',\n"
                . "        'charset' => 'utf8mb4',\n"
                . "    ],\n"
                . "    'admin' => [\n"
                . "        'password' => '" . addslashes($adminPw) . "',\n"
                . "    ],\n"
                . "];\n";
            if (@file_put_contents($CONFIG_PATH, $tpl) === false) {
                $errors[] = 'Tables were created, but includes/config.php could not be written. '
                    . 'Create it manually with these DB details, or make the includes/ folder writable and retry.';
            } else {
                $done = true;
            }
        } catch (Throwable $e) {
            $errors[] = 'Setup failed: ' . $e->getMessage();
        }
    }
}

function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
$adminUrl = $guessBase . '/admin/';
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install · SalesCraft Scorecard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--bg:#0a0e14;--card:#151c26;--ink:#e8eef5;--muted:#8b97a6;--line:#232e3b;--brand:#f5901e;--brand-d:#d9790f;
  --surface:#101722;--ok:#22c55e;--err:#f87171}
*{box-sizing:border-box}body{margin:0;font-family:'Inter',system-ui,Segoe UI,sans-serif;color:var(--ink);line-height:1.5;
  background:radial-gradient(1000px 460px at 82% -8%,rgba(245,144,30,.16),transparent 60%),var(--bg);min-height:100vh}
.wrap{max-width:560px;margin:0 auto;padding:40px 20px 70px}
.brand{display:flex;align-items:center;gap:11px;margin-bottom:24px}
.tile{width:42px;height:42px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(150deg,#f9a63f,#f5901e 55%,#e07d0d);
  color:#fff;font-weight:800;font-size:22px;box-shadow:0 6px 16px rgba(245,144,30,.4)}
.brand b{font-size:19px;font-weight:800}.brand span{color:var(--brand)}
.card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:26px;box-shadow:0 14px 34px rgba(0,0,0,.5)}
h1{font-size:20px;margin:0 0 4px}.sub{color:var(--muted);font-size:14px;margin:0 0 20px}
.req{list-style:none;padding:0;margin:0 0 20px;font-size:14px}
.req li{display:flex;align-items:center;gap:9px;padding:5px 0}
.req .b{width:20px;height:20px;border-radius:6px;display:grid;place-items:center;font-size:12px;font-weight:800;color:#fff}
.req .ok{background:var(--ok)}.req .no{background:var(--err)}
label{display:block;font-size:12.5px;font-weight:600;color:var(--muted);margin:14px 0 6px}
input{width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--ink);
  font:inherit;font-size:16px}
input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(245,144,30,.15)}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.hint{font-size:12px;color:var(--muted);margin-top:5px}
.btn{width:100%;margin-top:22px;padding:14px;border:0;border-radius:11px;font:inherit;font-weight:700;font-size:15px;cursor:pointer;
  background:linear-gradient(160deg,#f9a63f,#f5901e 60%,#e07d0d);color:#fff;box-shadow:0 8px 20px rgba(245,144,30,.32)}
.msg{padding:12px 15px;border-radius:11px;font-size:14px;margin-bottom:16px}
.msg.err{background:rgba(239,68,68,.12);color:var(--err);border:1px solid rgba(239,68,68,.3)}
.msg.ok{background:rgba(22,163,74,.12);color:var(--ok);border:1px solid rgba(22,163,74,.3)}
a{color:var(--brand);font-weight:600}
.warn{background:rgba(245,144,30,.1);border:1px solid rgba(245,144,30,.35);color:#f5b970;padding:12px 15px;border-radius:11px;font-size:13.5px;margin-top:16px}
code{background:var(--surface);padding:2px 6px;border-radius:5px;font-size:13px}
@media(max-width:520px){.row{grid-template-columns:1fr}}
</style></head><body>
<div class="wrap">
  <div class="brand"><span class="tile">S</span><b>SalesCraft <span>Scorecard</span></b></div>
  <div class="card">

  <?php if ($done): ?>
    <div class="msg ok">Installed successfully! 🎉</div>
    <h1>You're ready to go</h1>
    <p class="sub">The database is set up and your config is written.</p>
    <p style="font-size:14.5px">Your scorecard: <a href="<?= h($guessBase) ?>/"><?= h($guessBase) ?>/</a><br>
       Admin dashboard: <a href="<?= h($adminUrl) ?>"><?= h($adminUrl) ?></a> (log in with the admin password you just set).</p>
    <div class="warn"><b>Important:</b> delete <code>install.php</code> from the server now, then finish your SMTP / branding under <b>Admin → Settings</b>.</div>

  <?php elseif ($already): ?>
    <h1>Already installed</h1>
    <p class="sub">A <code>includes/config.php</code> already exists, so SalesCraft is set up.</p>
    <p style="font-size:14.5px">Open the <a href="<?= h($adminUrl) ?>">admin dashboard</a>. For security, <b>delete <code>install.php</code></b>. To reinstall, remove <code>includes/config.php</code> first.</p>

  <?php else: ?>
    <h1>Install SalesCraft Scorecard</h1>
    <p class="sub">Create a MySQL database + user in cPanel first, then fill this in.</p>

    <ul class="req">
      <?php foreach ($reqs as $name => $ok): ?>
        <li><span class="b <?= $ok ? 'ok' : 'no' ?>"><?= $ok ? '✓' : '✕' ?></span> <?= h($name) ?></li>
      <?php endforeach; ?>
    </ul>

    <?php if (!$reqsOk): ?>
      <div class="msg err">Your server doesn't meet all requirements above. Fix those, then reload this page.</div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="msg err"><?= h($e) ?></div><?php endforeach; ?>

    <?php if ($reqsOk): ?>
    <form method="post">
      <label>Database name</label>
      <input name="db_name" value="<?= h($_POST['db_name'] ?? '') ?>" placeholder="e.g. user_salescraft" required>
      <div class="row">
        <div><label>Database user</label><input name="db_user" value="<?= h($_POST['db_user'] ?? '') ?>" required></div>
        <div><label>Database password</label><input name="db_pass" type="password"></div>
      </div>
      <label>Database host</label>
      <input name="db_host" value="<?= h($_POST['db_host'] ?? 'localhost') ?>">
      <div class="hint">Almost always <code>localhost</code> on cPanel.</div>

      <label>Admin password (you choose — for logging into the dashboard)</label>
      <input name="admin_pw" type="password" required>

      <div class="row">
        <div><label>Brand name</label><input name="brand" value="<?= h($_POST['brand'] ?? 'SalesCraft') ?>"></div>
        <div><label>Notification email (optional)</label><input name="notify" type="email" value="<?= h($_POST['notify'] ?? '') ?>" placeholder="you@domain.com"></div>
      </div>

      <label>Site address (base URL)</label>
      <input name="base_url" value="<?= h($_POST['base_url'] ?? $guessBase) ?>">
      <div class="hint">Where this app lives. Used for links in emails.</div>

      <button class="btn" type="submit">Install now</button>
    </form>
    <?php endif; ?>
  <?php endif; ?>

  </div>
</div>
</body></html>
