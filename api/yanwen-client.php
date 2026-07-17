<?php

/**
 * Yanwen (燕文) Open Platform API client — designed against official docs:
 * https://opendocs.yw56.com.cn/webfile/6993833547773513728/
 *
 * Signing (docs):
 *   MD5_32_lower( apitoken + user_id + data + format + method + timestamp + version + apitoken )
 * where `data` is the compact JSON body string (no extra spaces).
 *
 * Config keys (api/config.local.php):
 *   YANWEN_USER_ID      — 客户号
 *   YANWEN_API_TOKEN    — 制单账号秘钥
 *   YANWEN_API_BASE     — optional, default https://open.yw56.com.cn/api/order
 *   YANWEN_API_VERSION  — optional, default V1.0
 *   YANWEN_CHANNEL_ID   — optional default product/channel id for create order
 *   YANWEN_WAREHOUSE_CODE — optional default warehouse code
 *
 * This file is safe to load without credentials: methods return structured errors.
 */

function yanwen_config(string $key, ?string $default = null): ?string
{
    $raw = null;
    if (function_exists('crtlu_config')) {
        $v = crtlu_config($key, $default);
        if ($v !== null && $v !== '') {
            $raw = (string)$v;
        }
    }
    if ($raw === null) {
        $raw = defined($key) ? (string)constant($key) : $default;
    }
    if ($raw === null) {
        return null;
    }
    // Trim BOM / whitespace from pasted secrets (common cause of “签名错误”)
    $trimmed = trim($raw, " \t\n\r\0\x0B\"'");
    $trimmed = preg_replace('/^\xEF\xBB\xBF/', '', $trimmed) ?? $trimmed;
    return $trimmed === '' ? $default : $trimmed;
}

function yanwen_is_configured(): bool
{
    $user = yanwen_config('YANWEN_USER_ID', '');
    $token = yanwen_config('YANWEN_API_TOKEN', '');
    return $user !== '' && $token !== '';
}

/**
 * Normalize Yanwen plain-text / JSON error bodies into a readable message.
 */
function yanwen_parse_error_message(?string $raw, ?array $decoded, int $http): ?string
{
    if (is_array($decoded)) {
        $msg = (string)($decoded['message'] ?? $decoded['msg'] ?? '');
        if ($msg !== '') {
            return $msg;
        }
    }
    $text = trim((string)$raw);
    if ($text === '') {
        return $http > 0 ? ('HTTP ' . $http) : null;
    }
    // Yanwen sometimes returns plain Chinese text (e.g. 签名错误...) instead of JSON
    if ($text[0] !== '{' && $text[0] !== '[') {
        return $text;
    }
    return null;
}

function yanwen_api_base(): string
{
    return rtrim((string)yanwen_config('YANWEN_API_BASE', 'https://open.yw56.com.cn/api/order'), '/');
}

/**
 * Build compact JSON for the `data` field used both as POST body and in the signature.
 */
