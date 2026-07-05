<?php
/**
 * Lead gate: verifies the client's details + CAPTCHA BEFORE they start the
 * questions. On success it stores a verified "gate pass" in the session that
 * submit.php later requires. This is where we confirm the client is a serious
 * human (phone + captcha), not a bot.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

function gate_fail(string $msg, string $field = ''): void
{
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => $msg, 'field' => $field, 'captcha' => sc_captcha_new()]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) gate_fail('Invalid request');

$name    = trim((string) ($data['name'] ?? ''));
$email   = trim((string) ($data['email'] ?? ''));
$phone   = trim((string) ($data['phone'] ?? ''));
$company = trim((string) ($data['company'] ?? ''));

if ($name === '') gate_fail('Please enter your name.', 'name');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) gate_fail('Please enter a valid email.', 'email');

if (sc_setting('require_phone', '1') === '1') {
    // Require at least 7 digits; allow +, spaces, (), -.
    $digits = preg_replace('/\D+/', '', $phone);
    if (strlen($digits) < 7) gate_fail('Please enter a valid phone number.', 'phone');
}

// --- CAPTCHA ---
$mode = sc_setting('captcha_mode', 'builtin');
if ($mode === 'recaptcha') {
    $token = (string) ($data['recaptcha'] ?? '');
    if (!sc_recaptcha_verify($token)) gate_fail('Please complete the "I\'m not a robot" check.', 'captcha');
} elseif ($mode === 'builtin') {
    $answer = (string) ($data['captcha'] ?? '');
    if (!sc_captcha_check($answer)) gate_fail('That answer wasn\'t right — try the new question.', 'captcha');
}
// mode 'off' → no check

$_SESSION['sc_gate'] = [
    'name'    => $name,
    'email'   => $email,
    'phone'   => $phone,
    'company' => $company,
    'ts'      => time(),
];

// Record the lead as soon as they clear the gate, so the admin sees people who
// started even if they never finish. Keeps any existing answers/step (resume).
try {
    $meta = json_encode(['name' => $name, 'company' => $company, 'phone' => $phone, 'email' => $email]);
    sc_db()->prepare(
        'INSERT INTO progress (email, answers, step, meta) VALUES (?, "{}", 1, ?)
         ON DUPLICATE KEY UPDATE meta = VALUES(meta)'
    )->execute([$email, $meta]);
} catch (Throwable $e) {
    // progress table may not exist yet — non-fatal
}

// Offer to resume if this email has saved progress (safe: only after captcha).
$progress = null;
try {
    $st = sc_db()->prepare('SELECT answers, step FROM progress WHERE email = ?');
    $st->execute([$email]);
    if ($row = $st->fetch()) {
        $a = json_decode((string) $row['answers'], true);
        if (is_array($a) && $a) $progress = ['answers' => $a, 'step' => (int) $row['step']];
    }
} catch (Throwable $e) {
    $progress = null; // table may not exist yet
}

echo json_encode(['ok' => true, 'progress' => $progress]);
