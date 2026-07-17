<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/admin-shell.php';
require_once __DIR__ . '/../api/yanwen-client.php';

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

$flash = null;
$flashError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'save');
    $orderId = (int)($_POST['id'] ?? 0);
    $statusFilter = (string)($_GET['status'] ?? '');
    $redirect = 'orders.php' . ($statusFilter !== '' ? '?status=' . urlencode($statusFilter) : '');

    if ($action === 'create_yanwen') {
        $force = !empty($_POST['force_yanwen']);
        $result = yanwen_fulfill_shop_order($pdo, $orderId, $force);
        $q = $statusFilter !== '' ? '&status=' . urlencode($statusFilter) : '';
        if (!empty($result['ok'])) {
            header('Location: orders.php?yanwen=ok&msg=' . rawurlencode((string)$result['message']) . $q);
        } else {
            header('Location: orders.php?yanwen=err&msg=' . rawurlencode((string)$result['message']) . $q);
        }
        exit;
    }

    $status = in_array($_POST['status'] ?? '', $allowedStatuses, true) ? $_POST['status'] : 'paid';
    $stmt = $pdo->prepare('UPDATE orders SET status = :status, yanwen_tracking = :tracking WHERE id = :id');
    $stmt->execute([
        ':status' => $status,
        ':tracking' => trim((string)($_POST['yanwen_tracking'] ?? '')),
        ':id' => $orderId,
    ]);
    header('Location: ' . $redirect);
    exit;
}

