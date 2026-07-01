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

