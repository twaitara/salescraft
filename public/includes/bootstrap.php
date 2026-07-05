<?php
/**
 * Shared bootstrap: loads config + DB-backed settings, exposes helpers.
 */
declare(strict_types=1);

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    exit('Missing includes/config.php — copy config.example.php and fill it in.');
}
$CONFIG = require $configPath;

/** The 10 categories, in the same order the front-end renders them. */
const SC_CATEGORIES = [
    'Sales Strategy', 'Sales Process', 'Lead Management', 'Sales Team', 'Messaging',
    'Objections', 'Sales Tools', 'CRM & Data', 'Management', 'Scalability',
];
const SC_QUESTIONS_PER_CAT = 4;
const SC_MAX = 200; // 10 categories * 4 questions * 5

/** Defaults used when a setting isn't in the DB (or the table doesn't exist yet). */
const SC_SETTING_DEFAULTS = [
    'brand_name'         => 'SalesCraft',
    'intro_headline'     => 'Where is your sales engine leaking?',
    'consultant_name'    => '',
    'consultant_email'   => '',   // where "a client finished" emails go
    'smtp_host'          => '',
    'smtp_port'          => '587',
    'smtp_secure'        => 'tls', // tls | ssl
    'smtp_user'          => '',
    'smtp_pass'          => '',
    'smtp_from_email'    => '',
    'smtp_from_name'     => 'SalesCraft',
    'base_url'           => '',
    'captcha_mode'       => 'builtin', // builtin | recaptcha | off
    'recaptcha_site_key' => '',
    'recaptcha_secret'   => '',
    'require_phone'      => '1',
    'admin_password_hash'=> '',
];

/** Lazy singleton PDO. */
function sc_db(): PDO
{
    static $pdo = null;
    global $CONFIG;
    if ($pdo === null) {
        $db = $CONFIG['db'];
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Load all settings from the DB once (empty array if the table is missing). */
function sc_settings_all(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (sc_db()->query('SELECT skey, sval FROM settings') as $row) {
                $cache[$row['skey']] = $row['sval'];
            }
        } catch (Throwable $e) {
            $cache = []; // table not created yet — fall back to defaults/config
        }
    }
    return $cache;
}

/** Get one setting: DB value if non-empty, else the given fallback, else the built-in default. */
function sc_setting(string $key, ?string $fallback = null): string
{
    $all = sc_settings_all();
    if (isset($all[$key]) && $all[$key] !== '') {
        return (string) $all[$key];
    }
    if ($fallback !== null) {
        return $fallback;
    }
    return SC_SETTING_DEFAULTS[$key] ?? '';
}

/** Upsert a setting (clears the in-process cache so reads stay fresh). */
function sc_setting_set(string $key, string $val): void
{
    $stmt = sc_db()->prepare(
        'INSERT INTO settings (skey, sval) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE sval = VALUES(sval)'
    );
    $stmt->execute([$key, $val]);
}

/**
 * Effective SMTP / notify config: DB settings first, config.php as fallback.
 * Returns the shape mailer.php expects.
 */
function sc_mail_config(): array
{
    global $CONFIG;
    $c = $CONFIG['smtp'] ?? [];
    $n = $CONFIG['notify'] ?? [];
    return [
        'smtp' => [
            'host'       => sc_setting('smtp_host', $c['host'] ?? ''),
            'port'       => sc_setting('smtp_port', (string) ($c['port'] ?? '587')),
            'secure'     => sc_setting('smtp_secure', $c['secure'] ?? 'tls'),
            'username'   => sc_setting('smtp_user', $c['username'] ?? ''),
            'password'   => sc_setting('smtp_pass', $c['password'] ?? ''),
            'from_email' => sc_setting('smtp_from_email', $c['from_email'] ?? ''),
            'from_name'  => sc_setting('smtp_from_name', $c['from_name'] ?? 'SalesCraft'),
        ],
        'notify' => [
            'to_email' => sc_setting('consultant_email', $n['to_email'] ?? ''),
            'to_name'  => sc_setting('consultant_name', $n['to_name'] ?? 'SalesCraft'),
        ],
        'base_url' => sc_setting('base_url', $CONFIG['base_url'] ?? ''),
    ];
}

/**
 * The active scorecard definition (admin-edited copy in settings, else default).
 * Returns ['categories' => [ ['name','icon','desc','fix','questions'=>[['t','a1','a3','a5']]] ]].
 */
function sc_scorecard(): array
{
    static $cache = null;
    if ($cache !== null) return $cache;
    $json = sc_setting('scorecard_schema');
    if ($json !== '') {
        $d = json_decode($json, true);
        if (is_array($d) && !empty($d['categories']) && is_array($d['categories'])) {
            $cache = $d;
            return $cache;
        }
    }
    $cache = require __DIR__ . '/scorecard_default.php';
    return $cache;
}

/** Total possible points for a scorecard schema (questions * 5). */
function sc_scorecard_max(array $sc): int
{
    $n = 0;
    foreach ($sc['categories'] as $c) $n += count($c['questions'] ?? []);
    return $n * 5;
}

/** Score band from a 0..1 ratio — mirrors the front-end. */
function sc_band(float $pct): string
{
    if ($pct >= 0.80) return 'Scalable Engine';
    if ($pct >= 0.60) return 'Strong Foundation';
    if ($pct >= 0.40) return 'Needs Structure';
    return 'High Risk';
}

/* ---------- CAPTCHA helpers ---------- */

/** Generate a fresh built-in math challenge; store the answer in the session; return the question. */
function sc_captcha_new(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['sc_captcha'] = $a + $b;
    return "$a + $b";
}

/** Check a built-in captcha answer against the session (single-use). */
function sc_captcha_check(string $answer): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $ok = isset($_SESSION['sc_captcha']) && (int) $answer === (int) $_SESSION['sc_captcha'];
    unset($_SESSION['sc_captcha']); // one attempt per challenge
    return $ok;
}

/** Verify a Google reCAPTCHA v2 token server-side. */
function sc_recaptcha_verify(string $token): bool
{
    $secret = sc_setting('recaptcha_secret');
    if ($secret === '' || $token === '') return false;
    $post = http_build_query(['secret' => $secret, 'response' => $token]);
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $post,
        'timeout' => 8,
    ]]);
    $resp = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    if ($resp === false) return false;
    $data = json_decode($resp, true);
    return !empty($data['success']);
}

function sc_e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
