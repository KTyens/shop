<?php

require_once __DIR__ . '/config.php';

function crtlu_table_exists(PDO $pdo, string $table): bool
{
    try {
        $stmt = $pdo->prepare('SHOW TABLES LIKE :table_name');
        $stmt->execute([':table_name' => $table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $error) {
        return false;
    }
}

function crtlu_columns(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
        return array_column($stmt->fetchAll(), 'Field');
    } catch (Throwable $error) {
        return [];
    }
}

function crtlu_upsert_member(PDO $pdo, array $customer, string $locale = 'en', string $currency = 'USD'): ?int
{
    if (!crtlu_table_exists($pdo, 'members')) {
        return null;
    }
    $email = strtolower(trim((string)($customer['email'] ?? '')));
    if ($email === '') {
        return null;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO members (email, name, locale, currency, status, created_at, updated_at)
        VALUES (:email, :name, :locale, :currency, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE name = VALUES(name), locale = VALUES(locale), currency = VALUES(currency), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        ':email' => $email,
        ':name' => (string)($customer['name'] ?? ''),
        ':locale' => $locale ?: 'en',
        ':currency' => strtoupper($currency ?: 'USD'),
        ':status' => 'active',
    ]);

    $lookup = $pdo->prepare('SELECT id FROM members WHERE email = :email LIMIT 1');
    $lookup->execute([':email' => $email]);
    $id = $lookup->fetchColumn();
    return $id ? (int)$id : null;
}

function crtlu_queue_email(PDO $pdo, ?int $orderId, string $toEmail, string $template, string $subject, string $body): void
{
    if (!crtlu_table_exists($pdo, 'email_notifications') || $toEmail === '') {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO email_notifications (order_id, to_email, template, subject, body, status)
        VALUES (:order_id, :to_email, :template, :subject, :body, :status)'
    );
    $stmt->execute([
        ':order_id' => $orderId,
        ':to_email' => $toEmail,
        ':template' => $template,
        ':subject' => $subject,
        ':body' => $body,
        ':status' => 'queued',
    ]);
}

function crtlu_notification_item_lines(array $item): array
{
    $quantity = (int)($item['quantity'] ?? 1);
    $name = trim((string)($item['product_name'] ?? 'Product'));
    $plugType = '';
    if (preg_match('/^(.*?)\s*\/\s*Plug:\s*(.+)$/i', $name, $matches)) {
        $name = trim($matches[1]);
        $plugType = trim($matches[2]);
    }

    $lines = ['- ' . $quantity . ' x ' . $name];
    if ($plugType !== '') {
        $lines[] = '  Power adapter: ' . $plugType;
    }
    return $lines;
}

function crtlu_send_order_email(PDO $pdo, int $orderId, array $order, array $items): void
{
    $to = trim((string)($order['customer_email'] ?? ''));
    if ($to === '') {
        return;
    }

    $subject = 'CRTLU Digital order #' . $orderId . ' confirmed';
    $lines = [
        'Thank you for your order.',
        '',
        'Order #' . $orderId,
        'Total: ' . strtoupper((string)($order['currency'] ?? 'usd')) . ' ' . number_format(((int)($order['amount_total'] ?? 0)) / 100, 2),
        '',
        'Items:',
    ];
    foreach ($items as $item) {
        array_push($lines, ...crtlu_notification_item_lines($item));
    }
    $lines[] = '';
    $lines[] = 'We will add Yanwen tracking after fulfillment.';
    $body = implode("\n", $lines);

    crtlu_queue_email($pdo, $orderId, $to, 'order_confirmation', $subject, $body);

    $from = crtlu_config('CRTLU_MAIL_FROM', '');
    if ($from !== '' && function_exists('mail')) {
        $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
        @mail($to, $subject, $body, $headers);
    }
}

function crtlu_order_alert_body(int $orderId, array $order, array $items): string
{
    $currency = strtoupper((string)($order['currency'] ?? 'usd'));
    $total = $currency . ' ' . number_format(((int)($order['amount_total'] ?? 0)) / 100, 2);
    $lines = [
        'New CRTL U Digital order',
        '',
        'Order #' . $orderId,
        'Total: ' . $total,
        'Customer: ' . trim((string)($order['customer_name'] ?? '')),
        'Email: ' . trim((string)($order['customer_email'] ?? '')),
    ];

    if (!empty($order['coupon_code'])) {
        $lines[] = 'Coupon: ' . (string)$order['coupon_code'];
    }

    $lines[] = '';
    $lines[] = 'Items:';
    foreach ($items as $item) {
        array_push($lines, ...crtlu_notification_item_lines($item));
    }

    $lines[] = '';
    $lines[] = 'Admin: ' . crtlu_base_url() . '/admin/orders.php';
    return implode("\n", $lines);
}

function crtlu_send_telegram_message(string $message): void
{
    $token = trim((string)crtlu_config('CRTLU_TELEGRAM_BOT_TOKEN', ''));
    $chatId = trim((string)crtlu_config('CRTLU_TELEGRAM_CHAT_ID', ''));
    if ($token === '' || $chatId === '' || !function_exists('curl_init')) {
        return;
    }

    $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_POSTFIELDS => http_build_query([
            'chat_id' => $chatId,
            'text' => $message,
            'disable_web_page_preview' => 'true',
        ]),
    ]);
    @curl_exec($ch);
    curl_close($ch);
}

function crtlu_send_admin_order_notifications(PDO $pdo, int $orderId, array $order, array $items): void
{
    $body = crtlu_order_alert_body($orderId, $order, $items);
    $subject = 'New CRTL U Digital order #' . $orderId;

    $notifyEmail = trim((string)crtlu_config('CRTLU_ORDER_NOTIFY_EMAIL', ''));
    if ($notifyEmail !== '') {
        crtlu_queue_email($pdo, $orderId, $notifyEmail, 'admin_order_alert', $subject, $body);

        $from = crtlu_config('CRTLU_MAIL_FROM', '');
        if ($from !== '' && function_exists('mail')) {
            $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
            @mail($notifyEmail, $subject, $body, $headers);
        }
    }

    crtlu_send_telegram_message($body);
}

function crtlu_send_member_email(PDO $pdo, string $to, string $template, string $subject, string $body): void
{
    $to = trim($to);
    if ($to === '') {
        return;
    }

    crtlu_queue_email($pdo, null, $to, $template, $subject, $body);

    $from = crtlu_config('CRTLU_MAIL_FROM', '');
    if ($from !== '' && function_exists('mail')) {
        $headers = 'From: ' . $from . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
        @mail($to, $subject, $body, $headers);
    }
}
