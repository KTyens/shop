<?php

require __DIR__ . '/auth.php';

crtlu_require_admin();

$pdo = crtlu_pdo();
$orders = $pdo->query(
    "SELECT * FROM orders
     WHERE status IN ('paid', 'processing')
     ORDER BY created_at ASC
     LIMIT 500"
)->fetchAll();

$orderIds = array_map(static fn(array $order): int => (int)$order['id'], $orders);
$itemsByOrder = [];
if ($orderIds) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemsStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY id ASC");
    $itemsStmt->execute($orderIds);
    foreach ($itemsStmt->fetchAll() as $item) {
        $itemsByOrder[(int)$item['order_id']][] = $item;
    }
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="yanwen-orders-' . date('Ymd-His') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, [
    'order_id',
    'customer_name',
    'email',
    'phone',
    'country',
    'state',
    'city',
    'postal_code',
    'address_line1',
    'address_line2',
    'items',
    'amount',
    'currency',
    'status',
]);

foreach ($orders as $order) {
    $address = json_decode((string)($order['shipping_address_json'] ?? ''), true);
    if (!is_array($address)) {
        $address = [];
    }

    $items = array_map(static function (array $item): string {
        return $item['quantity'] . ' x ' . $item['product_name'];
    }, $itemsByOrder[(int)$order['id']] ?? []);

    fputcsv($out, [
        $order['id'],
        $order['shipping_name'] ?: $order['customer_name'],
        $order['customer_email'],
        $order['phone'],
        $address['country'] ?? '',
        $address['state'] ?? '',
        $address['city'] ?? '',
        $address['postal_code'] ?? '',
        $address['line1'] ?? '',
        $address['line2'] ?? '',
        implode('; ', $items),
        number_format(((int)$order['amount_total']) / 100, 2, '.', ''),
        strtoupper($order['currency']),
        $order['status'],
    ]);
}

fclose($out);
