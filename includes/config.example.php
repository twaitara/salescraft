<?php
/**
 * SalesCraft configuration — copy this file to config.php and fill in real values.
 * config.php is gitignored so credentials never reach the repo.
 */

return [
    // --- Database (MySQL) ---
    'db' => [
        'host' => '127.0.0.1',
        'name' => 'salescraft',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    // --- Consultant / notifications ---
    // Where the "a client filled the scorecard" email is sent.
    'notify' => [
        'to_email' => 'consultant@example.com',
        'to_name'  => 'Sales Consultant',
    ],

    // --- Outgoing mail (SMTP) ---
    'smtp' => [
        'host'      => 'smtp.example.com',
        'port'      => 587,
        'secure'    => 'tls',          // 'tls' or 'ssl'
        'username'  => 'noreply@example.com',
        'password'  => 'change-me',
        'from_email'=> 'noreply@example.com',
        'from_name' => 'SalesCraft',
    ],

    // Base URL of the app (used to build shareable links in emails).
    'base_url' => 'http://127.0.0.1:8000',
];
