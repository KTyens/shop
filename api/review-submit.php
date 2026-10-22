<?php

/**
 * Verified-buyer review submission.
 *   GET  /api/review-submit.php?t=TOKEN   → reviewable items for the signed order link
 *   POST /api/review-submit.php           → JSON {t, order_item_id, rating, title, body, display_name, website}
 * New reviews are stored with status "pending" until approved in admin/reviews.php.
 */

require __DIR__ . '/config.php';
require_once __DIR__ . '/reviews-lib.php';

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Cache-Control: no-store');

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

try {
    $pdo = crtlu_pdo();
    $ipHash = crtlu_client_ip_hash();
} catch (Throwable $e) {
    crtlu_review_json(['error' => 'Reviews are temporarily unavailable.'], 503);
}

if ($method === 'GET') {
    if (!crtlu_review_rate_limit($pdo, 'get:' . substr($ipHash, 0, 32), 60, 3600)) {
        crtlu_review_json(['error' => 'Too many requests. Please try again later.'], 429);
    }
    $token = (string)($_GET['t'] ?? '');
} elseif ($method === 'POST') {
    if (!crtlu_review_rate_limit($pdo, 'post:' . substr($ipHash, 0, 32), 10, 3600)) {
        crtlu_review_json(['error' => 'Too many submissions. Please try again later.'], 429);
    }
    $raw = (string)file_get_contents('php://input', false, null, 0, 20000);
    $input = json_decode($raw, true);
    if (!is_array($input)) {
        crtlu_review_json(['error' => 'Invalid request.'], 400);
    }
    $token = (string)($input['t'] ?? '');
} else {
    crtlu_review_json(['error' => 'Method not allowed.'], 405);
}

$claims = crtlu_review_token_verify($token);
if (!$claims) {
    crtlu_review_json(['error' => 'This review link is invalid or has expired.'], 403);
}

$orderId = (int)$claims['o'];
$order = crtlu_review_order($pdo, $orderId);
if (!$order || !crtlu_review_order_is_eligible($order)) {
    crtlu_review_json(['error' => 'This order is not eligible for reviews.'], 403);
}

// Items must belong to the order AND be listed in the signed token.
$allowedItemIds = array_flip($claims['i']);
$items = array_values(array_filter(
    crtlu_review_order_items($pdo, $orderId),
    static fn(array $item): bool => isset($allowedItemIds[(int)$item['id']])
));
$reviewedStmt = $pdo->prepare('SELECT order_item_id, status FROM product_reviews WHERE order_id = :o');
$reviewedStmt->execute([':o' => $orderId]);
$reviewed = [];
foreach ($reviewedStmt->fetchAll() as $row) {
    $reviewed[(int)$row['order_item_id']] = (string)$row['status'];
}

$index = crtlu_review_catalog_index();

if ($method === 'GET') {
    crtlu_review_json([
        'ok' => true,
        'order_id' => $orderId,
        'items' => array_map(static function (array $item) use ($reviewed, $index): array {
            $pid = (string)$item['product_id'];
            return [
                'order_item_id' => (int)$item['id'],
                'product_id' => $pid,
                'product_name' => $index['series'][$pid]['name'] ?? (string)$item['product_name'],
                'item_label' => trim((string)preg_replace('/\s*\/\s*Plug:.*$/i', '', (string)$item['product_name'])),
                'reviewed' => isset($reviewed[(int)$item['id']]),
            ];
        }, $items),
    ]);
}

// ---- POST ----
if (trim((string)($input['website'] ?? '')) !== '') {
    // Honeypot filled → silently accept without storing.
    crtlu_review_json(['ok' => true, 'status' => 'pending']);
}
if (!crtlu_review_rate_limit($pdo, 'order:' . $orderId, 20, 86400)) {
    crtlu_review_json(['error' => 'Too many submissions for this order.'], 429);
}

$orderItemId = (int)($input['order_item_id'] ?? 0);
$target = null;
foreach ($items as $item) {
    if ((int)$item['id'] === $orderItemId) {
        $target = $item;
        break;
    }
}
if (!$target) {
    crtlu_review_json(['error' => 'This item is not part of your order.'], 403);
}
if (isset($reviewed[$orderItemId])) {
    crtlu_review_json(['error' => 'You have already reviewed this item. Thank you!'], 409);
}

$rating = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
if ($rating === false || $rating === null) {
    crtlu_review_json(['error' => 'Please choose a rating from 1 to 5 stars.'], 422);
}
$title = crtlu_review_clean_text((string)($input['title'] ?? ''), 120);
$body = crtlu_review_clean_text((string)($input['body'] ?? ''), 4000, true);
$displayName = crtlu_review_clean_text((string)($input['display_name'] ?? ''), 60);
if (mb_strlen($body, 'UTF-8') < 10) {
    crtlu_review_json(['error' => 'Please write at least 10 characters about the product.'], 422);
}
if (preg_match('#https?://|www\.#i', $title . ' ' . $body . ' ' . $displayName)) {
    crtlu_review_json(['error' => 'Links are not allowed in reviews.'], 422);
}

try {
    $stmt = $pdo->prepare(
        'INSERT INTO product_reviews (order_id, order_item_id, product_id, variant_id, rating, title, body, display_name, status, ip_hash)
         VALUES (:o, :oi, :p, :v, :r, :t, :b, :d, :s, :ip)'
    );
    $stmt->execute([
        ':o' => $orderId,
        ':oi' => $orderItemId,
        ':p' => (string)$target['product_id'],
        ':v' => (string)$target['variant_id'],
        ':r' => (int)$rating,
        ':t' => $title,
        ':b' => $body,
        ':d' => $displayName,
        ':s' => 'pending',
        ':ip' => $ipHash,
    ]);
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23000') {
        crtlu_review_json(['error' => 'You have already reviewed this item. Thank you!'], 409);
    }
    crtlu_review_json(['error' => 'Could not save your review. Please try again later.'], 500);
}

crtlu_review_json(['ok' => true, 'status' => 'pending']);
