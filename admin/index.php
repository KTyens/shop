<?php

require __DIR__ . '/auth.php';

crtlu_require_admin();

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table');
        $stmt->execute([':table' => $table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $error) {
        return false;
    }
}

function admin_count(PDO $pdo, string $sql): int
{
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (Throwable $error) {
        return 0;
    }
}

$dbError = '';
$stats = [
    'orders' => 0,
    'unshipped' => 0,
    'members' => 0,
    'pending_emails' => 0,
    'shipped' => 0,
];

try {
    $pdo = crtlu_pdo();
    if (admin_table_exists($pdo, 'orders')) {
        $stats['orders'] = admin_count($pdo, 'SELECT COUNT(*) FROM orders');
        $stats['unshipped'] = admin_count($pdo, "SELECT COUNT(*) FROM orders WHERE status IN ('paid', 'processing')");
        $stats['shipped'] = admin_count($pdo, "SELECT COUNT(*) FROM orders WHERE status = 'shipped'");
    }
    if (admin_table_exists($pdo, 'members')) {
        $stats['members'] = admin_count($pdo, 'SELECT COUNT(*) FROM members');
    }
    if (admin_table_exists($pdo, 'email_notifications')) {
        $stats['pending_emails'] = admin_count($pdo, "SELECT COUNT(*) FROM email_notifications WHERE status IN ('queued', 'failed')");
    }
} catch (Throwable $error) {
    $dbError = $error->getMessage();
}

$productCount = 0;
try {
    $catalogPath = dirname(__DIR__) . '/data/catalog.json';
    if (is_readable($catalogPath)) {
        $catalog = json_decode((string)file_get_contents($catalogPath), true);
        if (is_array($catalog) && isset($catalog['series']) && is_array($catalog['series'])) {
            $productCount = count($catalog['series']);
        }
    }
} catch (Throwable $error) {
    $productCount = 0;
}

