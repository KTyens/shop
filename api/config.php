<?php

$localConfig = __DIR__ . '/config.local.php';
if (is_file($localConfig)) {
    require $localConfig;
}

function crtlu_config(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }

    return defined($key) ? constant($key) : $default;
}

function crtlu_allowed_origins(): array
{
    $raw = crtlu_config(
        'CRTLU_ALLOWED_ORIGINS',
        'https://shop.crtlu.me,http://localhost:4321,http://127.0.0.1:4321'
    ) ?: '';

    return array_values(array_filter(array_map(static function (string $origin): string {
        return rtrim(trim($origin), '/');
    }, explode(',', $raw))));
}

function crtlu_apply_cors(): void
{
    $origin = rtrim((string)($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
    if ($origin === '') {
        return;
    }

    $allowed = in_array($origin, crtlu_allowed_origins(), true);
    if ($allowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
        header('Vary: Origin', false);
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code($allowed ? 204 : 403);
        exit;
    }
}

function crtlu_base_url(): string
{
    return rtrim(crtlu_config('CRTLU_BASE_URL', 'https://shop.crtlu.me'), '/');
}

function crtlu_pdo(): PDO
{
    $dsn = crtlu_config('CRTLU_DB_DSN');
    $user = crtlu_config('CRTLU_DB_USER');
    $pass = crtlu_config('CRTLU_DB_PASS');

    if (!$dsn || !$user) {
        throw new RuntimeException('Database is not configured.');
    }

    return new PDO($dsn, $user, $pass ?: '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

crtlu_apply_cors();