function yanwen_compact_json(array $data): string
{
    if ($data === []) {
        return '{}';
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $json === false ? '{}' : $json;
}

/**
 * Official sign: md5(apitoken + user_id + data + format + method + timestamp + version + apitoken)
 * MD5 = 32-char lowercase hex.
 */
function yanwen_sign(string $userId, string $apiToken, string $dataJson, string $method, string $timestamp, string $version = 'V1.0', string $format = 'json'): string
{
    $raw = $apiToken . $userId . $dataJson . $format . $method . $timestamp . $version . $apiToken;
    return md5($raw);
}

/**
 * Low-level POST to Yanwen open API.
 *
 * @return array{ok:bool,http_status:int,raw:?string,response:?array,error:?string,method:string,url:string}
 */
function yanwen_request(string $method, array $body = []): array
{
    $userId = (string)yanwen_config('YANWEN_USER_ID', '');
    $token = (string)yanwen_config('YANWEN_API_TOKEN', '');
    $version = (string)yanwen_config('YANWEN_API_VERSION', 'V1.0');
    $format = 'json';
    $base = yanwen_api_base();

    if ($userId === '' || $token === '') {
        return [
            'ok' => false,
            'http_status' => 0,
            'raw' => null,
            'response' => null,
            'error' => 'Yanwen API 未配置：请在 api/config.local.php 设置 YANWEN_USER_ID 与 YANWEN_API_TOKEN。',
            'method' => $method,
            'url' => $base,
        ];
    }

    $timestamp = (string)(int)round(microtime(true) * 1000);
    $dataJson = yanwen_compact_json($body);
    $sign = yanwen_sign($userId, $token, $dataJson, $method, $timestamp, $version, $format);

    $query = http_build_query([
        'user_id' => $userId,
        'method' => $method,
        'format' => $format,
        'timestamp' => $timestamp,
        'sign' => $sign,
        'version' => $version,
    ]);
    $url = $base . '?' . $query;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $dataJson,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 45,
    ]);
    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);
    curl_close($ch);

    if ($raw === false) {
        return [
            'ok' => false,
            'http_status' => $http,
            'raw' => null,
            'response' => null,
            'error' => $cerr !== '' ? $cerr : 'Yanwen 请求失败',
            'method' => $method,
            'url' => $url,
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $plain = yanwen_parse_error_message($raw, null, $http);
        $hint = null;
        if ($plain !== null && (str_contains($plain, '签名') || str_contains($plain, '秘钥') || str_contains($plain, '密钥') || $http === 401)) {
            $hint = '签名被燕文拒绝：请到客户中心「账号管理 → 制单账号管理」重新复制 apitoken，确认 user_id 为同一客户号，去掉首尾空格后写入 config.local.php。签名算法本身已与官方示例一致。';
        }
        return [
            'ok' => false,
            'http_status' => $http,
            'raw' => $raw,
            'response' => null,
            'error' => $plain ?: 'Yanwen 返回非 JSON',
            'hint' => $hint,
            'method' => $method,
            // Do not echo full URL with sign in admin logs by default — keep for debug
            'url' => $url,
            'user_id' => $userId,
            'data_for_sign' => $dataJson,
            'timestamp' => $timestamp,
            'version' => $version,
        ];
    }

    $success = !empty($decoded['success']) || (string)($decoded['code'] ?? '') === '0';
    $err = $success ? null : (yanwen_parse_error_message($raw, $decoded, $http) ?: 'Yanwen 业务失败');
    $hint = null;
    if (!$success && $err && (str_contains($err, '签名') || str_contains($err, '秘钥') || str_contains($err, '密钥') || $http === 401)) {
        $hint = '签名被燕文拒绝：请重新获取制单账号 apitoken，确认与 user_id 配对，并检查是否用了正式/测试环境混用（open vs open-fat）。';
    }
    return [
        'ok' => $success,
        'http_status' => $http,
        'raw' => $raw,
        'response' => $decoded,
        'error' => $err,
        'hint' => $hint,
        'method' => $method,
        'url' => $url,
    ];
}

// ---- Documented / standard 小包专线 methods (names per open platform TOC) ----

/** 查询通达国家列表 common.country.getlist */
function yanwen_country_list(): array
{
    return yanwen_request('common.country.getlist', []);
}

/** 查询交货仓列表 common.warehouse.getlist */
function yanwen_warehouse_list(?string $channelId = null): array
{
    $body = [];
    if ($channelId !== null && $channelId !== '') {
        $body['channelId'] = $channelId;
    }
    return yanwen_request('common.warehouse.getlist', $body);
}

/** 查询已开通的产品列表 express.channel.getlist（官方文档 method） */
function yanwen_channel_list(): array
{
    $method = (string)yanwen_config('YANWEN_METHOD_CHANNEL', 'express.channel.getlist');
    return yanwen_request($method, []);
}

/**
 * 创建运单 express.order.create
 * @see https://opendocs.yw56.com.cn/webfile/6993833835662151680/
 */
function yanwen_create_order(array $payload): array
{
    $method = (string)yanwen_config('YANWEN_METHOD_CREATE', 'express.order.create');
    return yanwen_request($method, $payload);
}

