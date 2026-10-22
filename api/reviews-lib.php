<?php

/**
 * Verified-buyer reviews: signed review tokens, order-item resolution, storage helpers.
 * Requires config.php (crtlu_config / crtlu_pdo) to be loaded first.
 *
 * Token format: base64url(json{v,o,i,p,x}) . "." . base64url(HMAC-SHA256(payload, CRTLU_REVIEW_SECRET))
 *   o = order id, i = order_item ids, p = product (series) ids, x = expiry unix time.
 */

const CRTLU_REVIEW_TOKEN_TTL_DAYS = 180;
const CRTLU_REVIEW_ELIGIBLE_STATUSES = ['paid', 'processing', 'shipped', 'delivered'];

function crtlu_b64url_encode(string $raw): string
{
    return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
}

function crtlu_b64url_decode(string $text): ?string
{
    if ($text === '' || preg_match('/[^A-Za-z0-9_-]/', $text)) {
        return null;
    }
    $decoded = base64_decode(strtr($text, '-_', '+/') . str_repeat('=', (4 - strlen($text) % 4) % 4), true);
    return $decoded === false ? null : $decoded;
}

function crtlu_review_secret(): string
{
    $secret = (string)(crtlu_config('CRTLU_REVIEW_SECRET') ?? '');
    if (strlen($secret) < 32) {
        throw new RuntimeException('Reviews are not configured (CRTLU_REVIEW_SECRET missing or shorter than 32 chars).');
    }
    return $secret;
}

function crtlu_review_token_create(int $orderId, array $orderItemIds, array $productIds, ?int $expiresAt = null): string
{
    $payload = json_encode([
        'v' => 1,
        'o' => $orderId,
        'i' => array_values(array_map('intval', $orderItemIds)),
        'p' => array_values(array_unique(array_map('strval', $productIds))),
        'x' => $expiresAt ?? (time() + CRTLU_REVIEW_TOKEN_TTL_DAYS * 86400),
    ], JSON_UNESCAPED_SLASHES);
    $body = crtlu_b64url_encode($payload);
    $sig = crtlu_b64url_encode(hash_hmac('sha256', $body, crtlu_review_secret(), true));
    return $body . '.' . $sig;
}

/** Returns decoded payload or null when signature/format/expiry is invalid. */
function crtlu_review_token_verify(string $token): ?array
{
    $token = trim($token);
    if (strlen($token) > 2000 || substr_count($token, '.') !== 1) {
        return null;
    }
    [$body, $sig] = explode('.', $token, 2);
    $expected = crtlu_b64url_encode(hash_hmac('sha256', $body, crtlu_review_secret(), true));
    if (!hash_equals($expected, $sig)) {
        return null;
    }
    $json = crtlu_b64url_decode($body);
    $data = $json !== null ? json_decode($json, true) : null;
    if (!is_array($data) || (int)($data['v'] ?? 0) !== 1 || (int)($data['o'] ?? 0) <= 0) {
        return null;
    }
    if ((int)($data['x'] ?? 0) < time()) {
        return null;
    }
    $data['i'] = array_values(array_filter(array_map('intval', (array)($data['i'] ?? [])), static fn(int $v): bool => $v > 0));
    $data['p'] = array_values(array_map('strval', (array)($data['p'] ?? [])));
    return $data;
}

function crtlu_review_link(string $token): string
{
    return crtlu_base_url() . '/review/?t=' . rawurlencode($token);
}

/** Catalog map: variant id => series id, plus lowercase "name variant label" / series name => ids. */
function crtlu_review_catalog_index(): array
{
    static $index = null;
    if ($index !== null) {
        return $index;
    }
    $index = ['variants' => [], 'names' => [], 'series' => []];
    $path = dirname(__DIR__) . '/data/catalog.json';
    $decoded = is_readable($path) ? json_decode((string)file_get_contents($path), true) : null;
    foreach ((array)($decoded['series'] ?? []) as $series) {
        $sid = (string)($series['id'] ?? '');
        if ($sid === '') {
            continue;
        }
        $name = trim((string)($series['name'] ?? ''));
        $index['series'][$sid] = ['id' => $sid, 'name' => $name];
        $index['names'][strtolower($name)] = ['series' => $sid, 'variant' => ''];
        foreach ((array)($series['variants'] ?? []) as $variant) {
            $vid = (string)($variant['id'] ?? '');
            if ($vid === '') {
                continue;
            }
            $index['variants'][$vid] = $sid;
            $full = strtolower(trim($name . ' ' . (string)($variant['label'] ?? '')));
            $index['names'][$full] = ['series' => $sid, 'variant' => $vid];
        }
    }
    return $index;
}

