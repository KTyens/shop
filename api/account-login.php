<?php

require __DIR__ . '/member-auth.php';

$payload = crtlu_request_json();
$email = crtlu_normalize_email((string)($payload['email'] ?? ''));
$code = preg_replace('/\D+/', '', (string)($payload['code'] ?? ''));

if (!crtlu_valid_email($email) || strlen($code) !== 6) {
    crtlu_json(['ok' => false, 'message' => 'Enter your email and 6-digit code.'], 400);
}

try {
    $pdo = crtlu_pdo();
    $member = crtlu_verify_login_code($pdo, $email, $code);
    crtlu_json(['ok' => true, 'authenticated' => true, 'member' => $member]);
} catch (Throwable $error) {
    crtlu_json(['ok' => false, 'message' => $error->getMessage()], 400);
}

