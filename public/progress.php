<?php
/**
 * Saves / clears a client's in-progress answers, keyed by the email they
 * verified at the gate. Loading is done in gate.php (after captcha) so nobody
 * can fetch someone else's progress. Degrades gracefully if the `progress`
 * table doesn't exist yet.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

$gate = $_SESSION['sc_gate'] ?? null;
if (!is_array($gate) || empty($gate['email'])) {
    echo json_encode(['ok' => false, 'error' => 'Please start from the top of the form.']);
    exit;
}
$email = (string) $gate['email'];
$in = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $in['action'] ?? 'save';

try {
    if ($action === 'reset') {
        sc_db()->prepare('DELETE FROM progress WHERE email = ?')->execute([$email]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Save: sanitise answers to "si-qi" => 1..5
    $answers = is_array($in['answers'] ?? null) ? $in['answers'] : [];
    $clean = [];
    foreach ($answers as $k => $v) {
        if (preg_match('/^\d+-\d+$/', (string) $k)) {
            $vv = (int) $v;
            if ($vv >= 1 && $vv <= 5) $clean[$k] = $vv;
        }
    }
    $step = (int) ($in['step'] ?? 1);
    $meta = is_array($in['meta'] ?? null) ? $in['meta'] : [];
    $metaClean = [
        'name'    => (string) ($meta['name'] ?? ''),
        'company' => (string) ($meta['company'] ?? ''),
        'phone'   => (string) ($meta['phone'] ?? ''),
        'email'   => $email,
    ];

    sc_db()->prepare(
        'INSERT INTO progress (email, answers, step, meta) VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE answers = VALUES(answers), step = VALUES(step), meta = VALUES(meta)'
    )->execute([$email, json_encode($clean), $step, json_encode($metaClean)]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => 'Could not save progress on the server.']);
}