/** 打印标签 */
function yanwen_print_label(array $payload): array
{
    $method = (string)yanwen_config('YANWEN_METHOD_LABEL', 'express.order.label.get');
    return yanwen_request($method, $payload);
}

/** 取消运单 */
function yanwen_cancel_order(array $payload): array
{
    $method = (string)yanwen_config('YANWEN_METHOD_CANCEL', 'express.order.cancel');
    return yanwen_request($method, $payload);
}

/** 查询运单详情 */
function yanwen_order_detail(array $payload): array
{
    $method = (string)yanwen_config('YANWEN_METHOD_DETAIL', 'express.order.get');
    return yanwen_request($method, $payload);
}

/**
 * 物流轨迹查询（独立 track 网关，非 open.yw56 签名接口）
 * GET https://api.track.yw56.com.cn/api/tracking?nums=运单号
 * Header: Authorization: 商户号/制单账号（YANWEN_USER_ID）
 *
 * @see https://opendocs.yw56.com.cn/webfile/7128663508291424256/
 */
function yanwen_track(string $waybillNumber): array
{
    $nums = trim($waybillNumber);
    $userId = (string)yanwen_config('YANWEN_USER_ID', '');
    $base = rtrim((string)yanwen_config('YANWEN_TRACK_BASE', 'https://api.track.yw56.com.cn/api/tracking'), '?&');

    if ($nums === '') {
        return [
            'ok' => false,
            'http_status' => 0,
            'raw' => null,
            'response' => null,
            'error' => '运单号为空',
            'checkpoints' => [],
        ];
    }
    if ($userId === '') {
        return [
            'ok' => false,
            'http_status' => 0,
            'raw' => null,
            'response' => null,
            'error' => '未配置 YANWEN_USER_ID（轨迹接口 Authorization 需要客户号）',
            'checkpoints' => [],
        ];
    }

    $url = $base . (str_contains($base, '?') ? '&' : '?') . 'nums=' . rawurlencode($nums);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $userId,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $cerr = curl_error($ch);

    if ($raw === false) {
        return [
            'ok' => false,
            'http_status' => $http,
            'raw' => null,
            'response' => null,
            'error' => $cerr !== '' ? $cerr : '轨迹请求失败',
            'checkpoints' => [],
            'url' => $url,
        ];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'http_status' => $http,
            'raw' => $raw,
            'response' => null,
            'error' => '轨迹接口返回非 JSON',
            'checkpoints' => [],
            'url' => $url,
        ];
    }

    // code 0 = success per official track docs
    $code = $decoded['code'] ?? null;
    $ok = $code === 0 || $code === '0' || ($decoded['message'] ?? '') === 'success';
    $result = $decoded['result'] ?? null;
    $first = is_array($result) && isset($result[0]) && is_array($result[0]) ? $result[0] : (is_array($result) ? $result : []);
    $checkpoints = [];
    if (is_array($first) && isset($first['checkpoints']) && is_array($first['checkpoints'])) {
        $checkpoints = $first['checkpoints'];
    }

    return [
        'ok' => $ok,
        'http_status' => $http,
        'raw' => $raw,
        'response' => $decoded,
        'error' => $ok ? null : (string)($decoded['message'] ?? '轨迹查询失败'),
        'tracking_number' => (string)($first['tracking_number'] ?? $nums),
        'waybill_number' => (string)($first['waybill_number'] ?? $nums),
        'exchange_number' => (string)($first['exchange_number'] ?? ''),
        'tracking_status' => (string)($first['tracking_status'] ?? ''),
        'checkpoints' => $checkpoints,
        'url' => $url,
    ];
}

/** Safe truncate (mbstring optional). */
function yanwen_substr(string $s, int $max): string
{
    if ($max < 1) {
        return '';
    }
    if (function_exists('mb_substr')) {
        return (string)mb_substr($s, 0, $max);
    }
    return strlen($s) <= $max ? $s : substr($s, 0, $max);
}

