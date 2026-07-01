<?php

require_once __DIR__ . '/../api/config.php';

function crtlu_require_admin(): void
{
    $user = crtlu_config('CRTLU_ADMIN_USER', 'admin');
    $pass = crtlu_config('CRTLU_ADMIN_PASS', '');
    $givenUser = $_SERVER['PHP_AUTH_USER'] ?? '';
    $givenPass = $_SERVER['PHP_AUTH_PW'] ?? '';

    if (!$pass || !hash_equals($user, $givenUser) || !hash_equals($pass, $givenPass)) {
        header('WWW-Authenticate: Basic realm="CRTLU Orders"');
        http_response_code(401);
        echo 'Authentication required';
        exit;
    }
}

function crtlu_money(int $cents, string $currency): string
{
    return strtoupper($currency) . ' ' . number_format($cents / 100, 2);
}

function crtlu_address_lines(?string $json): array
{
    $address = json_decode((string)$json, true);
    if (!is_array($address)) {
        return [];
    }

    return array_values(array_filter([
        $address['line1'] ?? '',
        $address['line2'] ?? '',
        trim(($address['city'] ?? '') . ' ' . ($address['state'] ?? '') . ' ' . ($address['postal_code'] ?? '')),
        $address['country'] ?? '',
    ]));
}