if (isset($_GET['yanwen'], $_GET['msg'])) {
    $flash = (string)$_GET['msg'];
    $flashError = (string)$_GET['yanwen'] === 'err';
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

$yanwenReady = yanwen_is_configured() && trim((string)yanwen_config('YANWEN_CHANNEL_ID', '')) !== '';

crtlu_admin_header('订单管理', '查看 Stripe 订单、一键创建燕文运单、更新履约状态。');
?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
    <a class="button primary" href="export-yanwen.php">导出燕文 CSV</a>
    <a class="button" href="yanwen.php">燕文 API 对接</a>
  </div>

  <?php if ($flash !== null): ?>
    <p class="message<?= $flashError ? ' error' : '' ?>" style="margin:0 0 14px;"><?= htmlspecialchars($flash) ?></p>
  <?php endif; ?>

  <?php if (!yanwen_is_configured()): ?>
    <p class="message error" style="margin:0 0 14px;">燕文密钥未配置：请在 <code>api/config.local.php</code> 填写后，到「燕文 API」页测试连通。</p>
  <?php elseif (!$yanwenReady): ?>
    <p class="message error" style="margin:0 0 14px;">
      已配置密钥，但未设置 <code>YANWEN_CHANNEL_ID</code>。请打开
      <a href="yanwen.php?action=channels">拉取已开通产品</a>，把产品 id 写入 config 后再一键创建运单。
    </p>
  <?php endif; ?>

  <div class="cards">
    <div class="card"><span class="muted">当前列表订单数</span><strong><?= htmlspecialchars((string)$summary['orders']) ?></strong></div>
    <div class="card"><span class="muted">当前列表金额</span><strong><?= htmlspecialchars(crtlu_money((int)$summary['revenue'], 'usd')) ?></strong></div>
    <div class="card"><span class="muted">待发货（本页）</span><strong><?= htmlspecialchars((string)$summary['unshipped']) ?></strong></div>
  </div>

  <nav class="filters" aria-label="订单筛选">
    <a class="<?= $statusFilter === '' ? 'active' : '' ?>" href="orders.php">全部</a>
    <?php foreach ($allowedStatuses as $status): ?>
      <a class="<?= $statusFilter === $status ? 'active' : '' ?>" href="orders.php?status=<?= urlencode($status) ?>"><?= htmlspecialchars(crtlu_admin_status_label($status)) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>订单</th>
          <th>客户</th>
          <th>商品</th>
          <th>金额</th>
          <th>收件地址</th>
          <th>状态</th>
          <th>燕文运单 / 更新</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $order): ?>
        <?php
          $addressLines = crtlu_address_lines($order['shipping_address_json'] ?? null);
          $hasTracking = trim((string)($order['yanwen_tracking'] ?? '')) !== '';
          $canCreate = in_array((string)$order['status'], ['paid', 'processing', 'shipped'], true);
        ?>
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
            <ul style="margin:8px 0 0;padding-left:17px;color:#c9d8df;">
              <?php foreach (($itemsByOrder[(int)$order['id']] ?? []) as $item): ?>
                <?php [$itemName, $plugType] = crtlu_admin_order_item_parts((string)$item['product_name']); ?>
                <li style="margin:0 0 10px;">
                  <?= htmlspecialchars((string)$item['quantity']) ?> × <?= htmlspecialchars($itemName) ?>
                  <?php if ($plugType !== ''): ?><br><span class="plug-pill">电源插头：<?= htmlspecialchars($plugType) ?></span><?php endif; ?>
                </li>
              <?php endforeach; ?>
            </ul>
          </td>
          <td>
            <?= htmlspecialchars(crtlu_money((int)$order['amount_total'], $order['currency'])) ?><br>
            <?php if (!empty($order['coupon_code'])): ?>
              <span class="muted">优惠码：<?= htmlspecialchars($order['coupon_code']) ?></span><br>
              <span class="muted">优惠：<?= htmlspecialchars(crtlu_money((int)($order['discount_total'] ?? 0), $order['currency'])) ?></span><br>
            <?php endif; ?>
            <?php if (!empty($order['display_currency']) || !empty($order['locale'])): ?>
              <span class="muted">偏好：<?= htmlspecialchars(trim(($order['display_currency'] ?? '') . ' ' . ($order['locale'] ?? ''))) ?></span>
            <?php endif; ?>
          </td>
          <td style="max-width:260px;">
            <strong><?= htmlspecialchars($order['shipping_name']) ?></strong><br>
            <?php foreach ($addressLines as $line): ?>
              <span class="muted"><?= htmlspecialchars($line) ?></span><br>
            <?php endforeach; ?>
          </td>
          <td>
            <span class="status-pill"><?= htmlspecialchars(crtlu_admin_status_label((string)$order['status'])) ?></span>
          </td>
          <td>
            <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
              <input type="hidden" name="action" value="save">
              <input type="hidden" name="id" value="<?= htmlspecialchars((string)$order['id']) ?>">
              <select name="status" aria-label="订单状态">
                <?php foreach ($allowedStatuses as $status): ?>
                  <option value="<?= $status ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars(crtlu_admin_status_label($status)) ?></option>
                <?php endforeach; ?>
              </select>
              <input name="yanwen_tracking" value="<?= htmlspecialchars($order['yanwen_tracking'] ?? '') ?>" placeholder="燕文运单号">
              <button class="button primary" type="submit">保存</button>
            </form>
            <?php if ($canCreate): ?>
            <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:8px;" onsubmit="return confirm('确认向燕文创建运单？成功后将写回运单号<?= $hasTracking ? '（当前已有号，需勾选强制才会重建）' : '' ?>。');">
              <input type="hidden" name="action" value="create_yanwen">
              <input type="hidden" name="id" value="<?= htmlspecialchars((string)$order['id']) ?>">
              <button class="button" type="submit" <?= $yanwenReady ? '' : 'disabled title="请先配置 YANWEN_CHANNEL_ID"' ?>>
                创建燕文运单
              </button>
              <?php if ($hasTracking): ?>
                <label class="muted" style="font-size:12px;display:inline-flex;gap:4px;align-items:center;">
                  <input type="checkbox" name="force_yanwen" value="1"> 强制重建
                </label>
              <?php endif; ?>
            </form>
            <?php endif; ?>
            <?php if ($hasTracking && yanwen_is_configured()): ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
              <a class="button" href="yanwen-label.php?order_id=<?= (int)$order['id'] ?>" target="_blank" rel="noopener">
                打印标签 PDF
              </a>
              <a class="button" href="yanwen-label.php?order_id=<?= (int)$order['id'] ?>&amp;print_remark=1" target="_blank" rel="noopener" title="含拣货单信息">
                标签+拣货单
              </a>
            </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$orders): ?>
        <tr><td colspan="7" class="muted">当前筛选下没有订单。</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php crtlu_admin_footer(); ?>