/** Strip " / Plug: XX" suffix from product line names for customs. */
function yanwen_clean_product_name(string $name): string
{
    $name = trim($name);
    if (preg_match('/^(.*?)\s*\/\s*Plug:\s*.+$/i', $name, $m)) {
        return trim($m[1]);
    }
    return $name;
}

/**
 * Extract waybill number from express.order.create response.
 */
function yanwen_extract_waybill(?array $response): string
{
    if (!is_array($response)) {
        return '';
    }
    $data = $response['data'] ?? null;
    if (is_array($data)) {
        foreach (['waybillNumber', 'waybill_number', 'yanwenNumber', 'trackingNumber'] as $key) {
            $v = trim((string)($data[$key] ?? ''));
            if ($v !== '') {
                return $v;
            }
        }
    }
    foreach (['waybillNumber', 'waybill_number'] as $key) {
        $v = trim((string)($response[$key] ?? ''));
        if ($v !== '') {
            return $v;
        }
    }
    return '';
}

/**
 * Map shop order + items → official express.order.create body.
 * @see https://opendocs.yw56.com.cn/webfile/6993833835662151680/
 *
 * @param array $order orders table row
 * @param list<array> $items order_items rows
 */
function yanwen_build_create_payload(array $order, array $items): array
{
    $address = json_decode((string)($order['shipping_address_json'] ?? ''), true);
    if (!is_array($address)) {
        $address = [];
    }

    $channelId = (string)yanwen_config('YANWEN_CHANNEL_ID', '');
    $companyCode = (string)yanwen_config('YANWEN_WAREHOUSE_CODE', '');
    $unitWeightG = max(1, (int)yanwen_config('YANWEN_DEFAULT_WEIGHT_G', '500'));
    $hasBattery = (int)yanwen_config('YANWEN_HAS_BATTERY', '0');
    $hscode = (string)yanwen_config('YANWEN_DEFAULT_HSCODE', '851762');
    $currency = strtoupper((string)($order['currency'] ?? 'USD'));
    if ($currency === '') {
        $currency = 'USD';
    }

    $productList = [];
    $totalQty = 0;
    $totalWeight = 0;
    foreach ($items as $item) {
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $totalQty += $qty;
        $lineWeight = $unitWeightG * $qty;
        $totalWeight += $lineWeight;
        $name = yanwen_clean_product_name((string)($item['product_name'] ?? 'Electronics'));
        if ($name === '') {
            $name = 'Electronics';
        }
        $price = round(((int)($item['unit_amount'] ?? 0)) / 100, 2);
        if ($price <= 0) {
            $price = 1.0;
        }
        $productList[] = [
            'goodsNameCh' => $name,
            'goodsNameEn' => $name,
            'price' => $price,
            'priceExport' => $price,
            'hscode' => $hscode,
            'quantity' => $qty,
            'weight' => $unitWeightG, // 单件克
        ];
    }

    if ($productList === []) {
        $productList[] = [
            'goodsNameCh' => 'Electronics',
            'goodsNameEn' => 'Electronics',
            'price' => 1.0,
            'priceExport' => 1.0,
            'hscode' => $hscode,
            'quantity' => 1,
            'weight' => $unitWeightG,
        ];
        $totalQty = 1;
        $totalWeight = $unitWeightG;
    }

    $line1 = trim((string)($address['line1'] ?? ''));
    $line2 = trim((string)($address['line2'] ?? ''));
    $fullAddress = trim($line1 . ($line2 !== '' ? ', ' . $line2 : ''));
    if ($fullAddress === '') {
        $fullAddress = 'Address pending';
    }

    $receiverName = trim((string)(($order['shipping_name'] ?? '') !== '' ? $order['shipping_name'] : ($order['customer_name'] ?? '')));
    if ($receiverName === '') {
        $receiverName = 'Customer';
    }

    $country = trim((string)($address['country'] ?? ''));
    $phone = trim((string)($order['phone'] ?? ''));

    $payload = [
        'channelId' => $channelId,
        'orderSource' => (string)yanwen_config('YANWEN_ORDER_SOURCE', 'CRTLU'),
        'orderNumber' => 'CRTLU-' . (string)($order['id'] ?? ''),
        'transactionNumber' => (string)($order['stripe_session_id'] ?? ''),
        'dateOfReceipt' => date('Y-m-d', strtotime((string)($order['created_at'] ?? 'now')) ?: time()),
        'remark' => 'CRTLU shop order #' . (string)($order['id'] ?? ''),
        'salesPlatform' => (string)yanwen_config('YANWEN_SALES_PLATFORM', 'Independent Store'),
        'receiverInfo' => [
            'name' => yanwen_substr($receiverName, 50),
            'phone' => yanwen_substr($phone !== '' ? $phone : '0000000000', 50),
            'email' => yanwen_substr((string)($order['customer_email'] ?? ''), 100),
            'country' => $country,
            'state' => yanwen_substr(trim((string)($address['state'] ?? '')), 50),
            'city' => yanwen_substr(trim((string)($address['city'] ?? '')), 50),
            'zipCode' => yanwen_substr(trim((string)($address['postal_code'] ?? '')), 50),
            'address' => yanwen_substr($fullAddress, 200),
        ],
        'parcelInfo' => [
            'hasBattery' => $hasBattery ? 1 : 0,
            'currency' => $currency,
            'totalQuantity' => $totalQty,
            'totalWeight' => $totalWeight,
            'productList' => $productList,
        ],
    ];

    if ($companyCode !== '') {
        $payload['companyCode'] = $companyCode;
    }

    // Optional ship-from (improves clearance on some channels)
    $senderName = (string)yanwen_config('YANWEN_SENDER_NAME', '');
    if ($senderName !== '') {
        $payload['senderInfo'] = [
            'name' => yanwen_substr($senderName, 50),
            'phone' => yanwen_substr((string)yanwen_config('YANWEN_SENDER_PHONE', ''), 50),
            'email' => yanwen_substr((string)yanwen_config('YANWEN_SENDER_EMAIL', ''), 100),
            'company' => yanwen_substr((string)yanwen_config('YANWEN_SENDER_COMPANY', 'CRTLU Digital'), 100),
            'country' => (string)yanwen_config('YANWEN_SENDER_COUNTRY', 'CN'),
            'state' => (string)yanwen_config('YANWEN_SENDER_STATE', ''),
            'city' => (string)yanwen_config('YANWEN_SENDER_CITY', ''),
            'zipCode' => (string)yanwen_config('YANWEN_SENDER_ZIP', ''),
            'address' => yanwen_substr((string)yanwen_config('YANWEN_SENDER_ADDRESS', ''), 200),
        ];
    }

    return $payload;
}

