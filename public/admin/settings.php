<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/../includes/mailer.php';
require __DIR__ . '/../includes/pdf.php';

$flash = '';
$flashType = 'ok';

/* ---- Save settings (redirect-after-post) ---- */
if (($_POST['_action'] ?? '') === 'save') {
    // Plain text settings.
    $keys = [
        'brand_name', 'intro_headline', 'consultant_name', 'consultant_email',
        'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_from_email', 'smtp_from_name',
        'base_url', 'captcha_mode', 'recaptcha_site_key',
    ];
    foreach ($keys as $k) {
        sc_setting_set($k, trim((string) ($_POST[$k] ?? '')));
    }
    // Checkbox.
    sc_setting_set('require_phone', isset($_POST['require_phone']) ? '1' : '0');

    // Secrets: only overwrite when a new value is typed (blank = keep existing).
    foreach (['smtp_pass', 'recaptcha_secret'] as $secret) {
        $v = (string) ($_POST[$secret] ?? '');
        if ($v !== '') sc_setting_set($secret, $v);
    }

    // Admin password change.
    $pw = (string) ($_POST['new_password'] ?? '');
    if ($pw !== '') {
        if ($pw !== (string) ($_POST['confirm_password'] ?? '')) {
            header('Location: settings.php?err=pw');
            exit;
        }
        sc_setting_set('admin_password_hash', password_hash($pw, PASSWORD_DEFAULT));
    }

    header('Location: settings.php?saved=1');
    exit;
}

/* ---- Send a test email (to any address you type) using the SAVED settings ---- */
if (($_POST['_action'] ?? '') === 'test') {
    $to = trim((string) ($_POST['test_email'] ?? ''));
    if ($to !== '' && !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $flash = 'That test address is not a valid email.'; $flashType = 'err';
    } else {
        $sc = sc_scorecard();
        $cats = []; $total = 0; $max = 0;
        foreach ($sc['categories'] as $cat) {
            $qs = $cat['questions'] ?? []; $qsnap = []; $sum = 0;
            foreach ($qs as $qi => $q) { $v = ($qi % 5) + 1; $sum += $v; $qsnap[] = ['t' => $q['t'] ?? '', 'v' => $v]; }
            $cm = count($qs) * 5;
            $cats[] = ['name' => $cat['name'] ?? '', 'score' => $sum, 'max' => $cm, 'fix' => $cat['fix'] ?? '', 'questions' => $qsnap];
            $total += $sum; $max += $cm;
        }
        $sample = [
            'id' => 0, 'client_name' => 'Test Client', 'client_company' => 'Preview Co',
            'client_email' => 'test@example.com', 'client_phone' => '+000 000 0000',
            'total' => $total, 'max' => $max, 'percent' => $max ? round($total / $max * 100) : 0,
            'band' => sc_band($max ? $total / $max : 0), 'categories' => $cats,
        ];
        $pdf = null;
        try { $pdf = sc_build_pdf($sample + ['brand' => sc_setting('brand_name', 'SalesCraft'), 'date' => date('j M Y')]); }
        catch (Throwable $e) { $pdf = null; }

        $dest = $to !== '' ? $to : sc_setting('consultant_email');
        $res  = sc_send_notification(sc_mail_config(), $sample, $pdf, $to !== '' ? $to : null);
        if ($res['ok']) { $flash = 'Test email (with sample PDF) sent to ' . $dest . '.'; }
        else { $flash = 'Test email failed: ' . $res['error']; $flashType = 'err'; }
    }
}

if (isset($_GET['saved'])) { $flash = 'Settings saved.'; }
if (isset($_GET['err']) && $_GET['err'] === 'pw') { $flash = 'Passwords did not match — password unchanged.'; $flashType = 'err'; }

// Current values for the form.
$g = fn(string $k) => sc_setting($k);
$hasPass = sc_setting('smtp_pass') !== '';
$hasSecret = sc_setting('recaptcha_secret') !== '';
$mode = $g('captcha_mode');
$scorecardUrl = rtrim($g('base_url'), '/') . '/';