$cards = [
    [
        'title' => '产品管理',
        'desc' => '手动上架新产品；编辑名称、描述、规格、价格；替换主图与详情图、排序删除图片。',
        'href' => 'products.php',
        'metric' => $productCount,
        'metric_label' => 'product series',
        'action' => 'Edit products',
        'primary' => true,
    ],
    [
        'title' => '上架新产品',
        'desc' => '填写型号信息与价格，可选上传主图/详情图，创建后进入编辑页完善。',
        'href' => 'product-new.php',
        'metric' => '+',
        'metric_label' => 'new listing',
        'action' => 'Create product',
    ],
    [
        'title' => '订单管理',
        'desc' => '查看 Stripe 订单、客户地址、商品明细，更新订单状态和燕文单号。',
        'href' => 'orders.php',
        'metric' => $stats['orders'],
        'metric_label' => 'total orders',
        'action' => 'Open orders',
    ],
    [
        'title' => '会员管理',
        'desc' => '查看已验证会员、登录状态、购买次数和累计消费。',
        'href' => 'members.php',
        'metric' => $stats['members'],
        'metric_label' => 'members',
        'action' => 'Open members',
    ],
    [
        'title' => '优惠券',
        'desc' => '新增、启用、停用或删除结账优惠码。',
        'href' => 'coupons.php',
        'metric' => 'JSON',
        'metric_label' => 'coupon rules',
        'action' => 'Edit coupons',
    ],
    [
        'title' => '邮件队列',
        'desc' => '查看订单通知、会员验证码邮件，重试失败邮件或标记已发送。',
        'href' => 'emails.php',
        'metric' => $stats['pending_emails'],
        'metric_label' => 'queued / failed',
        'action' => 'Open emails',
    ],
    [
        'title' => '燕文导出',
        'desc' => '下载待发货订单 CSV，用于导入或整理燕文发货资料。',
        'href' => 'export-yanwen.php',
        'metric' => $stats['unshipped'],
        'metric_label' => 'ready to export',
        'action' => 'Download CSV',
    ],
    [
        'title' => '快递追踪',
        'desc' => '快速进入已发货订单，检查或补充燕文追踪号。',
        'href' => 'orders.php?status=shipped',
        'metric' => $stats['shipped'],
        'metric_label' => 'shipped orders',
        'action' => 'Track shipped',
    ],
];
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CRTLU Admin Dashboard</title>
<style>
:root {
  color-scheme: dark;
  --bg: #071016;
  --panel: #0d171f;
  --panel-2: #101d26;
  --line: rgba(255,255,255,.13);
  --text: #f5fbff;
  --muted: #91a1ae;
  --green: #8bff85;
  --cyan: #5de7ff;
}
* { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: radial-gradient(circle at 80% 0, rgba(93,231,255,.12), transparent 30%), var(--bg); color: var(--text); }
main { width: min(1180px, calc(100% - 32px)); margin: 0 auto; padding: 34px 0 46px; }
a { color: inherit; }
.hero { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px; padding: 28px; border: 1px solid var(--line); background: linear-gradient(135deg, rgba(13,23,31,.94), rgba(13,23,31,.72)); }
.eyebrow { color: var(--green); font-size: 12px; font-weight: 900; letter-spacing: .12em; text-transform: uppercase; }
h1 { margin: 8px 0 10px; font-size: clamp(34px, 5vw, 62px); line-height: .95; }
.muted { color: var(--muted); }
.hero p { max-width: 680px; margin: 0; line-height: 1.65; color: #c8d7df; }
.quick { display: flex; gap: 10px; flex-wrap: wrap; }
.button, .card-link { min-height: 38px; display: inline-flex; align-items: center; justify-content: center; padding: 0 14px; border: 1px solid rgba(255,255,255,.18); background: var(--panel); text-decoration: none; font-weight: 900; text-transform: uppercase; font-size: 12px; letter-spacing: .04em; }
.button.primary, .card.primary .card-link { background: linear-gradient(90deg, #7cff8c, var(--cyan)); color: #001014; border: 0; }
.notice { margin-top: 16px; padding: 12px 14px; border: 1px solid rgba(255,107,107,.38); color: #ffb8b8; background: rgba(255,107,107,.08); }
.stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin: 18px 0; }
.stat { padding: 16px; border: 1px solid var(--line); background: rgba(13,23,31,.86); }
.stat strong { display: block; font-size: 30px; margin-bottom: 5px; }
.grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-top: 18px; }
.card { min-height: 245px; display: flex; flex-direction: column; justify-content: space-between; padding: 20px; border: 1px solid var(--line); background: linear-gradient(180deg, rgba(16,29,38,.94), rgba(10,18,24,.94)); }
.card:hover { border-color: rgba(93,231,255,.42); }
.card h2 { margin: 0; font-size: 24px; }
.card p { margin: 10px 0 18px; color: #c8d7df; line-height: 1.58; }
.metric { display: flex; justify-content: space-between; gap: 12px; align-items: flex-end; padding-top: 14px; border-top: 1px solid var(--line); }
.metric strong { font-size: 28px; }
.metric span { color: var(--muted); font-size: 12px; text-transform: uppercase; }
.footer { margin-top: 18px; color: var(--muted); font-size: 13px; line-height: 1.6; }
@media (max-width: 920px) {
  .hero { display: block; }
  .quick { margin-top: 18px; }
  .stats, .grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<main>
  <section class="hero">
    <div>
      <div class="eyebrow">CRTLU Admin</div>
      <h1>后台管理中心</h1>
      <p>统一进入产品、订单、会员、优惠券、邮件通知、燕文导出和快递追踪。以后只需要记住这个地址：<strong>/admin/</strong></p>
    </div>
    <nav class="quick" aria-label="Quick actions">
      <a class="button primary" href="products.php">产品</a>
      <a class="button" href="orders.php">订单</a>
      <a class="button" href="members.php">会员</a>
      <a class="button" href="coupons.php">优惠券</a>
      <a class="button" href="emails.php">邮件</a>
    </nav>
  </section>

  <?php if ($dbError): ?>
    <div class="notice">数据库暂时不可用：<?= h($dbError) ?>。入口仍可点击，但涉及订单和会员数据的页面可能需要先检查 config.local.php。</div>
  <?php endif; ?>

  <section class="stats" aria-label="Dashboard stats">
    <div class="stat"><strong><?= h($stats['orders']) ?></strong><span class="muted">订单总数</span></div>
    <div class="stat"><strong><?= h($stats['unshipped']) ?></strong><span class="muted">待发货</span></div>
    <div class="stat"><strong><?= h($stats['members']) ?></strong><span class="muted">会员</span></div>
    <div class="stat"><strong><?= h($stats['pending_emails']) ?></strong><span class="muted">待处理邮件</span></div>
  </section>

  <section class="grid" aria-label="Admin sections">
    <?php foreach ($cards as $card): ?>
      <article class="card<?= !empty($card['primary']) ? ' primary' : '' ?>">
        <div>
          <h2><?= h($card['title']) ?></h2>
          <p><?= h($card['desc']) ?></p>
        </div>
        <div>
          <div class="metric">
            <strong><?= h($card['metric']) ?></strong>
            <span><?= h($card['metric_label']) ?></span>
          </div>
          <p><a class="card-link" href="<?= h($card['href']) ?>"><?= h($card['action']) ?></a></p>
        </div>
      </article>
    <?php endforeach; ?>
  </section>

  <p class="footer">
    <strong>线上入口：</strong><a href="https://api.crtlu.me/admin/">https://api.crtlu.me/admin/</a><br>
    使用 HTTP Basic Auth（账号密码在 Serv00 的 <code>api/config.local.php</code>：<code>CRTLU_ADMIN_USER</code> / <code>CRTLU_ADMIN_PASS</code>）。<br>
    产品图预览走前台 <code>https://shop.crtlu.me</code>；在后台改图后若前台未变，需把 <code>public/assets/products</code> 与 catalog 同步到 Git/Cloudflare。
  </p>
</main>
</body>
</html>
