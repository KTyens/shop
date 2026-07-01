<?php

require __DIR__ . '/member-auth.php';

function account_orders(PDO $pdo, array $member): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM orders
        WHERE member_id = :member_id OR customer_email = :email
        ORDER BY created_at DESC LIMIT 50'
    );
    $stmt->execute([
        ':member_id' => (int)$member['id'],
        ':email' => (string)$member['email'],
    ]);
    $orders = $stmt->fetchAll();

    $ids = array_map(static fn(array $order): int => (int)$order['id'], $orders);
    $itemsByOrder = [];
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $items = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY id ASC");
        $items->execute($ids);
        foreach ($items->fetchAll() as $item) {
            $itemsByOrder[(int)$item['order_id']][] = $item;
        }
    }

    foreach ($orders as &$order) {
        $order['items'] = $itemsByOrder[(int)$order['id']] ?? [];
    }
    unset($order);
    return $orders;
}

function account_addresses(PDO $pdo, int $memberId): array
{
    if (!crtlu_table_exists($pdo, 'member_addresses')) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT * FROM member_addresses WHERE member_id = :member_id ORDER BY is_default DESC, updated_at DESC, id DESC');
    $stmt->execute([':member_id' => $memberId]);
    return $stmt->fetchAll();
}

try {
    $pdo = crtlu_pdo();
} catch (Throwable $error) {
    crtlu_json([
        'authenticated' => false,
        'member' => null,
        'orders' => [],
        'addresses' => [],
        'message' => 'Account database is not configured yet.',
    ], $_SERVER['REQUEST_METHOD'] === 'GET' ? 200 : 500);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $member = crtlu_current_member($pdo);
    if (!$member) {
        crtlu_json(['authenticated' => false, 'member' => null, 'orders' => [], 'addresses' => []]);
    }
    crtlu_json([
        'authenticated' => true,
        'member' => $member,
        'orders' => account_orders($pdo, $member),
        'addresses' => account_addresses($pdo, (int)$member['id']),
    ]);
}

$member = crtlu_require_member($pdo);
$payload = crtlu_request_json();
$action = (string)($payload['action'] ?? '');

try {
    if ($action === 'profile') {
        $name = trim(substr((string)($payload['name'] ?? ''), 0, 255));
        $locale = substr((string)($payload['locale'] ?? 'en'), 0, 16);
        $currency = strtoupper(substr((string)($payload['currency'] ?? 'USD'), 0, 8));
        $stmt = $pdo->prepare('UPDATE members SET name = :name, locale = :locale, currency = :currency WHERE id = :id');
        $stmt->execute([
            ':name' => $name,
            ':locale' => $locale ?: 'en',
            ':currency' => $currency ?: 'USD',
            ':id' => (int)$member['id'],
        ]);
    } elseif ($action === 'save_address') {
        if (!crtlu_table_exists($pdo, 'member_addresses')) {
            throw new RuntimeException('Address table is missing. Import database/phase5-migration.sql first.');
        }
        $address = is_array($payload['address'] ?? null) ? $payload['address'] : [];
        $addressId = (int)($address['id'] ?? 0);
        $isDefault = !empty($address['is_default']) ? 1 : 0;
        if ($isDefault) {
            $pdo->prepare('UPDATE member_addresses SET is_default = 0 WHERE member_id = :member_id')->execute([':member_id' => (int)$member['id']]);
        }
        $params = [
            ':member_id' => (int)$member['id'],
            ':label' => trim(substr((string)($address['label'] ?? 'Default'), 0, 80)) ?: 'Default',
            ':recipient_name' => trim(substr((string)($address['recipient_name'] ?? ''), 0, 255)),
            ':phone' => trim(substr((string)($address['phone'] ?? ''), 0, 80)),
            ':country' => trim(substr((string)($address['country'] ?? ''), 0, 80)),
            ':postal_code' => trim(substr((string)($address['postal_code'] ?? ''), 0, 40)),
            ':state' => trim(substr((string)($address['state'] ?? ''), 0, 120)),
            ':city' => trim(substr((string)($address['city'] ?? ''), 0, 120)),
            ':line1' => trim(substr((string)($address['line1'] ?? ''), 0, 255)),
            ':line2' => trim(substr((string)($address['line2'] ?? ''), 0, 255)),
            ':is_default' => $isDefault,
        ];
        if ($addressId > 0) {
            $params[':id'] = $addressId;
            $stmt = $pdo->prepare(
                'UPDATE member_addresses SET label = :label, recipient_name = :recipient_name, phone = :phone,
                country = :country, postal_code = :postal_code, state = :state, city = :city,
                line1 = :line1, line2 = :line2, is_default = :is_default
                WHERE id = :id AND member_id = :member_id'
            );
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO member_addresses (member_id, label, recipient_name, phone, country, postal_code, state, city, line1, line2, is_default)
                VALUES (:member_id, :label, :recipient_name, :phone, :country, :postal_code, :state, :city, :line1, :line2, :is_default)'
            );
            $stmt->execute($params);
        }
    } elseif ($action === 'delete_address') {
        $addressId = (int)($payload['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM member_addresses WHERE id = :id AND member_id = :member_id');
        $stmt->execute([':id' => $addressId, ':member_id' => (int)$member['id']]);
    } else {
        crtlu_json(['ok' => false, 'message' => 'Unknown account action.'], 400);
    }

    $member = crtlu_member_by_id($pdo, (int)$member['id']);
    if (!$member) {
        crtlu_member_session_start();
        unset($_SESSION['member_id']);
        crtlu_json(['ok' => false, 'authenticated' => false, 'message' => 'Account session expired.'], 401);
    }
    crtlu_json([
        'ok' => true,
        'authenticated' => true,
        'member' => $member,
        'orders' => account_orders($pdo, $member),
        'addresses' => account_addresses($pdo, (int)($member['id'] ?? 0)),
    ]);
} catch (Throwable $error) {
    crtlu_json(['ok' => false, 'message' => $error->getMessage()], 500);
}
