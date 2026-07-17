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

function crtlu_ensure_email_table(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    if (crtlu_table_exists($pdo, 'email_notifications')) {
        return;
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `email_notifications` (
          id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
          order_id BIGINT UNSIGNED NULL,
          to_email VARCHAR(255) NOT NULL,
          template VARCHAR(80) NOT NULL DEFAULT '',
          subject VARCHAR(255) NOT NULL DEFAULT '',
          body LONGTEXT NOT NULL,
          status VARCHAR(40) NOT NULL DEFAULT 'queued',
          last_error TEXT NULL,
          sent_at TIMESTAMP NULL DEFAULT NULL,
          created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_email_order_id (order_id),
          KEY idx_email_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

/**
 * Queue an email row; returns insert id or 0.
 */
function crtlu_queue_email(PDO $pdo, ?int $orderId, string $toEmail, string $template, string $subject, string $body, string $status = 'queued', ?string $lastError = null): int
{
    if ($toEmail === '') {
        return 0;
    }
    try {
        crtlu_ensure_email_table($pdo);
    } catch (Throwable $e) {
        return 0;
    }
    if (!crtlu_table_exists($pdo, 'email_notifications')) {
        return 0;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO email_notifications (order_id, to_email, template, subject, body, status, last_error, sent_at)
        VALUES (:order_id, :to_email, :template, :subject, :body, :status, :last_error, :sent_at)'
    );
    $sent = $status === 'sent' ? date('Y-m-d H:i:s') : null;
    $stmt->execute([
        ':order_id' => $orderId,
        ':to_email' => $toEmail,
        ':template' => $template,
        ':subject' => $subject,
        ':body' => $body,
        ':status' => $status,
        ':last_error' => $lastError,
        ':sent_at' => $sent,
    ]);
    return (int)$pdo->lastInsertId();
}

/**
 * Send email via Resend API / SMTP / PHP mail().
 *
 * @return array{ok:bool,via:string,error:?string}
 */
function crtlu_send_mail(string $to, string $subject, string $body): array
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'via' => 'none', 'error' => 'Invalid recipient'];
    }

    $from = trim((string)crtlu_config('CRTLU_MAIL_FROM', ''));
    $fromName = trim((string)crtlu_config('CRTLU_MAIL_FROM_NAME', 'CRTLU Digital'));
    if ($from === '') {
        return [
            'ok' => false,
            'via' => 'none',
            'error' => 'CRTLU_MAIL_FROM is not set in config.local.php',
        ];
    }

    // 1) Resend (recommended for Gmail deliverability)
    $resendKey = trim((string)crtlu_config('CRTLU_RESEND_API_KEY', ''));
    if ($resendKey !== '' && function_exists('curl_init')) {
        $payload = json_encode([
            'from' => ($fromName !== '' ? $fromName . ' <' . $from . '>' : $from),
            'to' => [$to],
            'subject' => $subject,
            'text' => $body,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $resendKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload ?: '{}',
        ]);
        $raw = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        if ($raw === false) {
            return ['ok' => false, 'via' => 'resend', 'error' => $cerr !== '' ? $cerr : 'Resend request failed'];
        }
        $decoded = json_decode((string)$raw, true);
        if ($http >= 200 && $http < 300) {
            return ['ok' => true, 'via' => 'resend', 'error' => null];
        }
        $msg = is_array($decoded) ? (string)($decoded['message'] ?? $decoded['error'] ?? $raw) : (string)$raw;
        return ['ok' => false, 'via' => 'resend', 'error' => 'HTTP ' . $http . ': ' . $msg];
    }

    // 2) SMTP (STARTTLS/SSL) when host is configured
    $smtpHost = trim((string)crtlu_config('CRTLU_SMTP_HOST', ''));
    if ($smtpHost !== '') {
        return crtlu_send_mail_smtp($to, $subject, $body, $from, $fromName);
    }

    // 3) PHP mail() — often blocked/silently dropped on shared hosts (Gmail rarely receives)
    if (!function_exists('mail')) {
        return ['ok' => false, 'via' => 'mail', 'error' => 'PHP mail() is not available'];
    }
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [
        'From: ' . ($fromName !== '' ? sprintf('"%s" <%s>', addcslashes($fromName, '"\\'), $from) : $from),
        'Reply-To: ' . $from,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Mailer: CRTLU-Shop',
    ];
    $ok = @mail($to, $encodedSubject, $body, implode("\r\n", $headers));
    return [
        'ok' => (bool)$ok,
        'via' => 'mail',
        'error' => $ok ? null : 'PHP mail() returned false (host often blocks outbound mail; configure SMTP or Resend)',
    ];
}

