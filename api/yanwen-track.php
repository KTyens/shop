<?php

/**
 * Member-facing Yanwen tracking lookup.
 * POST JSON: { "order_id": 123 }  — must own the order and have yanwen_tracking.
 */

require __DIR__ . '/member-auth.php';
require_once __DIR__ . '/yanwen-client.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    crtlu_json(['ok' => false, 'message' => 'POST required.'], 405);
}

try {
    $pdo = crtlu_pdo();
} catch (Throwable $e) {
    crtlu_json(['ok' => false, 'message' => 'Database not configured.'], 500);
}

$member = crtlu_require_member($pdo);
$payload = crtlu_request_json();
$orderId = (int)($payload['order_id'] ?? 0);
if ($orderId < 1) {
    crtlu_json(['ok' => false, 'message' => 'Invalid order_id.'], 400);
}

$stmt = $pdo->prepare(
    'SELECT id, customer_email, member_id, yanwen_tracking, status
     FROM orders
     WHERE id = :id
       AND (member_id = :member_id OR customer_email = :email)
     LIMIT 1'
);
$stmt->execute([
    ':id' => $orderId,
    ':member_id' => (int)$member['id'],
    ':email' => (string)$member['email'],
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    crtlu_json(['ok' => false, 'message' => 'Order not found.'], 404);
}

$tracking = trim((string)($order['yanwen_tracking'] ?? ''));
if ($tracking === '') {
    crtlu_json([
        'ok' => false,
        'message' => 'Tracking number not available yet.',
        'order_id' => $orderId,
    ], 404);
}

$result = yanwen_track($tracking);
$checkpoints = [];
foreach (($result['checkpoints'] ?? []) as $cp) {
    if (!is_array($cp)) {
        continue;
    }
    $checkpoints[] = [
        'time' => (string)($cp['time_stamp'] ?? $cp['time'] ?? ''),
        'time_zone' => (string)($cp['time_zone'] ?? ''),
        'status' => (string)($cp['tracking_status'] ?? $cp['status'] ?? ''),
        'message' => (string)($cp['message'] ?? ''),
        'location' => (string)($cp['location'] ?? ''),
    ];
}

// Newest first for UI if timestamps are ascending
usort($checkpoints, static function (array $a, array $b): int {
    return strcmp((string)$b['time'], (string)$a['time']);
});

crtlu_json([
    'ok' => !empty($result['ok']),
    'message' => $result['ok'] ? 'OK' : (string)($result['error'] ?? 'Track failed'),
    'order_id' => $orderId,
    'tracking_number' => (string)($result['tracking_number'] ?? $tracking),
    'waybill_number' => (string)($result['waybill_number'] ?? $tracking),
    'exchange_number' => (string)($result['exchange_number'] ?? ''),
    'tracking_status' => (string)($result['tracking_status'] ?? ''),
    'checkpoints' => $checkpoints,
]);
