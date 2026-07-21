<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/admin-shell.php';
require_once __DIR__ . '/../api/notifications.php';

crtlu_require_admin();

$pdo = crtlu_pdo();
$members = [];
$hasMembers = crtlu_table_exists($pdo, 'members');
if ($hasMembers) {
    $stmt = $pdo->query(
        'SELECT m.*,
          COUNT(DISTINCT o.id) AS order_count,
          COALESCE(SUM(o.amount_total), 0) AS revenue_cents
        FROM members m
        LEFT JOIN orders o ON o.member_id = m.id OR o.customer_email = m.email
        GROUP BY m.id
        ORDER BY m.updated_at DESC
        LIMIT 300'
    );
    $members = $stmt->fetchAll();
}

crtlu_admin_header('会员管理', '已验证邮箱的客户账号、登录与累计消费。');
?>
  <?php if (!$hasMembers): ?>
    <p class="muted">未找到 members 表。请在数据库导入 <code>database/phase4-migration.sql</code> 与 <code>database/phase5-migration.sql</code>。</p>
  <?php endif; ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>邮箱</th>
          <th>姓名</th>
          <th>偏好</th>
          <th>订单 / 消费</th>
          <th>最近登录</th>
          <th>状态</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($members as $member): ?>
        <tr>
          <td><strong><?= htmlspecialchars((string)$member['email']) ?></strong><br><span class="muted">#<?= htmlspecialchars((string)$member['id']) ?></span></td>
          <td><?= htmlspecialchars((string)$member['name']) ?></td>
          <td><?= htmlspecialchars((string)$member['locale']) ?> / <?= htmlspecialchars((string)$member['currency']) ?></td>
          <td><?= htmlspecialchars((string)$member['order_count']) ?> 笔<br><span class="muted"><?= htmlspecialchars(crtlu_money((int)$member['revenue_cents'], 'usd')) ?></span></td>
          <td><?= htmlspecialchars((string)($member['last_login_at'] ?? '—')) ?></td>
          <td><?= htmlspecialchars(crtlu_admin_status_label((string)($member['status'] ?? ''))) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if ($hasMembers && !$members): ?>
        <tr><td colspan="6" class="muted">暂无会员记录。</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php crtlu_admin_footer(); ?>
