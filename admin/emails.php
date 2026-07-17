<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/admin-shell.php';
require_once __DIR__ . '/../api/notifications.php';

crtlu_require_admin();

$pdo = crtlu_pdo();
$message = '';
$messageError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && crtlu_table_exists($pdo, 'email_notifications')) {
    $id = (int)($_POST['id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');
    $stmt = $pdo->prepare('SELECT * FROM email_notifications WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $id]);
    $email = $stmt->fetch();
    if ($email && $action === 'mark_sent') {
        $pdo->prepare('UPDATE email_notifications SET status = :status, sent_at = CURRENT_TIMESTAMP, last_error = NULL WHERE id = :id')->execute([':status' => 'sent', ':id' => $id]);
        $message = '已标记为已发送。';
    } elseif ($email && $action === 'retry') {
        $send = crtlu_send_mail((string)$email['to_email'], (string)$email['subject'], (string)$email['body']);
        $pdo->prepare('UPDATE email_notifications SET status = :status, sent_at = :sent_at, last_error = :last_error WHERE id = :id')->execute([
            ':status' => $send['ok'] ? 'sent' : 'failed',
            ':sent_at' => $send['ok'] ? date('Y-m-d H:i:s') : null,
            ':last_error' => $send['ok'] ? null : (($send['via'] ?? '') . ': ' . ($send['error'] ?? 'send failed')),
            ':id' => $id,
        ]);
        $message = $send['ok']
            ? ('邮件已发送（via ' . ($send['via'] ?? '?') . '）。')
            : ('发送失败：' . ($send['error'] ?? 'unknown'));
        $messageError = !$send['ok'];
    }
}

$emails = [];
$hasEmails = crtlu_table_exists($pdo, 'email_notifications');
if ($hasEmails) {
    $stmt = $pdo->query('SELECT * FROM email_notifications ORDER BY created_at DESC LIMIT 300');
    $emails = $stmt->fetchAll();
}

crtlu_admin_header('邮件队列', '订单通知、会员验证码等排队邮件；可重试或标记已发送。');
?>
  <?php if ($message): ?><p class="message<?= $messageError ? ' error' : '' ?>"><?= htmlspecialchars($message) ?></p><?php endif; ?>
  <?php if (!$hasEmails): ?><p class="muted">未找到邮件表。请导入 <code>database/phase4-migration.sql</code>。</p><?php endif; ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>收件人</th>
          <th>模板</th>
          <th>主题 / 正文</th>
          <th>状态</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($emails as $email): ?>
        <tr>
          <td>#<?= htmlspecialchars((string)$email['id']) ?><br><span class="muted"><?= htmlspecialchars((string)$email['created_at']) ?></span></td>
          <td><?= htmlspecialchars((string)$email['to_email']) ?><br><span class="muted">订单 <?= htmlspecialchars((string)($email['order_id'] ?? '—')) ?></span></td>
          <td><?= htmlspecialchars((string)$email['template']) ?></td>
          <td><strong><?= htmlspecialchars((string)$email['subject']) ?></strong><pre style="white-space:pre-wrap;max-width:520px;color:#cbd8df;"><?= htmlspecialchars((string)$email['body']) ?></pre></td>
          <td><?= htmlspecialchars(crtlu_admin_status_label((string)$email['status'])) ?><br><span class="muted"><?= htmlspecialchars((string)($email['last_error'] ?? '')) ?></span></td>
          <td>
            <form method="post" style="display:flex;gap:8px;flex-wrap:wrap;">
              <input type="hidden" name="id" value="<?= htmlspecialchars((string)$email['id']) ?>">
              <button class="button primary" name="action" value="retry" type="submit">重试发送</button>
              <button class="button" name="action" value="mark_sent" type="submit">标记已发送</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if ($hasEmails && !$emails): ?>
        <tr><td colspan="6" class="muted">队列为空。</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
<?php crtlu_admin_footer(); ?>
