<?php

require __DIR__ . '/config.php';
require __DIR__ . '/catalog.php';
require __DIR__ . '/notifications.php';
require __DIR__ . '/member-auth.php';

function stripe_signature_is_valid(string $payload, string $header, string $secret): bool
{
    $parts = [];
    foreach (explode(',', $header) as $piece) {
        [$key, $value] = array_pad(explode('=', $piece, 2), 2, null);
        if ($key && $value) {
            $parts[$key][] = $value;
        }
    }

    $timestamp = $parts['t'][0] ?? null;
    $signatures = $parts['v1'] ?? [];
    if (!$timestamp || !$signatures || abs(time() - (int)$timestamp) > 300) {
        return false;
    }

    $signedPayload = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signedPayload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) {
            return true;
        }
    }
    return false;
}

function stripe_get(string $path): array
{
    $secret = crtlu_config('STRIPE_SECRET_KEY');
    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret],
    ]);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($raw === false || $status >= 400) {
        throw new RuntimeException('Stripe read failed.');
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Stripe returned invalid JSON.');
    }
    return $decoded;
}

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$webhookSecret = crtlu_config('STRIPE_WEBHOOK_SECRET');

if (!$webhookSecret || !stripe_signature_is_valid($payload, $signature, $webhookSecret)) {
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

$event = json_decode($payload, true);
if (!is_array($event)) {
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

if (($event['type'] ?? '') !== 'checkout.session.completed') {
    http_response_code(200);
    echo 'Ignored';
    exit;
}

$session = $event['data']['object'] ?? [];
$sessionId = (string)($session['id'] ?? '');
$lineItems = stripe_get('checkout/sessions/' . rawurlencode($sessionId) . '/line_items?limit=100');
$customer = $session['customer_details'] ?? [];
$shipping = $session['shipping_details'] ?? [];
$metadata = $session['metadata'] ?? [];

$pdo = crtlu_pdo();
$pdo->beginTransaction();

try {
    $orderColumns = crtlu_columns($pdo, 'orders');
    $metadataMemberId = (int)($metadata['member_id'] ?? 0);
    $memberId = $metadataMemberId > 0 && crtlu_member_by_id($pdo, $metadataMemberId)
        ? $metadataMemberId
        : crtlu_upsert_member(
        $pdo,
        $customer,
        (string)($metadata['locale'] ?? 'en'),
        (string)($metadata['display_currency'] ?? $session['currency'] ?? 'USD')
    );

    $orderData = [
        'stripe_session_id' => $sessionId,
        'customer_email' => $customer['email'] ?? '',
        'customer_name' => $customer['name'] ?? '',
        'phone' => $customer['phone'] ?? '',
        'amount_total' => (int)($session['amount_total'] ?? 0),
        'currency' => $session['currency'] ?? 'usd',
        'payment_status' => $session['payment_status'] ?? 'paid',
        'shipping_name' => $shipping['name'] ?? '',
        'shipping_address_json' => json_encode($shipping['address'] ?? [], JSON_UNESCAPED_SLASHES),
        'status' => 'paid',
        'member_id' => $memberId,
        'coupon_code' => $metadata['coupon_code'] ?? '',
        'discount_total' => (int)($metadata['discount_cents'] ?? 0),
        'display_currency' => $metadata['display_currency'] ?? '',
        'locale' => $metadata['locale'] ?? '',
    ];
    $insertData = array_intersect_key($orderData, array_flip($orderColumns));
    $columnsSql = implode(', ', array_map(static fn(string $column): string => '`' . $column . '`', array_keys($insertData)));
    $valuesSql = implode(', ', array_map(static fn(string $column): string => ':' . $column, array_keys($insertData)));

    $stmt = $pdo->prepare("INSERT IGNORE INTO orders ($columnsSql) VALUES ($valuesSql)");
    $stmt->execute(array_combine(
        array_map(static fn(string $column): string => ':' . $column, array_keys($insertData)),
        array_values($insertData)
    ));

    $orderId = (int)$pdo->lastInsertId();
    $emailItems = [];
    if ($orderId > 0) {
        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, product_name, quantity, unit_amount, currency)
            VALUES (:order_id, :product_name, :quantity, :unit_amount, :currency)'
        );
        foreach (($lineItems['data'] ?? []) as $item) {
            $price = $item['price'] ?? [];
            $product = $item['description'] ?? ($price['nickname'] ?? 'Product');
            $emailItems[] = [
                'product_name' => $product,
                'quantity' => (int)($item['quantity'] ?? 1),
                'unit_amount' => (int)($item['amount_subtotal'] ?? 0),
                'currency' => $price['currency'] ?? ($session['currency'] ?? 'usd'),
            ];
            $itemStmt->execute([
                ':order_id' => $orderId,
                ':product_name' => $product,
                ':quantity' => (int)($item['quantity'] ?? 1),
                ':unit_amount' => (int)($item['amount_subtotal'] ?? 0),
                ':currency' => $price['currency'] ?? ($session['currency'] ?? 'usd'),
            ]);
        }
        if (crtlu_table_exists($pdo, 'coupon_redemptions') && (string)($orderData['coupon_code'] ?? '') !== '') {
            $couponStmt = $pdo->prepare(
                'INSERT INTO coupon_redemptions (coupon_code, order_id, member_id, customer_email, discount_total)
                VALUES (:coupon_code, :order_id, :member_id, :customer_email, :discount_total)'
            );
            $couponStmt->execute([
                ':coupon_code' => $orderData['coupon_code'],
                ':order_id' => $orderId,
                ':member_id' => $memberId,
                ':customer_email' => $orderData['customer_email'],
                ':discount_total' => (int)($orderData['discount_total'] ?? 0),
            ]);
        }
        crtlu_send_order_email($pdo, $orderId, $orderData, $emailItems);
    }

    $pdo->commit();
    http_response_code(200);
    echo 'OK';
} catch (Throwable $error) {
    $pdo->rollBack();
    http_response_code(500);
    echo 'Webhook error';
}
