<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notifications.php';

function crtlu_member_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('crtlu_member');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function crtlu_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function crtlu_request_json(): array
{
    $payload = json_decode((string)file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : [];
}

function crtlu_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function crtlu_valid_email(string $email): bool
{
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 255;
}

function crtlu_member_by_id(PDO $pdo, int $memberId): ?array
{
    if ($memberId < 1 || !crtlu_table_exists($pdo, 'members')) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, email, name, locale, currency, status, last_login_at, created_at FROM members WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $memberId]);
    $member = $stmt->fetch();
    return is_array($member) ? $member : null;
}

function crtlu_current_member(PDO $pdo): ?array
{
    crtlu_member_session_start();
    $memberId = (int)($_SESSION['member_id'] ?? 0);
    $member = crtlu_member_by_id($pdo, $memberId);
    if (!$member || ($member['status'] ?? '') !== 'active') {
        unset($_SESSION['member_id']);
        return null;
    }
    return $member;
}

function crtlu_require_member(PDO $pdo): array
{
    $member = crtlu_current_member($pdo);
    if (!$member) {
        crtlu_json(['authenticated' => false, 'message' => 'Please sign in.'], 401);
    }
    return $member;
}

function crtlu_issue_login_code(PDO $pdo, string $email): ?string
{
    if (!crtlu_table_exists($pdo, 'member_login_codes')) {
        throw new RuntimeException('Member login table is missing. Import database/phase5-migration.sql first.');
    }

    $recent = $pdo->prepare('SELECT COUNT(*) FROM member_login_codes WHERE email = :email AND created_at > (CURRENT_TIMESTAMP - INTERVAL 10 MINUTE)');
    $recent->execute([':email' => $email]);
    if ((int)$recent->fetchColumn() >= 5) {
        throw new RuntimeException('Too many login requests. Please wait a few minutes.');
    }

    $code = (string)random_int(100000, 999999);
    $stmt = $pdo->prepare(
        'INSERT INTO member_login_codes (email, code_hash, expires_at)
        VALUES (:email, :code_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 15 MINUTE))'
    );
    $stmt->execute([
        ':email' => $email,
        ':code_hash' => password_hash($code, PASSWORD_DEFAULT),
    ]);

    $body = implode("\n", [
        'Your CRTL U Digital sign-in code is:',
        '',
        $code,
        '',
        'This code expires in 15 minutes. If you did not request it, you can ignore this email.',
    ]);
    crtlu_send_member_email($pdo, $email, 'member_login_code', 'Your CRTL U Digital sign-in code', $body);

    return crtlu_config('CRTLU_LOGIN_CODE_DEBUG', '0') === '1' ? $code : null;
}

function crtlu_verify_login_code(PDO $pdo, string $email, string $code): array
{
    if (!crtlu_table_exists($pdo, 'member_login_codes')) {
        throw new RuntimeException('Member login table is missing. Import database/phase5-migration.sql first.');
    }

    $stmt = $pdo->prepare(
        'SELECT * FROM member_login_codes
        WHERE email = :email AND used_at IS NULL AND expires_at > CURRENT_TIMESTAMP
        ORDER BY created_at DESC LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($code, (string)$row['code_hash'])) {
        throw new RuntimeException('The sign-in code is invalid or expired.');
    }

    $mark = $pdo->prepare('UPDATE member_login_codes SET used_at = CURRENT_TIMESTAMP WHERE id = :id');
    $mark->execute([':id' => (int)$row['id']]);

    $memberId = crtlu_upsert_member($pdo, ['email' => $email, 'name' => ''], crtlu_config('CRTLU_DEFAULT_LOCALE', 'en') ?: 'en', crtlu_config('CRTLU_DEFAULT_CURRENCY', 'USD') ?: 'USD');
    if (!$memberId) {
        throw new RuntimeException('Member account could not be created.');
    }
    $pdo->prepare('UPDATE members SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id')->execute([':id' => $memberId]);
    $member = crtlu_member_by_id($pdo, $memberId);
    if (!$member) {
        throw new RuntimeException('Member account could not be loaded.');
    }

    crtlu_member_session_start();
    session_regenerate_id(true);
    $_SESSION['member_id'] = $memberId;

    return $member;
}

