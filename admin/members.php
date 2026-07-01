<?php

require __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/notifications.php';

crtlu_require_admin();

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

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
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Members | CRTL U Admin</title>
<style>
body { margin: 0; font-family: system-ui, sans-serif; background: #071016; color: #f5fbff; }
main { max-width: 1180px; margin: 0 auto; padding: 28px; }
.topbar, .links { display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap; }
.links a { min-height: 34px; display: inline-flex; align-items: center; border: 1px solid rgba(255,255,255,.18); background: #0d171f; color: #fff; padding: 0 12px; text-decoration: none; font-weight: 800; }
table { width: 100%; border-collapse: collapse; background: #0d171f; border: 1px solid rgba(255,255,255,.12); margin-top: 18px; }
th, td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,.12); text-align: left; font-size: 14px; }
th { color: #8bff85; }
.muted { color: #91a1ae; }
</style>
</head>
<body>
<main>
  <div class="topbar">
    <div><h1>Members</h1><p class="muted">Verified customer accounts and repeat purchase context.</p></div>
    <nav class="links"><a href="orders.php">Orders</a><a href="coupons.php">Coupons</a><a href="emails.php">Emails</a></nav>
  </div>
  <?php if (!$hasMembers): ?>
    <p class="muted">Members table not found. Import database/phase4-migration.sql and database/phase5-migration.sql.</p>
  <?php endif; ?>
  <table>
    <thead><tr><th>Email</th><th>Name</th><th>Preference</th><th>Orders</th><th>Last login</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($members as $member): ?>
      <tr>
        <td><strong><?= h($member['email']) ?></strong><br><span class="muted">#<?= h($member['id']) ?></span></td>
        <td><?= h($member['name']) ?></td>
        <td><?= h($member['locale']) ?> / <?= h($member['currency']) ?></td>
        <td><?= h($member['order_count']) ?> orders<br><span class="muted"><?= h(crtlu_money((int)$member['revenue_cents'], 'usd')) ?></span></td>
        <td><?= h($member['last_login_at'] ?? '') ?></td>
        <td><?= h($member['status']) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
</body>
</html>
