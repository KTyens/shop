<?php

/**
 * Public approved reviews.
 *   GET /api/reviews.php?product=SERIES_ID  → {product_id, count, average, reviews[]}
 *   GET /api/reviews.php                    → {products: {SERIES_ID: {count, average, reviews[]}}} (used at build time)
 * Only status = 'approved' rows are ever returned. No order/email/IP data is exposed.
 */

require __DIR__ . '/config.php';
require_once __DIR__ . '/reviews-lib.php';

header('Cache-Control: public, max-age=300');

$product = trim((string)($_GET['product'] ?? ''));
if ($product !== '' && !preg_match('/^[a-z0-9][a-z0-9._-]{0,119}$/i', $product)) {
    crtlu_review_json(['error' => 'Invalid product id.'], 400);
}

function crtlu_review_summary(array $rows): array
{
    $count = count($rows);
    $sum = array_sum(array_map(static fn(array $r): int => (int)$r['rating'], $rows));
    return [
        'count' => $count,
        'average' => $count ? round($sum / $count, 2) : null,
        'reviews' => array_map('crtlu_review_public', $rows),
    ];
}

try {
    $pdo = crtlu_pdo();
    if ($product !== '') {
        $stmt = $pdo->prepare("SELECT * FROM product_reviews WHERE status = 'approved' AND product_id = :p ORDER BY approved_at DESC, id DESC LIMIT 200");
        $stmt->execute([':p' => $product]);
        crtlu_review_json(['product_id' => $product] + crtlu_review_summary($stmt->fetchAll()));
    }
    $rows = $pdo->query("SELECT * FROM product_reviews WHERE status = 'approved' ORDER BY approved_at DESC, id DESC LIMIT 5000")->fetchAll();
    $grouped = [];
    foreach ($rows as $row) {
        $grouped[(string)$row['product_id']][] = $row;
    }
    $out = [];
    foreach ($grouped as $pid => $list) {
        $out[$pid] = crtlu_review_summary($list);
    }
    crtlu_review_json(['generated_at' => gmdate('c'), 'products' => (object)$out]);
} catch (Throwable $e) {
    crtlu_review_json(['error' => 'Reviews are temporarily unavailable.'], 503);
}
