<?php

require __DIR__ . '/config.php';
require __DIR__ . '/promotions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function fail_coupon(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['valid' => false, 'message' => $message], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    fail_coupon('Invalid coupon request.');
}

try {
    $cart = crtlu_cart_lines($payload['items'] ?? []);
    $result = crtlu_validate_coupon((string)($payload['coupon_code'] ?? ''), (int)$cart['subtotal_cents']);
    echo json_encode(array_merge($result, [
        'subtotal_cents' => (int)$cart['subtotal_cents'],
        'currency' => $cart['currency'],
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    fail_coupon($error->getMessage());
}
