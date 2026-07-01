<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$checks = [
    'base_url' => (bool) crtlu_config('CRTLU_BASE_URL'),
    'stripe_secret_key' => str_starts_with((string) crtlu_config('STRIPE_SECRET_KEY', ''), 'sk_'),
    'stripe_webhook_secret' => str_starts_with((string) crtlu_config('STRIPE_WEBHOOK_SECRET', ''), 'whsec_'),
    'database_dsn' => (bool) crtlu_config('CRTLU_DB_DSN'),
    'database_user' => (bool) crtlu_config('CRTLU_DB_USER'),
    'admin_password' => (bool) crtlu_config('CRTLU_ADMIN_PASS'),
];

$databaseOk = false;
$databaseError = null;

try {
    crtlu_pdo()->query('SELECT 1');
    $databaseOk = true;
} catch (Throwable $error) {
    $databaseError = 'Database connection failed.';
}

$checks['database_connection'] = $databaseOk;
$ok = !in_array(false, $checks, true);

http_response_code($ok ? 200 : 500);
echo json_encode([
    'ok' => $ok,
    'checks' => $checks,
    'database_error' => $databaseError,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

