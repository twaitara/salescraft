<?php
/**
 * Session password gate for the admin area.
 * Include at the top of every admin page: require __DIR__ . '/auth.php';
 *
 * Password source: the `admin_password_hash` setting (set via Settings). Until
 * one is set, it falls back to config.php's admin.password so first login works.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/brand.php';

session_start();

if (empty($_SESSION['sc_csrf'])) {
    $_SESSION['sc_csrf'] = bin2hex(random_bytes(16));
}
/** CSRF token for admin forms. */
function sc_csrf(): string { return (string) ($_SESSION['sc_csrf'] ?? ''); }
function sc_csrf_ok($t): bool { return is_string($t) && hash_equals((string) ($_SESSION['sc_csrf'] ?? ''), $t); }

function sc_admin_check_password(string $input): bool
{
    global $CONFIG;
    $hash = sc_setting('admin_password_hash');
    if ($hash !== '') {
        return password_verify($input, $hash);
    }
    $fallback = (string) ($CONFIG['admin']['password'] ?? '');
    return $fallback !== '' && hash_equals($fallback, $input);
}

// Handle login submit.
if (($_POST['_action'] ?? '') === 'login') {
    if (sc_admin_check_password((string) ($_POST['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['sc_admin'] = true;
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    $loginError = 'Wrong password.';
}

// Handle logout.
if (isset($_GET['logout'])) {
    unset($_SESSION['sc_admin']);
    header('Location: index.php');
    exit;
}

// Gate.
if (empty($_SESSION['sc_admin'])) {
    $err = $loginError ?? '';
    sc_admin_head('Sign in');
    ?>
    <div style="min-height:100vh;display:grid;place-items:center">
      <form method="post" class="card pad" style="width:340px">
        <div style="margin-bottom:18px"><?= sc_logo_lockup(32) ?></div>
        <div class="field full">
          <label>Admin password</label>
          <input type="password" name="password" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" autofocus>
        </div>
        <input type="hidden" name="_action" value="login">
        <div class="saverow" style="margin-top:16px">
          <button type="submit" class="btn primary" style="width:100%;justify-content:center">Sign in</button>
        </div>
        <?php if ($err): ?><div class="flash err" style="margin-top:16px;margin-bottom:0"><i data-lucide="alert-triangle"></i><?= sc_e($err) ?></div><?php endif; ?>
      </form>
    </div>
    <?php
    sc_admin_foot();
    exit;
}
