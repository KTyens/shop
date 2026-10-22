<?php

/**
 * 评价审核（Basic Auth）。新评价默认 pending，审核通过后才会出现在前台 / JSON-LD。
 * 注意：前台产品页的评价在构建时写入静态 HTML —— 通过审核后需在本地
 * `npm run reviews:sync && npm run build` 并推送 main 才会更新 JSON-LD（列表会在客户端实时刷新）。
 */

require __DIR__ . '/auth.php';
require __DIR__ . '/admin-shell.php';
require_once __DIR__ . '/../api/notifications.php';
require_once __DIR__ . '/../api/reviews-lib.php';

crtlu_require_admin();

$pdo = crtlu_pdo();
$flash = '';
$flashError = false;
$hasTable = crtlu_table_exists($pdo, 'product_reviews');

// Lightweight CSRF token derived from admin credentials (Basic Auth has no session).
$csrf = hash_hmac('sha256', 'reviews-admin', (string)crtlu_config('CRTLU_ADMIN_PASS', '') . '|' . (string)crtlu_config('CRTLU_REVIEW_SECRET', ''));

if ($hasTable && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('CSRF 校验失败');
    }
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'approve') {
        $pdo->prepare("UPDATE product_reviews SET status = 'approved', approved_at = CURRENT_TIMESTAMP WHERE id = :id")->execute([':id' => $id]);
        $flash = '已通过 #' . $id;
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE product_reviews SET status = 'rejected', approved_at = NULL WHERE id = :id")->execute([':id' => $id]);
        $flash = '已拒绝 #' . $id;
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM product_reviews WHERE id = :id')->execute([':id' => $id]);
        $flash = '已删除 #' . $id . '（该订单商品可重新提交评价）';
    }
    header('Location: reviews.php?status=' . urlencode((string)($_GET['status'] ?? 'pending')) . '&msg=' . rawurlencode($flash));
    exit;
}
if (isset($_GET['msg'])) {
    $flash = (string)$_GET['msg'];
}

// Generate a review link for an order manually (e.g. to paste into a support reply).
$linkOrderId = (int)($_GET['link_order'] ?? 0);
$generatedLink = '';
if ($linkOrderId > 0) {
    try {
        $order = crtlu_review_order($pdo, $linkOrderId);
        if (!$order || !crtlu_review_order_is_eligible($order)) {
            $flash = '订单 #' . $linkOrderId . ' 不存在或状态不可评价（需 paid/processing/shipped/delivered）。';
            $flashError = true;
        } else {
            $token = crtlu_review_token_for_order($pdo, $linkOrderId);
            if ($token === null) {
                $flash = '无法生成：订单商品无法匹配到产品，或未配置 CRTLU_REVIEW_SECRET（≥32 字符）。';
                $flashError = true;
            } else {
                $generatedLink = crtlu_review_link($token);
            }
        }
    } catch (Throwable $e) {
        $flash = '生成失败：' . $e->getMessage();
        $flashError = true;
    }
}

$statuses = ['pending', 'approved', 'rejected'];
$status = in_array($_GET['status'] ?? 'pending', $statuses, true) ? (string)($_GET['status'] ?? 'pending') : 'pending';
$rows = [];
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
if ($hasTable) {
    foreach ($pdo->query('SELECT status, COUNT(*) c FROM product_reviews GROUP BY status')->fetchAll() as $r) {
        $counts[(string)$r['status']] = (int)$r['c'];
    }
    $stmt = $pdo->prepare('SELECT r.*, o.customer_email FROM product_reviews r LEFT JOIN orders o ON o.id = r.order_id WHERE r.status = :s ORDER BY r.created_at DESC LIMIT 300');
    $stmt->execute([':s' => $status]);
    $rows = $stmt->fetchAll();
}
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

crtlu_admin_header('评价审核', '仅真实买家（签名链接 + 已付款订单）可提交；默认待审核。通过后需重新构建前台以更新静态 JSON-LD。');
?>
<?php if ($flash !== ''): ?>
  <p class="<?= $flashError ? 'danger' : '' ?>" style="padding:10px 12px;border:1px solid var(--line);background:var(--panel);<?= $flashError ? 'color:var(--danger);' : '' ?>"><?= $h($flash) ?></p>
<?php endif; ?>
<?php if (!$hasTable): ?>
  <p style="color:var(--danger)">缺少 <code>product_reviews</code> 表：请先在 MySQL 执行 <code>database/phase6-reviews-migration.sql</code>。</p>
<?php else: ?>
  <form method="get" style="display:flex;gap:8px;align-items:center;margin:12px 0;">
    <label class="muted">为订单生成评价链接：</label>
    <input name="link_order" type="number" min="1" placeholder="订单号" value="<?= $linkOrderId ?: '' ?>" style="min-height:34px;background:var(--panel);color:var(--text);border:1px solid var(--line);padding:0 10px;">
    <button class="button" type="submit">生成</button>
  </form>
  <?php if ($generatedLink !== ''): ?>
    <p><input readonly value="<?= $h($generatedLink) ?>" style="width:100%;min-height:34px;background:var(--panel);color:var(--cyan);border:1px solid var(--line);padding:0 10px;" onclick="this.select()"></p>
  <?php endif; ?>
  <div class="filters">
    <?php foreach ($statuses as $s): ?>
      <a class="<?= $s === $status ? 'active' : '' ?>" href="reviews.php?status=<?= $s ?>"><?= $h(crtlu_admin_status_label($s)) ?> (<?= $counts[$s] ?? 0 ?>)</a>
    <?php endforeach; ?>
  </div>
  <?php if (!$rows): ?>
    <p class="muted">暂无评价。</p>
  <?php endif; ?>
  <?php foreach ($rows as $r): ?>
    <div class="card" style="margin-bottom:12px;">
      <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
          <strong style="font-size:18px;display:inline;"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></strong>
          <span style="margin-left:8px;font-weight:800;"><?= $h($r['title']) ?></span>
          <div class="muted" style="font-size:12px;margin-top:4px;">
            #<?= (int)$r['id'] ?> · 产品 <code><?= $h($r['product_id']) ?></code> <?= $r['variant_id'] !== '' ? '/ <code>' . $h($r['variant_id']) . '</code>' : '' ?>
            · 订单 #<?= (int)$r['order_id'] ?> (<?= $h($r['customer_email'] ?? '') ?>) · 署名「<?= $h($r['display_name'] ?: 'Verified buyer') ?>」 · <?= $h($r['created_at']) ?>
          </div>
        </div>
        <form method="post" style="display:flex;gap:6px;">
          <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <?php if ($r['status'] !== 'approved'): ?><button class="button primary" name="action" value="approve">通过</button><?php endif; ?>
          <?php if ($r['status'] !== 'rejected'): ?><button class="button" name="action" value="reject">拒绝</button><?php endif; ?>
          <button class="button danger" name="action" value="delete" onclick="return confirm('删除后该商品可重新评价，确定？')">删除</button>
        </form>
      </div>
      <p style="white-space:pre-wrap;line-height:1.6;margin:10px 0 0;"><?= $h($r['body']) ?></p>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php crtlu_admin_footer(); ?>
