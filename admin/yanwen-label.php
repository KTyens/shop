<?php

/**
 * Download Yanwen shipping label PDF for an order or waybill.
 * GET: order_id=123  OR  waybill=YE...
 * Optional: print_remark=1  (include pick list on label)
 */

require __DIR__ . '/auth.php';
require_once __DIR__ . '/../api/yanwen-client.php';

crtlu_require_admin();

$orderId = (int)($_GET['order_id'] ?? 0);
$waybill = trim((string)($_GET['waybill'] ?? ''));
$printRemark = !empty($_GET['print_remark']);

if ($waybill === '' && $orderId > 0) {
    try {
        $pdo = crtlu_pdo();
        $stmt = $pdo->prepare('SELECT yanwen_tracking FROM orders WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $waybill = trim((string)($row['yanwen_tracking'] ?? ''));
    } catch (Throwable $e) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo '数据库错误：' . $e->getMessage();
        exit;
    }
}

if ($waybill === '') {
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><meta charset="utf-8"><title>标签</title>';
    echo '<p>缺少运单号。请先创建燕文运单或填写运单号后再打印。</p>';
    echo '<p><a href="orders.php">返回订单</a></p>';
    exit;
}

$result = yanwen_fetch_label_pdf($waybill, $printRemark);
if (empty($result['ok']) || empty($result['pdf_binary'])) {
    http_response_code(502);
    header('Content-Type: text/html; charset=utf-8');
    $msg = htmlspecialchars((string)($result['message'] ?? '打印失败'));
    $wb = htmlspecialchars($waybill);
    echo '<!DOCTYPE html><meta charset="utf-8"><title>标签失败</title>';
    echo '<p style="color:#b42318;"><strong>打印标签失败</strong></p>';
    echo '<p>运单号：' . $wb . '</p>';
    echo '<p>' . $msg . '</p>';
    echo '<p><a href="orders.php">返回订单</a> · <a href="yanwen.php">燕文 API</a></p>';
    if (!empty($result['yanwen']['raw'])) {
        echo '<pre style="max-width:900px;overflow:auto;background:#111;color:#c9d8df;padding:12px;font-size:12px;">';
        echo htmlspecialchars(substr((string)$result['yanwen']['raw'], 0, 4000));
        echo '</pre>';
    }
    exit;
}

$filename = 'yanwen-label-' . preg_replace('/[^A-Za-z0-9_-]+/', '', $waybill) . '.pdf';
$binary = $result['pdf_binary'];

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . (string)strlen($binary));
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
echo $binary;
exit;
