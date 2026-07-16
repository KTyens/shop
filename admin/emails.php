<?php

require __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/notifications.php';

crtlu_require_admin();

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$pdo = crtlu_pdo();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && crtlu_table_exists($pdo, 'email_notifications')) {
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM email_notifications WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $email = $stmt->fetch();
    if ($email && $action === 'mark_sent') {
        $pdo->prepare('UPDATE email_notifications SET status = :status, sent_at = CURRENT_TIMESTAMP, last_error = NULL WHERE id = :id')->execute([':status' => 'sent', ':id' => $id]);
        $message = 'Marked as sent.';
    } elseif ($email && $action === 'retry') {
        $from = crtlu_config('CRTLU_MAIL_FROM', '');
        if ($from !== '' && function_exists('mail')) {
            $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
            $sent = @mail((string)$email['to_email'], (string)$email['subject'], (string)$email['body'], $headers);
            $pdo->prepare('UPDATE email_notifications SET status = :status, sent_at = :sent_at, last_error = :last_error WHERE id = :id')->execute([
                ':status' => $sent ? 'sent' : 'failed',
                ':sent_at' => $sent ? date('Y-m-d H:i:s') : null,
                ':last_error' => $sent ? null : 'PHP mail() returned false.',
                ':id' => $id,
            ]);
            $message = $sent ? 'Email sent.' : 'Email failed.';
        } else {
            $pdo->prepare('UPDATE email_notifications SET status = :status, last_error = :last_error WHERE id = :id')->execute([
                ':status' => 'failed',
                ':last_error' => 'CRTLU_MAIL_FROM is not configured or mail() is unavailable.',
                ':id' => $id,
            ]);
            $message = 'Mail is not configured.';
        }
    }
}

$emails = [];
$hasEmails = crtlu_table_exists($pdo, 'email_notifications');
if ($hasEmails) {
    $stmt = $pdo->query('SELECT * FROM email_notifications ORDER BY created_at DESC LIMIT 300');
    $emails = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Email Queue | CRTL U Admin</title>
<style>
body { margin: 0; font-family: system-ui, sans-serif; background: #071016; color: #f5fbff; }
main { max-width: 1280px; margin: 0 auto; padding: 28px; }
.topbar, .links, .actions { display: flex; justify-content: space-between; gap: 10px; align-items: center; flex-wrap: wrap; }
.links a, button { min-height: 34px; border: 1px solid rgba(255,255,255,.18); background: #0d171f; color: #fff; padding: 0 12px; text-decoration: none; font-weight: 800; }
button.primary { background: #5de7ff; color: #001014; border: 0; }
table { width: 100%; border-collapse: collapse; background: #0d171f; border: 1px solid rgba(255,255,255,.12); margin-top: 18px; }
th, td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,.12); text-align: left; font-size: 14px; vertical-align: top; }
th { color: #8bff85; }
.muted { color: #91a1ae; }
pre { white-space: pre-wrap; max-width: 520px; color: #cbd8df; }
.message { color: #8bff85; }
</style>
</head>
<body>
<main>
  <div class="topbar">
    <div><h1>Email Queue</h1><p class="muted">Order confirmations and member login codes queued by the site.</p></div>
    <nav class="links"><a href="index.php">Dashboard</a><a href="products.php">Products</a><a href="orders.php">Orders</a><a href="members.php">Members</a><a href="coupons.php">Coupons</a></nav>
  </div>
  <?php if ($message): ?><p class="message"><?= h($message) ?></p><?php endif; ?>
  <?php if (!$hasEmails): ?><p class="muted">Email table not found. Import database/phase4-migration.sql.</p><?php endif; ?>
  <table>
    <thead><tr><th>ID</th><th>To</th><th>Template</th><th>Subject / Body</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($emails as $email): ?>
      <tr>
        <td>#<?= h($email['id']) ?><br><span class="muted"><?= h($email['created_at']) ?></span></td>
        <td><?= h($email['to_email']) ?><br><span class="muted">Order <?= h($email['order_id'] ?? '') ?></span></td>
        <td><?= h($email['template']) ?></td>
        <td><strong><?= h($email['subject']) ?></strong><pre><?= h($email['body']) ?></pre></td>
        <td><?= h($email['status']) ?><br><span class="muted"><?= h($email['last_error'] ?? '') ?></span></td>
        <td>
          <form method="post" class="actions">
            <input type="hidden" name="id" value="<?= h($email['id']) ?>">
            <button class="primary" name="action" value="retry" type="submit">Retry</button>
            <button name="action" value="mark_sent" type="submit">Mark sent</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
</body>
</html>