/**
 * Validate payload before create (channel + receiver country/address).
 *
 * @return list<string> empty if ok
 */
function yanwen_validate_create_payload(array $payload): array
{
    $errors = [];
    if (trim((string)($payload['channelId'] ?? '')) === '') {
        $errors[] = '未配置 YANWEN_CHANNEL_ID（请在后台燕文 API 页拉取产品列表后填入）';
    }
    $recv = is_array($payload['receiverInfo'] ?? null) ? $payload['receiverInfo'] : [];
    if (trim((string)($recv['country'] ?? '')) === '') {
        $errors[] = '收件人国家缺失（shipping_address_json.country）';
    }
    if (trim((string)($recv['address'] ?? '')) === '' || (string)$recv['address'] === 'Address pending') {
        $errors[] = '收件地址不完整';
    }
    if (trim((string)($recv['name'] ?? '')) === '') {
        $errors[] = '收件人姓名缺失';
    }
    $parcel = is_array($payload['parcelInfo'] ?? null) ? $payload['parcelInfo'] : [];
    if (empty($parcel['productList']) || !is_array($parcel['productList'])) {
        $errors[] = '订单无商品行，无法申报';
    }
    return $errors;
}

/**
 * Create Yanwen waybill for a shop order and write back yanwen_tracking.
 *
 * @return array{ok:bool,message:string,waybill?:string,order_id?:int,yanwen?:array,payload?:array}
 */
