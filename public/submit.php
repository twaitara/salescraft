<?php
/**
 * Receives a completed scorecard (JSON POST), stores it, and emails the consultant.
 * Scores are recomputed server-side from the raw answers so totals can't be spoofed.
 */
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

function fail(int $code, string $msg): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Method not allowed');
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    fail(400, 'Invalid payload');
}

$name    = trim((string) ($data['client_name'] ?? ''));
$company = trim((string) ($data['client_company'] ?? ''));
$email   = trim((string) ($data['client_email'] ?? ''));
$answers = $data['answers'] ?? null;

if ($name === '') {
    fail(422, 'Client name is required');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail(422, 'Invalid email address');
}
if (!is_array($answers)) {
    fail(422, 'Missing answers');
}

// Recompute per-category scores + total from the raw answers (server is authoritative).
$catCount = count(SC_CATEGORIES);
$categories = [];
$total = 0;
$answered = 0;
for ($si = 0; $si < $catCount; $si++) {
    $sum = 0;
    for ($qi = 0; $qi < SC_QUESTIONS_PER_CAT; $qi++) {
        $key = "$si-$qi";
        if (isset($answers[$key])) {
            $v = (int) $answers[$key];
            if ($v < 1 || $v > 5) {
                fail(422, "Answer $key out of range");
            }
            $sum += $v;
            $answered++;
        }
    }
    $categories[] = ['name' => SC_CATEGORIES[$si], 'score' => $sum];
    $total += $sum;
}

$expected = $catCount * SC_QUESTIONS_PER_CAT;
if ($answered !== $expected) {
    fail(422, "Incomplete: $answered of $expected questions answered");
}

$percent = round($total / SC_MAX * 100, 2);
$band    = sc_band($total / SC_MAX);

// Keep only valid, in-range answers for storage.
$cleanAnswers = [];
foreach ($answers as $k => $v) {
    if (preg_match('/^\d+-\d+$/', (string) $k)) {
        $cleanAnswers[$k] = (int) $v;
    }
}

try {
    $stmt = sc_db()->prepare(
        'INSERT INTO submissions
            (client_name, client_company, client_email, total, max_score, percent, band, categories, answers, ip, user_agent)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $name,
        $company !== '' ? $company : null,
        $email !== '' ? $email : null,
        $total,
        SC_MAX,
        $percent,
        $band,
        json_encode($categories, JSON_UNESCAPED_UNICODE),
        json_encode($cleanAnswers),
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
    $id = (int) sc_db()->lastInsertId();
} catch (Throwable $e) {
    fail(500, 'Could not save submission');
}

// Notify the consultant. A mail failure must not lose the saved submission.
$mail = sc_send_notification($CONFIG, [
    'id'             => $id,
    'client_name'    => $name,
    'client_company' => $company,
    'client_email'   => $email,
    'total'          => $total,
    'percent'        => $percent,
    'band'           => $band,
    'categories'     => $categories,
]);

echo json_encode([
    'ok'          => true,
    'id'          => $id,
    'total'       => $total,
    'band'        => $band,
    'emailed'     => $mail['ok'],
    'email_error' => $mail['ok'] ? null : $mail['error'],
]);
