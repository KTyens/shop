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
        'https://shop.crtlu.me,https://shop-crtlu.pages.dev,http://localhost:4321,http://127.0.0.1:4321'
    ) ?: '';

    return array_values(array_filter(array_map(static function (string $origin): string {
        return rtrim(trim($origin), '/');
    }, explode(',', $raw))));
}

/**
 * True when browser Origin may call this API with credentials.
 * Includes configured list + same parent domain as CRTLU_BASE_URL (e.g. shop/api.crtlu.me).
 */
function crtlu_origin_is_allowed(string $origin): bool
{
    $origin = rtrim($origin, '/');
    if ($origin === '') {
        return false;
    }
    if (in_array($origin, crtlu_allowed_origins(), true)) {
        return true;
    }

    $originHost = parse_url($origin, PHP_URL_HOST);
    $baseHost = parse_url(crtlu_base_url(), PHP_URL_HOST);
    if (!is_string($originHost) || $originHost === '' || !is_string($baseHost) || $baseHost === '') {
        return false;
    }

    $parentOf = static function (string $host): string {
        $parts = explode('.', strtolower($host));
        if (count($parts) < 2) {
            return $host;
        }
        return implode('.', array_slice($parts, -2));
    };

    return $parentOf($originHost) === $parentOf($baseHost);
}

function crtlu_apply_cors(): void
{
    $origin = rtrim((string)($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $allowed = $origin !== '' && crtlu_origin_is_allowed($origin);

    if ($allowed) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
        header('Vary: Origin', false);
    }

    // Always terminate preflight here so endpoint body parsers never run on OPTIONS.
    if ($method === 'OPTIONS') {
        http_response_code($allowed || $origin === '' ? 204 : 403);
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