/**
 * Minimal SMTP client (LOGIN auth, TLS on 587 or SSL on 465).
 *
 * @return array{ok:bool,via:string,error:?string}
 */
function crtlu_send_mail_smtp(string $to, string $subject, string $body, string $from, string $fromName): array
{
    $host = trim((string)crtlu_config('CRTLU_SMTP_HOST', ''));
    $port = (int)crtlu_config('CRTLU_SMTP_PORT', '587');
    $user = (string)crtlu_config('CRTLU_SMTP_USER', '');
    $pass = (string)crtlu_config('CRTLU_SMTP_PASS', '');
    $secure = strtolower(trim((string)crtlu_config('CRTLU_SMTP_SECURE', 'tls'))); // tls|ssl|none

    if ($host === '') {
        return ['ok' => false, 'via' => 'smtp', 'error' => 'SMTP host empty'];
    }
    if ($port < 1) {
        $port = $secure === 'ssl' ? 465 : 587;
    }

    $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$fp) {
        return ['ok' => false, 'via' => 'smtp', 'error' => "Connect failed: $errstr ($errno)"];
    }
    stream_set_timeout($fp, 20);

    $read = static function () use ($fp): string {
        $data = '';
        while (!feof($fp)) {
            $line = fgets($fp, 515);
            if ($line === false) {
                break;
            }
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $write = static function (string $cmd) use ($fp): void {
        fwrite($fp, $cmd . "\r\n");
    };
    $expect = static function (string $resp, string $prefix) use ($fp): ?string {
        if (str_starts_with(trim($resp), $prefix)) {
            return null;
        }
        fclose($fp);
        return 'Unexpected SMTP: ' . trim($resp);
    };

    $greeting = $read();
    if ($err = $expect($greeting, '220')) {
        return ['ok' => false, 'via' => 'smtp', 'error' => $err];
    }

    $ehloHost = 'crtlu.me';
    $write('EHLO ' . $ehloHost);
    $ehlo = $read();
    if ($err = $expect($ehlo, '250')) {
        return ['ok' => false, 'via' => 'smtp', 'error' => $err];
    }

    if ($secure === 'tls') {
        $write('STARTTLS');
        $tls = $read();
        if ($err = $expect($tls, '220')) {
            return ['ok' => false, 'via' => 'smtp', 'error' => $err];
        }
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return ['ok' => false, 'via' => 'smtp', 'error' => 'STARTTLS crypto failed'];
        }
        $write('EHLO ' . $ehloHost);
        $ehlo2 = $read();
        if ($err = $expect($ehlo2, '250')) {
            return ['ok' => false, 'via' => 'smtp', 'error' => $err];
        }
    }

    if ($user !== '') {
        $write('AUTH LOGIN');
        $a1 = $read();
        if ($err = $expect($a1, '334')) {
            return ['ok' => false, 'via' => 'smtp', 'error' => $err];
        }
        $write(base64_encode($user));
        $a2 = $read();
        if ($err = $expect($a2, '334')) {
            return ['ok' => false, 'via' => 'smtp', 'error' => $err];
        }
        $write(base64_encode($pass));
        $a3 = $read();
        if ($err = $expect($a3, '235')) {
            return ['ok' => false, 'via' => 'smtp', 'error' => $err ?: 'SMTP auth failed'];
        }
    }

    $write('MAIL FROM:<' . $from . '>');
    if ($err = $expect($read(), '250')) {
        return ['ok' => false, 'via' => 'smtp', 'error' => $err];
    }
    $write('RCPT TO:<' . $to . '>');
    $rcpt = $read();
    if (!str_starts_with(trim($rcpt), '250') && !str_starts_with(trim($rcpt), '251')) {
        fclose($fp);
        return ['ok' => false, 'via' => 'smtp', 'error' => 'RCPT rejected: ' . trim($rcpt)];
    }
    $write('DATA');
    if ($err = $expect($read(), '354')) {
        return ['ok' => false, 'via' => 'smtp', 'error' => $err];
    }

    $fromHeader = $fromName !== ''
        ? sprintf('"%s" <%s>', addcslashes($fromName, '"\\'), $from)
        : $from;
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $normalizedBody = str_replace(["\r\n", "\r"], "\n", $body);
    $normalizedBody = str_replace("\n.", "\n..", $normalizedBody);
    $msg = implode("\r\n", [
        'From: ' . $fromHeader,
        'To: <' . $to . '>',
        'Subject: ' . $encodedSubject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'Date: ' . date('r'),
        '',
        str_replace("\n", "\r\n", $normalizedBody),
        '.',
    ]);
    fwrite($fp, $msg . "\r\n");
    if ($err = $expect($read(), '250')) {
        return ['ok' => false, 'via' => 'smtp', 'error' => $err];
    }
    $write('QUIT');
    fclose($fp);
    return ['ok' => true, 'via' => 'smtp', 'error' => null];
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

    $send = crtlu_send_mail($to, $subject, $body);
    crtlu_queue_email(
        $pdo,
        $orderId,
        $to,
        'order_confirmation',
        $subject,
        $body,
        $send['ok'] ? 'sent' : 'failed',
        $send['ok'] ? null : (($send['via'] ?? '') . ': ' . ($send['error'] ?? 'send failed'))
    );
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
        $send = crtlu_send_mail($notifyEmail, $subject, $body);
        crtlu_queue_email(
            $pdo,
            $orderId,
            $notifyEmail,
            'admin_order_alert',
            $subject,
            $body,
            $send['ok'] ? 'sent' : 'failed',
            $send['ok'] ? null : (($send['via'] ?? '') . ': ' . ($send['error'] ?? 'send failed'))
        );
    }

    crtlu_send_telegram_message($body);
}

