<?php
/**
 * Shared bootstrap: loads config, exposes helpers, opens a PDO connection lazily.
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

/** Score band from a 0..1 ratio — mirrors the front-end. */
function sc_band(float $pct): string
{
    if ($pct >= 0.80) return 'Scalable Engine';
    if ($pct >= 0.60) return 'Strong Foundation';
    if ($pct >= 0.40) return 'Needs Structure';
    return 'High Risk';
}

function sc_e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