function yanwen_fulfill_shop_order(PDO $pdo, int $orderId, bool $force = false): array
{
    if (!yanwen_is_configured()) {
        return ['ok' => false, 'message' => '燕文 API 未配置密钥', 'order_id' => $orderId];
    }

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return ['ok' => false, 'message' => '订单不存在', 'order_id' => $orderId];
    }

    $existing = trim((string)($order['yanwen_tracking'] ?? ''));
    if ($existing !== '' && !$force) {
        return [
            'ok' => false,
            'message' => '订单已有运单号 ' . $existing . '（如需重建请勾选强制）',
            'order_id' => $orderId,
            'waybill' => $existing,
        ];
    }

    $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id ASC');
    $itemsStmt->execute([':id' => $orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    $payload = yanwen_build_create_payload($order, $items);
    $errors = yanwen_validate_create_payload($payload);
    if ($errors) {
        return [
            'ok' => false,
            'message' => implode('；', $errors),
            'order_id' => $orderId,
            'payload' => $payload,
        ];
    }

    $result = yanwen_create_order($payload);
    if (empty($result['ok'])) {
        return [
            'ok' => false,
            'message' => (string)($result['error'] ?? '创建运单失败'),
            'order_id' => $orderId,
            'yanwen' => $result,
            'payload' => $payload,
        ];
    }

    $waybill = yanwen_extract_waybill(is_array($result['response'] ?? null) ? $result['response'] : null);
    if ($waybill === '') {
        return [
            'ok' => false,
            'message' => '燕文返回成功但未解析到 waybillNumber，请查看原始响应',
            'order_id' => $orderId,
            'yanwen' => $result,
            'payload' => $payload,
        ];
    }

    $newStatus = in_array((string)$order['status'], ['paid', 'processing'], true) ? 'shipped' : (string)$order['status'];
    $upd = $pdo->prepare('UPDATE orders SET yanwen_tracking = :tracking, status = :status WHERE id = :id');
    $upd->execute([
        ':tracking' => $waybill,
        ':status' => $newStatus,
        ':id' => $orderId,
    ]);

    return [
        'ok' => true,
        'message' => '已创建燕文运单并写回：' . $waybill,
        'order_id' => $orderId,
        'waybill' => $waybill,
        'status' => $newStatus,
        'yanwen' => $result,
    ];
}

/**
 * Health / connectivity probe used by admin UI before real shipping.
 */
function yanwen_probe(): array
{
    if (!yanwen_is_configured()) {
        return [
            'configured' => false,
            'ok' => false,
            'message' => '未配置 YANWEN_USER_ID / YANWEN_API_TOKEN',
            'steps' => [
                '在燕文客户中心申请开放平台 / 制单 API',
                '在 api/config.local.php 填写 YANWEN_USER_ID、YANWEN_API_TOKEN',
                '可选：YANWEN_CHANNEL_ID、YANWEN_WAREHOUSE_CODE',
                '在后台「燕文API」页点击测试：国家列表 / 交货仓 / 产品列表',
            ],
        ];
    }

    $countries = yanwen_country_list();
    $userId = (string)yanwen_config('YANWEN_USER_ID', '');
    $token = (string)yanwen_config('YANWEN_API_TOKEN', '');
    $base = yanwen_api_base();
    $tokenLen = strlen($token);
    $tokenPreview = $tokenLen <= 8
        ? str_repeat('*', $tokenLen)
        : (substr($token, 0, 4) . str_repeat('*', max(0, $tokenLen - 8)) . substr($token, -4));

    return [
        'configured' => true,
        'ok' => $countries['ok'],
        'message' => $countries['ok']
            ? '签名与连通性正常（common.country.getlist）'
            : ($countries['error'] ?? '探测失败'),
        'hint' => $countries['hint'] ?? null,
        'config_check' => [
            'user_id' => $userId,
            'api_base' => $base,
            'token_length' => $tokenLen,
            'token_preview' => $tokenPreview,
            'env_guess' => str_contains($base, 'open-fat') ? '测试(open-fat)' : '正式(open)',
        ],
        'sample' => $countries,
    ];
}