/**
 * Queue + send member-facing email (login codes, etc.).
 *
 * @return array{ok:bool,via:string,error:?string,queued_id:int}
 */
function crtlu_send_member_email(PDO $pdo, string $to, string $template, string $subject, string $body): array
{
    $to = trim($to);
    if ($to === '') {
        return ['ok' => false, 'via' => 'none', 'error' => 'Empty recipient', 'queued_id' => 0];
    }

    $send = crtlu_send_mail($to, $subject, $body);
    $qid = crtlu_queue_email(
        $pdo,
        null,
        $to,
        $template,
        $subject,
        $body,
        $send['ok'] ? 'sent' : 'failed',
        $send['ok'] ? null : (($send['via'] ?? '') . ': ' . ($send['error'] ?? 'send failed'))
    );

    // Optional: also notify admin Telegram when login mail fails (ops visibility)
    if (!$send['ok'] && $template === 'member_login_code') {
        crtlu_send_telegram_message(
            "Login code email FAILED for {$to}\nvia=" . ($send['via'] ?? '') . "\n" . ($send['error'] ?? '')
        );
    }

    return [
        'ok' => (bool)$send['ok'],
        'via' => (string)($send['via'] ?? ''),
        'error' => $send['error'] ?? null,
        'queued_id' => $qid,
    ];
}
