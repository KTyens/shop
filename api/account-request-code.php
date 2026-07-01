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
    $response = [
        'ok' => true,
        'message' => 'We sent a 6-digit sign-in code to your email.',
    ];
    if ($debugCode !== null) {
        $response['debug_code'] = $debugCode;
    }
    crtlu_json($response);
} catch (Throwable $error) {
    crtlu_json(['ok' => false, 'message' => $error->getMessage()], 500);
}

