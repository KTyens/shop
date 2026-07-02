<?php

require __DIR__ . '/config.php';
require __DIR__ . '/promotions.php';
require __DIR__ . '/member-auth.php';

header('Content-Type: application/json');

function fail_checkout(string $message, int $status = 400): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}

function post_stripe(string $path, array $params): array
{
    $secret = crtlu_config('STRIPE_SECRET_KEY');
    if (!$secret || !str_starts_with($secret, 'sk_')) {
        fail_checkout('Stripe secret key is not configured.', 500);
    }

    $ch = curl_init('https://api.stripe.com/v1/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $secret,
            'Content-Type: application/x-www-form-urlencoded',
        ],
    ]);

    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($raw === false || $status >= 400) {
        fail_checkout($error ?: 'Stripe checkout request failed.', 502);
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        fail_checkout('Stripe returned an invalid response.', 502);
    }

    return $decoded;
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload) || !isset($payload['items']) || !is_array($payload['items'])) {
    fail_checkout('Invalid cart payload.');
}

$lineItems = [];
$metadataItems = [];
$index = 0;
$memberId = 0;
$couponCode = crtlu_coupon_code((string)($payload['coupon_code'] ?? ''));
$displayCurrency = strtoupper(substr((string)($payload['display_currency'] ?? 'USD'), 0, 8));
$locale = substr((string)($payload['locale'] ?? 'en'), 0, 12);
$discountResult = ['valid' => false, 'discount_cents' => 0];

try {
    try {
        $pdo = crtlu_pdo();
        $member = crtlu_current_member($pdo);
        $memberId = $member ? (int)$member['id'] : 0;
    } catch (Throwable $ignored) {
        $memberId = 0;
    }
    $cart = crtlu_cart_lines($payload['items']);
    if ($couponCode !== '') {
        $discountResult = crtlu_validate_coupon($couponCode, (int)$cart['subtotal_cents']);
        if (empty($discountResult['valid'])) {
            fail_checkout($discountResult['message'] ?? 'Coupon code is not valid.');
        }
        $cart['lines'] = crtlu_apply_discount_to_lines($cart['lines'], (int)$cart['subtotal_cents'], (int)$discountResult['discount_cents']);
    }
} catch (Throwable $error) {
    fail_checkout($error->getMessage());
}

foreach ($cart['lines'] as $line) {
    $id = $line['id'];
    $qty = $line['qty'];
    $product = $line['product'];
    $lineItems["line_items[$index][quantity]"] = $qty;
    $lineItems["line_items[$index][price_data][currency]"] = $product['currency'];
    $lineItems["line_items[$index][price_data][unit_amount]"] = $line['adjusted_unit_amount'];
    $lineItems["line_items[$index][price_data][product_data][name]"] = $product['name'];
    $lineItems["line_items[$index][price_data][product_data][description]"] = $product['description'];
    $metadataItems[] = $id . ':' . $qty;
    $index++;
}

if ($index === 0) {
    fail_checkout('Cart is empty.');
}

$params = array_merge($lineItems, [
    'mode' => 'payment',
    'success_url' => crtlu_base_url() . '/success/?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url' => crtlu_base_url() . '/?checkout=cancelled',
    'billing_address_collection' => 'required',
    'phone_number_collection[enabled]' => 'true',
    'shipping_address_collection[allowed_countries][0]' => 'US',
    'shipping_address_collection[allowed_countries][1]' => 'CA',
    'shipping_address_collection[allowed_countries][2]' => 'GB',
    'shipping_address_collection[allowed_countries][3]' => 'DE',
    'shipping_address_collection[allowed_countries][4]' => 'FR',
    'shipping_address_collection[allowed_countries][5]' => 'ES',
    'shipping_address_collection[allowed_countries][6]' => 'IT',
    'shipping_address_collection[allowed_countries][7]' => 'NL',
    'shipping_address_collection[allowed_countries][8]' => 'AU',
    'shipping_address_collection[allowed_countries][9]' => 'JP',
    'shipping_options[0][shipping_rate_data][type]' => 'fixed_amount',
    'shipping_options[0][shipping_rate_data][fixed_amount][amount]' => 1200,
    'shipping_options[0][shipping_rate_data][fixed_amount][currency]' => 'usd',
    'shipping_options[0][shipping_rate_data][display_name]' => 'Yanwen tracked shipping',
    'shipping_options[0][shipping_rate_data][delivery_estimate][minimum][unit]' => 'business_day',
    'shipping_options[0][shipping_rate_data][delivery_estimate][minimum][value]' => 7,
    'shipping_options[0][shipping_rate_data][delivery_estimate][maximum][unit]' => 'business_day',
    'shipping_options[0][shipping_rate_data][delivery_estimate][maximum][value]' => 18,
    'metadata[cart]' => implode(',', $metadataItems),
    'metadata[source]' => 'crtlu-shop',
    'metadata[coupon_code]' => $couponCode,
    'metadata[discount_cents]' => (string)(int)($discountResult['discount_cents'] ?? 0),
    'metadata[display_currency]' => $displayCurrency,
    'metadata[locale]' => $locale,
    'metadata[member_id]' => (string)$memberId,
]);

$session = post_stripe('checkout/sessions', $params);
echo json_encode(['url' => $session['url'] ?? null], JSON_UNESCAPED_SLASHES);