/** Resolve [product_id, variant_id] for an order_items row (new rows carry ids; legacy rows match by name). */
function crtlu_review_resolve_item(array $item): array
{
    $index = crtlu_review_catalog_index();
    $productId = trim((string)($item['product_id'] ?? ''));
    $variantId = trim((string)($item['variant_id'] ?? ''));
    if ($productId === '' && $variantId !== '' && isset($index['variants'][$variantId])) {
        $productId = $index['variants'][$variantId];
    }
    if ($productId === '') {
        $name = (string)($item['product_name'] ?? '');
        $name = strtolower(trim((string)preg_replace('/\s*\/\s*Plug:.*$/i', '', $name)));
        if (isset($index['names'][$name])) {
            $productId = $index['names'][$name]['series'];
            $variantId = $variantId ?: $index['names'][$name]['variant'];
        } else {
            // Longest series-name prefix match.
            $best = '';
            foreach ($index['series'] as $sid => $s) {
                $sn = strtolower($s['name']);
                if ($sn !== '' && str_starts_with($name, $sn) && strlen($sn) > strlen($best)) {
                    $best = $sn;
                    $productId = $sid;
                }
            }
        }
    }
    return [$productId, $variantId];
}

function crtlu_review_order(PDO $pdo, int $orderId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch();
    return $order ?: null;
}

function crtlu_review_order_is_eligible(array $order): bool
{
    return in_array((string)($order['status'] ?? ''), CRTLU_REVIEW_ELIGIBLE_STATUSES, true)
        && in_array((string)($order['payment_status'] ?? 'paid'), ['paid', 'no_payment_required', ''], true);
}

/** Order items with resolved product ids (only items mapped to a catalog product). */
function crtlu_review_order_items(PDO $pdo, int $orderId): array
{
    $stmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id ASC');
    $stmt->execute([':id' => $orderId]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        [$pid, $vid] = crtlu_review_resolve_item($row);
        if ($pid === '') {
            continue;
        }
        $row['product_id'] = $pid;
        $row['variant_id'] = $vid;
        $out[] = $row;
    }
    return $out;
}

/** Build a token for every reviewable item in an order (null if nothing reviewable / not configured). */
function crtlu_review_token_for_order(PDO $pdo, int $orderId): ?string
{
    try {
        $items = crtlu_review_order_items($pdo, $orderId);
        if (!$items) {
            return null;
        }
        return crtlu_review_token_create(
            $orderId,
            array_map(static fn(array $i): int => (int)$i['id'], $items),
            array_map(static fn(array $i): string => (string)$i['product_id'], $items)
        );
    } catch (Throwable $e) {
        return null;
    }
}

function crtlu_review_clean_text(string $value, int $max, bool $multiline = false): string
{
    $value = strip_tags($value);
    $value = $multiline
        ? (string)preg_replace('/[^\P{C}\n]+/u', '', str_replace("\r\n", "\n", $value))
        : (string)preg_replace('/\p{C}+/u', ' ', $value);
    $value = (string)preg_replace($multiline ? '/[ \t]+/u' : '/\s+/u', ' ', $value);
    if ($multiline) {
        $value = (string)preg_replace("/\n{3,}/", "\n\n", $value);
    }
    $value = trim($value);
    return mb_substr($value, 0, $max, 'UTF-8');
}

function crtlu_client_ip_hash(): string
{
    $ip = (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '');
    $ip = trim(explode(',', $ip)[0]);
    return hash_hmac('sha256', $ip, crtlu_review_secret());
}

/** Simple sliding-window limiter stored in MySQL. Returns true when allowed (and records the hit). */
function crtlu_review_rate_limit(PDO $pdo, string $bucket, int $max, int $windowSeconds): bool
{
    $bucket = substr($bucket, 0, 80);
    $since = date('Y-m-d H:i:s', time() - $windowSeconds);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM review_rate_limits WHERE bucket = :b AND created_at >= :s');
    $stmt->execute([':b' => $bucket, ':s' => $since]);
    if ((int)$stmt->fetchColumn() >= $max) {
        return false;
    }
    $pdo->prepare('INSERT INTO review_rate_limits (bucket) VALUES (:b)')->execute([':b' => $bucket]);
    if (random_int(1, 50) === 1) {
        $pdo->prepare('DELETE FROM review_rate_limits WHERE created_at < :old')
            ->execute([':old' => date('Y-m-d H:i:s', time() - 86400 * 2)]);
    }
    return true;
}

/** Public shape of an approved review (no order id, email, ip). */
function crtlu_review_public(array $row): array
{
    return [
        'id' => (int)$row['id'],
        'product_id' => (string)$row['product_id'],
        'rating' => (int)$row['rating'],
        'title' => (string)$row['title'],
        'body' => (string)$row['body'],
        'display_name' => (string)$row['display_name'] !== '' ? (string)$row['display_name'] : 'Verified buyer',
        'verified' => true,
        'date' => substr((string)($row['approved_at'] ?: $row['created_at']), 0, 10),
    ];
}

function crtlu_review_json(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
