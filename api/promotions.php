<?php

require_once __DIR__ . '/catalog.php';

function crtlu_coupon_code(string $code): string
{
    return strtoupper(trim(preg_replace('/\s+/', '', $code)));
}

function crtlu_coupons(): array
{
    $path = dirname(__DIR__) . '/data/coupons.json';
    if (!is_readable($path)) {
        return [];
    }

    $decoded = json_decode((string)file_get_contents($path), true);
    $coupons = [];
    foreach (($decoded['coupons'] ?? []) as $coupon) {
        $code = crtlu_coupon_code((string)($coupon['code'] ?? ''));
        if ($code === '') {
            continue;
        }
        $coupon['code'] = $code;
        $coupons[$code] = $coupon;
    }
    return $coupons;
}

function crtlu_cart_lines(array $items): array
{
    $catalog = crtlu_products();
    $lines = [];
    $subtotal = 0;

    foreach ($items as $item) {
        $id = (string)($item['id'] ?? '');
        $qty = (int)($item['qty'] ?? 0);
        if (!isset($catalog[$id]) || $qty < 1 || $qty > 10) {
            throw new InvalidArgumentException('Cart contains an invalid product or quantity.');
        }

        $product = $catalog[$id];
        $unitAmount = (int)$product['price_cents'];
        if ($unitAmount < 1) {
            throw new InvalidArgumentException('Cart contains a product without a valid price.');
        }

        $lines[] = [
            'id' => $id,
            'qty' => $qty,
            'product' => $product,
            'unit_amount' => $unitAmount,
            'adjusted_unit_amount' => $unitAmount,
        ];
        $subtotal += $unitAmount * $qty;
    }

    if (!$lines) {
        throw new InvalidArgumentException('Cart is empty.');
    }

    return ['lines' => $lines, 'subtotal_cents' => $subtotal, 'currency' => $lines[0]['product']['currency'] ?? 'usd'];
}

function crtlu_validate_coupon(string $code, int $subtotalCents): array
{
    $code = crtlu_coupon_code($code);
    if ($code === '') {
        return ['valid' => false, 'message' => 'Enter a coupon code.'];
    }

    $coupon = crtlu_coupons()[$code] ?? null;
    if (!$coupon || empty($coupon['active'])) {
        return ['valid' => false, 'message' => 'Coupon code is not valid.'];
    }

    $now = time();
    if (!empty($coupon['starts_at']) && strtotime((string)$coupon['starts_at']) > $now) {
        return ['valid' => false, 'message' => 'Coupon is not active yet.'];
    }
    if (!empty($coupon['ends_at']) && strtotime((string)$coupon['ends_at']) < $now) {
        return ['valid' => false, 'message' => 'Coupon has expired.'];
    }

    $minSubtotal = (int)($coupon['min_subtotal_cents'] ?? 0);
    if ($subtotalCents < $minSubtotal) {
        return [
            'valid' => false,
            'message' => 'Coupon requires a higher cart subtotal.',
            'min_subtotal_cents' => $minSubtotal,
        ];
    }

    $discount = 0;
    if (($coupon['type'] ?? '') === 'percent') {
        $percent = max(0, min(80, (float)($coupon['percent_off'] ?? 0)));
        $discount = (int)floor($subtotalCents * $percent / 100);
    } elseif (($coupon['type'] ?? '') === 'amount') {
        $discount = (int)($coupon['amount_off_cents'] ?? 0);
    }

    $maxDiscount = (int)($coupon['max_discount_cents'] ?? 0);
    if ($maxDiscount > 0) {
        $discount = min($discount, $maxDiscount);
    }
    $discount = max(0, min($discount, max(0, $subtotalCents - 100)));

    if ($discount < 1) {
        return ['valid' => false, 'message' => 'Coupon does not apply to this cart.'];
    }

    return [
        'valid' => true,
        'coupon' => [
            'code' => $code,
            'label' => (string)($coupon['label'] ?? $code),
            'type' => (string)($coupon['type'] ?? ''),
            'percent_off' => $coupon['percent_off'] ?? null,
            'amount_off_cents' => $coupon['amount_off_cents'] ?? null,
            'max_discount_cents' => $coupon['max_discount_cents'] ?? null,
            'min_subtotal_cents' => $coupon['min_subtotal_cents'] ?? null,
        ],
        'discount_cents' => $discount,
        'message' => (string)($coupon['label'] ?? 'Coupon applied.'),
    ];
}

function crtlu_apply_discount_to_lines(array $lines, int $subtotalCents, int $discountCents): array
{
    if ($discountCents < 1 || $subtotalCents < 1) {
        return $lines;
    }

    $ratio = max(0.01, ($subtotalCents - $discountCents) / $subtotalCents);
    foreach ($lines as &$line) {
        $line['adjusted_unit_amount'] = max(1, (int)round($line['unit_amount'] * $ratio));
    }
    unset($line);

    return $lines;
}
