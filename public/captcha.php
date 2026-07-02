<?php
/** Returns a fresh built-in captcha question (JSON) and stores its answer in the session. */
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
session_start();
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['question' => sc_captcha_new()]);
