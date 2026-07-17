<?php

require __DIR__ . '/member-auth.php';

$payload = crtlu_request_json();
$email = crtlu_normalize_email((string)($payload['email'] ?? ''));
if (!crtlu_valid_email($email)) {
    crtlu_json(['ok' => false, 'message' => 'Enter a valid email address.'], 400);
}

try {
    $pdo = crtlu_pdo();
    $debugCode = crtlu_issue_login_code($pdo, $email);
    $mail = crtlu_last_login_mail_result();
    $mailOk = is_array($mail) ? !empty($mail['ok']) : true;

    $response = [
        'ok' => true,
        'mail_sent' => $mailOk,
        'mail_via' => is_array($mail) ? (string)($mail['via'] ?? '') : '',
        'message' => $mailOk
            ? 'We sent a 6-digit sign-in code to your email. Check inbox and spam.'
            : 'We generated a sign-in code, but the email could not be delivered. Please try again later or contact support. (Mail server not configured for Gmail.)',
    ];

    if (!$mailOk && is_array($mail) && !empty($mail['error'])) {
        // Safe ops hint for admin debugging; not the code itself.
        $response['mail_error'] = (string)$mail['error'];
    }

    if ($debugCode !== null) {
        $response['debug_code'] = $debugCode;
        $response['message'] = 'Debug mode: use code ' . $debugCode . ' (email send ' . ($mailOk ? 'ok' : 'failed') . ').';
    }

    // Still HTTP 200 so the UI can show the message; mail_sent=false signals delivery issue.
    crtlu_json($response, $mailOk ? 200 : 200);
} catch (Throwable $error) {
    crtlu_json(['ok' => false, 'message' => $error->getMessage()], 500);
}
