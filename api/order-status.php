<?php

require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$sessionId = (string)($_GET['session_id'] ?? '');
if ($sessionId === '' || !str_starts_with($sessionId, 'cs_')) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session id.']);
    exit;
}

try {
    $pdo = crtlu_pdo();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE stripe_session_id = :session_id LIMIT 1');
    $stmt->execute([':session_id' => $sessionId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode([
            'found' => false,
            'message' => 'Order is still being confirmed by Stripe webhook.',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    $itemsStmt = $pdo->prepare('SELECT product_name, quantity, unit_amount, currency FROM order_items WHERE order_id = :order_id ORDER BY id ASC');
    $itemsStmt->execute([':order_id' => (int)$order['id']]);

    echo json_encode([
        'found' => true,
        'order' => [
            'id' => (int)$order['id'],
            'email' => $order['customer_email'],
            'amount_total' => (int)$order['amount_total'],
            'currency' => $order['currency'],
            'coupon_code' => $order['coupon_code'] ?? '',
            'discount_total' => (int)($order['discount_total'] ?? 0),
            'display_currency' => $order['display_currency'] ?? '',
            'locale' => $order['locale'] ?? '',
            'status' => $order['status'],
            'yanwen_tracking' => $order['yanwen_tracking'],
            'created_at' => $order['created_at'],
        ],
        'items' => $itemsStmt->fetchAll(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['error' => 'Order lookup failed.']);
}