sc_admin_head('Settings');
sc_admin_topbar('settings');
?>
<div class="wrap" style="max-width:760px">
  <div class="page-h"><div><h1>Settings</h1><div class="sub">Everything here is yours to configure. Clients never see it.</div></div></div>

  <?php if ($flash): ?>
    <div class="flash <?= $flashType ?>"><i data-lucide="<?= $flashType === 'ok' ? 'check-circle' : 'alert-triangle' ?>"></i><?= sc_e($flash) ?></div>
  <?php endif; ?>

  <form method="post" class="card pad">
    <input type="hidden" name="_action" value="save">

    <!-- Profile / brand -->
    <div class="form-sec">
      <h2><i data-lucide="user"></i> Your profile & brand</h2>
      <p class="desc">Who receives results, and how the scorecard is titled for clients.</p>
      <div class="frow">
        <div class="field"><label>Brand name</label><input name="brand_name" value="<?= sc_e($g('brand_name')) ?>"></div>
        <div class="field"><label>Your name (consultant)</label><input name="consultant_name" value="<?= sc_e($g('consultant_name')) ?>"></div>
      </div>
      <div class="frow">
        <div class="field"><label>Notification email</label><input type="email" name="consultant_email" value="<?= sc_e($g('consultant_email')) ?>" placeholder="you@yourdomain.com">
          <div class="hint">Where the "a client completed the scorecard" email is sent.</div></div>
        <div class="field"><label>Scorecard headline</label><input name="intro_headline" value="<?= sc_e($g('intro_headline')) ?>"></div>
      </div>
    </div>

    <!-- Email / SMTP -->
    <div class="form-sec">
      <h2><i data-lucide="mail"></i> Outgoing email (SMTP)</h2>
      <p class="desc">Used to send you the notification. Use your mailbox's SMTP details.</p>
      <div class="frow">
        <div class="field"><label>SMTP host</label><input name="smtp_host" value="<?= sc_e($g('smtp_host')) ?>" placeholder="smtp.yourhost.com"></div>
        <div class="field"><label>Port</label><input name="smtp_port" value="<?= sc_e($g('smtp_port')) ?>" placeholder="587"></div>
      </div>
      <div class="frow">
        <div class="field"><label>Encryption</label>
          <select name="smtp_secure">
            <option value="tls" <?= $g('smtp_secure') === 'tls' ? 'selected' : '' ?>>TLS (587)</option>
            <option value="ssl" <?= $g('smtp_secure') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option>
          </select></div>
        <div class="field"><label>SMTP username</label><input name="smtp_user" value="<?= sc_e($g('smtp_user')) ?>" autocomplete="off"></div>
      </div>
      <div class="frow">
        <div class="field"><label>SMTP password</label><input type="password" name="smtp_pass" placeholder="<?= $hasPass ? '•••••••• (unchanged)' : 'enter password' ?>" autocomplete="new-password"><div class="hint">Leave blank to keep the current password.</div></div>
        <div class="field"><label>From name</label><input name="smtp_from_name" value="<?= sc_e($g('smtp_from_name')) ?>"></div>
      </div>
      <div class="frow">
        <div class="field"><label>From email</label><input type="email" name="smtp_from_email" value="<?= sc_e($g('smtp_from_email')) ?>"></div>
        <div class="field"><label>App base URL</label><input name="base_url" value="<?= sc_e($g('base_url')) ?>" placeholder="https://nineonetwo.online/salescraft"><div class="hint">Used to build the "view results" link in emails.</div></div>
      </div>
    </div>

    <!-- Lead gate / captcha -->
    <div class="form-sec">
      <h2><i data-lucide="shield-check"></i> Lead gate & CAPTCHA</h2>
      <p class="desc">Confirm the client is a serious human before they start.</p>
      <label class="check" style="margin-bottom:16px"><input type="checkbox" name="require_phone" <?= $g('require_phone') === '1' ? 'checked' : '' ?>> Require a phone number before starting</label>
      <div class="frow">
        <div class="field"><label>CAPTCHA type</label>
          <select name="captcha_mode" id="capmode">
            <option value="builtin" <?= $mode === 'builtin' ? 'selected' : '' ?>>Built-in (simple math — works out of the box)</option>
            <option value="recaptcha" <?= $mode === 'recaptcha' ? 'selected' : '' ?>>Google reCAPTCHA v2</option>
            <option value="off" <?= $mode === 'off' ? 'selected' : '' ?>>Off (not recommended)</option>
          </select></div>
        <div class="field"></div>
      </div>
      <div id="recap" style="<?= $mode === 'recaptcha' ? '' : 'display:none' ?>">
        <div class="frow">
          <div class="field"><label>reCAPTCHA site key</label><input name="recaptcha_site_key" value="<?= sc_e($g('recaptcha_site_key')) ?>"></div>
          <div class="field"><label>reCAPTCHA secret key</label><input type="password" name="recaptcha_secret" placeholder="<?= $hasSecret ? '•••••••• (unchanged)' : 'enter secret' ?>" autocomplete="new-password"><div class="hint">Leave blank to keep the current secret.</div></div>
        </div>
      </div>
    </div>

    <!-- Security -->
    <div class="form-sec">
      <h2><i data-lucide="lock"></i> Admin password</h2>
      <p class="desc">Change the password you use to sign in here.</p>
      <div class="frow">
        <div class="field"><label>New password</label><input type="password" name="new_password" autocomplete="new-password" placeholder="Leave blank to keep current"></div>
        <div class="field"><label>Confirm new password</label><input type="password" name="confirm_password" autocomplete="new-password"></div>
      </div>
    </div>

    <div class="saverow">
      <button type="submit" class="btn primary"><i data-lucide="save"></i> Save settings</button>
    </div>
  </form>

  <!-- Test email + share link -->
  <div class="card pad" style="margin-top:16px">
    <div class="form-sec" style="padding-top:0">
      <h2><i data-lucide="send"></i> Test & share</h2>
      <p class="desc">Your shareable scorecard link (send this to clients):</p>
      <div class="field full"><input readonly value="<?= sc_e($scorecardUrl) ?>" onclick="this.select()"></div>
      <form method="post" style="margin-top:14px">
        <input type="hidden" name="_action" value="test">
        <div class="frow" style="align-items:end">
          <div class="field"><label>Send a test to</label><input type="email" name="test_email" placeholder="<?= sc_e(sc_setting('consultant_email') ?: 'you@example.com') ?>"><div class="hint">Leave blank to send to your notification email.</div></div>
          <div class="field"><button type="submit" class="btn"><i data-lucide="mail-check"></i> Send test email (with sample PDF)</button><div class="hint" style="margin-top:8px">Save your SMTP settings first.</div></div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.getElementById('capmode').addEventListener('change', function(){
    document.getElementById('recap').style.display = this.value === 'recaptcha' ? '' : 'none';
  });
</script>
<?php sc_admin_foot(); ?>
