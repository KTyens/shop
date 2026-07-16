<?php

require __DIR__ . '/auth.php';

crtlu_require_admin();

$pdo = crtlu_pdo();
$allowedStatuses = ['paid', 'processing', 'shipped', 'delivered', 'refunded'];

function crtlu_admin_order_item_parts(string $name): array
{
    if (preg_match('/^(.*?)\s*\/\s*Plug:\s*(.+)$/i', trim($name), $matches)) {
        return [trim($matches[1]), trim($matches[2])];
    }

    return [trim($name), ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = in_array($_POST['status'] ?? '', $allowedStatuses, true) ? $_POST['status'] : 'paid';
    $stmt = $pdo->prepare('UPDATE orders SET status = :status, yanwen_tracking = :tracking WHERE id = :id');
    $stmt->execute([
        ':status' => $status,
        ':tracking' => trim((string)($_POST['yanwen_tracking'] ?? '')),
        ':id' => (int)($_POST['id'] ?? 0),
    ]);
    header('Location: orders.php?status=' . urlencode((string)($_GET['status'] ?? '')));
    exit;
}

$statusFilter = (string)($_GET['status'] ?? '');
$where = '';
$params = [];
if ($statusFilter !== '' && in_array($statusFilter, $allowedStatuses, true)) {
    $where = 'WHERE status = :status';
    $params[':status'] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT * FROM orders $where ORDER BY created_at DESC LIMIT 200");
$stmt->execute($params);
$orders = $stmt->fetchAll();

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

$summary = [
    'orders' => count($orders),
    'revenue' => array_sum(array_map(static fn(array $order): int => (int)$order['amount_total'], $orders)),
    'unshipped' => count(array_filter($orders, static fn(array $order): bool => in_array($order['status'], ['paid', 'processing'], true))),
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRTLU Orders</title>
<style>
body { margin: 0; font-family: system-ui, sans-serif; background: #071016; color: #f5fbff; }
main { max-width: 1280px; margin: 0 auto; padding: 28px; }
a { color: inherit; }
.topbar { display: flex; justify-content: space-between; gap: 18px; align-items: center; margin-bottom: 18px; }
.muted { color: #91a1ae; }
.cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 18px 0; }
.card { background: #0d171f; border: 1px solid rgba(255,255,255,.12); padding: 16px; }
.card strong { display: block; font-size: 26px; margin-top: 6px; }
.filters { display: flex; gap: 8px; flex-wrap: wrap; margin: 18px 0; }
.filters a, .button { display: inline-flex; align-items: center; min-height: 34px; padding: 0 12px; border: 1px solid rgba(255,255,255,.18); text-decoration: none; background: #0d171f; }
.filters a.active, .button.primary { background: #5de7ff; color: #001014; font-weight: 800; }
table { width: 100%; border-collapse: collapse; background: #0d171f; }
th, td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,.12); text-align: left; vertical-align: top; font-size: 14px; }
th { color: #8bff85; }
input, select, button { min-height: 34px; border: 1px solid rgba(255,255,255,.18); background: #071016; color: #fff; padding: 0 8px; }
button { background: #5de7ff; color: #001014; font-weight: 800; cursor: pointer; }
.items { margin: 8px 0 0; padding-left: 17px; color: #c9d8df; }
.items li { margin: 0 0 10px; }
.plug-pill { display: inline-flex; margin-top: 5px; padding: 4px 7px; border: 1px solid rgba(93,231,255,.32); background: rgba(93,231,255,.08); color: #8feeff; font-size: 12px; font-weight: 800; }
.status-pill { display: inline-flex; padding: 4px 8px; border: 1px solid rgba(139,255,133,.3); color: #8bff85; background: rgba(139,255,133,.08); }
.address { max-width: 260px; }
.order-actions { display: flex; gap: 8px; flex-wrap: wrap; }
@media (max-width: 900px) {
  .cards { grid-template-columns: 1fr; }
  table { min-width: 1100px; }
  .table-wrap { overflow-x: auto; }
}
</style>
</head>
<body>
<main>
  <div class="topbar">
    <div>
      <h1>CRTLU Orders</h1>
      <p class="muted">Paid Stripe orders. Update fulfillment status and export pending shipments for Yanwen.</p>
    </div>
    <div class="order-actions">
      <a class="button" href="index.php">Dashboard</a><a href="products.php">Products</a>
      <a class="button" href="members.php">Members</a>
      <a class="button" href="coupons.php">Coupons</a>
      <a class="button" href="emails.php">Emails</a>
      <a class="button primary" href="export-yanwen.php">Export Yanwen CSV</a>
    </div>
  </div>

  <div class="cards">
    <div class="card"><span class="muted">Current view orders</span><strong><?= htmlspecialchars((string)$summary['orders']) ?></strong></div>
    <div class="card"><span class="muted">Current view revenue</span><strong><?= htmlspecialchars(crtlu_money((int)$summary['revenue'], 'usd')) ?></strong></div>
    <div class="card"><span class="muted">Unshipped in view</span><strong><?= htmlspecialchars((string)$summary['unshipped']) ?></strong></div>
  </div>

  <nav class="filters" aria-label="Order filters">
    <a class="<?= $statusFilter === '' ? 'active' : '' ?>" href="orders.php">All</a>
    <?php foreach ($allowedStatuses as $status): ?>
      <a class="<?= $statusFilter === $status ? 'active' : '' ?>" href="orders.php?status=<?= urlencode($status) ?>"><?= htmlspecialchars($status) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Ship To</th>
          <th>Status</th>
          <th>Yanwen Tracking</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <?php $addressLines = crtlu_address_lines($order['shipping_address_json'] ?? null); ?>
        <tr>
          <td>
            #<?= htmlspecialchars((string)$order['id']) ?><br>
            <span class="muted"><?= htmlspecialchars($order['created_at']) ?></span><br>
            <span class="muted"><?= htmlspecialchars($order['stripe_session_id']) ?></span>
          </td>
          <td>
            <?= htmlspecialchars($order['customer_name']) ?><br>
            <span class="muted"><?= htmlspecialchars($order['customer_email']) ?></span><br>
            <span class="muted"><?= htmlspecialchars($order['phone']) ?></span>
          </td>
          <td>
            <ul class="items">
              <?php foreach (($itemsByOrder[(int)$order['id']] ?? []) as $item): ?>
                <?php [$itemName, $plugType] = crtlu_admin_order_item_parts((string)$item['product_name']); ?>
                <li>
                  <?= htmlspecialchars((string)$item['quantity']) ?> x <?= htmlspecialchars($itemName) ?>
                  <?php if ($plugType !== ''): ?><br><span class="plug-pill">Power adapter: <?= htmlspecialchars($plugType) ?></span><?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </td>
          <td>
            <?= htmlspecialchars(crtlu_money((int)$order['amount_total'], $order['currency'])) ?><br>
            <?php if (!empty($order['coupon_code'])): ?>
              <span class="muted">Coupon: <?= htmlspecialchars($order['coupon_code']) ?></span><br>
              <span class="muted">Discount: <?= htmlspecialchars(crtlu_money((int)($order['discount_total'] ?? 0), $order['currency'])) ?></span><br>
            <?php endif; ?>
            <?php if (!empty($order['display_currency']) || !empty($order['locale'])): ?>
              <span class="muted">Pref: <?= htmlspecialchars(trim(($order['display_currency'] ?? '') . ' ' . ($order['locale'] ?? ''))) ?></span>
            <?php endif; ?>
          </td>
          <td class="address">
            <strong><?= htmlspecialchars($order['shipping_name']) ?></strong><br>
            <?php foreach ($addressLines as $line): ?>
              <span class="muted"><?= htmlspecialchars($line) ?></span><br>
            <?php endforeach; ?>
          </td>
          <td>
            <span class="status-pill"><?= htmlspecialchars($order['status']) ?></span>
          </td>
          <td>
            <form method="post" class="order-actions">
              <input type="hidden" name="id" value="<?= htmlspecialchars((string)$order['id']) ?>">
              <select name="status">
                <?php foreach ($allowedStatuses as $status): ?>
                  <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                <?php endforeach; ?>
              </select>
              <input name="yanwen_tracking" value="<?= htmlspecialchars($order['yanwen_tracking'] ?? '') ?>" placeholder="Yanwen tracking">
              <button type="submit">Save</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?>
        <tr><td colspan="7" class="muted">No orders in this view.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
