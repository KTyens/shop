<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/admin-shell.php';
require_once __DIR__ . '/../api/yanwen-client.php';

crtlu_require_admin();

$result = null;
$action = (string)($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'probe') {
    $result = yanwen_probe();
} elseif ($action === 'countries') {
    $result = yanwen_country_list();
} elseif ($action === 'warehouses') {
    $channel = trim((string)($_GET['channelId'] ?? ''));
    $result = yanwen_warehouse_list($channel !== '' ? $channel : null);
} elseif ($action === 'channels') {
    $result = yanwen_channel_list();
}

$configured = yanwen_is_configured();

crtlu_admin_header(
    '燕文 API',
    '开放平台对接面板。官方文档：opendocs.yw56.com.cn · 设计说明见 docs/yanwen-api-integration.md'
);
?>
  <section class="panel">
    <h2 style="margin:0 0 10px;font-size:18px;">配置状态</h2>
    <?php if ($configured): ?>
      <p class="message">已检测到 <code>YANWEN_USER_ID</code> 与 <code>YANWEN_API_TOKEN</code>。</p>
    <?php else: ?>
      <p class="message error">尚未配置密钥。请在 Serv00 的 <code>api/config.local.php</code> 填写（申请通过后）：</p>
      <pre style="background:#071016;border:1px solid var(--line);padding:12px;overflow:auto;color:#c9d8df;font-size:12px;line-height:1.5;">define('YANWEN_USER_ID', '客户号');
define('YANWEN_API_TOKEN', 'apitoken');
define('YANWEN_API_BASE', 'https://open.yw56.com.cn/api/order'); // 测试: https://open-fat.yw56.com.cn/api/order
define('YANWEN_CHANNEL_ID', '');
define('YANWEN_WAREHOUSE_CODE', '');</pre>
    <?php endif; ?>
    <p class="muted" style="margin:12px 0 0;line-height:1.6;">
      公共签名与请求封装已实现（<code>api/yanwen-client.php</code>），算法已与官方文档示例对齐。
      当前订单仍可用 <a href="export-yanwen.php">燕文 CSV 导出</a> 人工下单；密钥正确后在此页先测连通，再开通「一键创建运单」。
    </p>
    <div class="muted" style="margin:14px 0 0;padding:12px;border:1px solid var(--line);line-height:1.7;font-size:13px;">
      <strong>若提示「签名错误 / 秘钥已失效」</strong>（HTTP 401）：
      <ol style="margin:8px 0 0;padding-left:18px;">
        <li>登录燕文客户中心 → <strong>账号管理 → 制单账号管理</strong>，重新复制 <strong>apitoken</strong>（不是登录密码）。</li>
        <li>确认 <code>YANWEN_USER_ID</code> 是<strong>同一客户号</strong>（正式账号不要用测试 100000 的 token）。</li>
        <li>正式环境用 <code>https://open.yw56.com.cn/api/order</code>；仅沙箱用 <code>open-fat</code> + 测试账号 100000。</li>
        <li>粘贴时去掉首尾空格/换行；改完 Serv00 上的 <code>config.local.php</code> 后再点「测试连通」。</li>
        <li>若仍失败：在客户中心<strong>重置/重新获取秘钥</strong>后再试（接口原文即提示秘钥可能已失效）。</li>
      </ol>
    </div>
  </section>

  <section class="panel">
    <h2 style="margin:0 0 12px;font-size:18px;">联调操作</h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <a class="button primary" href="yanwen.php?action=probe">测试连通（国家列表）</a>
      <a class="button" href="yanwen.php?action=countries">拉取国家列表</a>
      <a class="button" href="yanwen.php?action=channels">拉取已开通产品</a>
      <a class="button" href="yanwen.php?action=warehouses">拉取交货仓</a>
      <a class="button" href="export-yanwen.php">下载待发货 CSV</a>
      <a class="button" href="orders.php">返回订单</a>
    </div>
  </section>

  <?php if ($result !== null): ?>
  <section class="panel">
    <h2 style="margin:0 0 12px;font-size:18px;">最近一次响应</h2>
    <?php if (!empty($result['ok']) || (!empty($result['configured']) && !empty($result['ok']))): ?>
      <p class="message"><?= htmlspecialchars((string)($result['message'] ?? '成功')) ?></p>
    <?php elseif (isset($result['configured']) && $result['configured'] === false): ?>
      <p class="message error"><?= htmlspecialchars((string)($result['message'] ?? '未配置')) ?></p>
      <?php if (!empty($result['steps']) && is_array($result['steps'])): ?>
        <ol class="muted" style="line-height:1.7;">
          <?php foreach ($result['steps'] as $step): ?>
            <li><?= htmlspecialchars((string)$step) ?></li>
          <?php endforeach; ?>
        </ol>
      <?php endif; ?>
    <?php else: ?>
      <p class="message error"><?= htmlspecialchars((string)($result['error'] ?? $result['message'] ?? '失败')) ?></p>
      <?php
        $hint = $result['hint'] ?? ($result['sample']['hint'] ?? null);
        if (is_string($hint) && $hint !== ''):
      ?>
        <p class="muted" style="margin:10px 0 0;line-height:1.65;"><?= htmlspecialchars($hint) ?></p>
      <?php endif; ?>
    <?php endif; ?>
    <pre style="background:#071016;border:1px solid var(--line);padding:12px;overflow:auto;max-height:420px;color:#c9d8df;font-size:12px;line-height:1.45;"><?= htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '') ?></pre>
  </section>
  <?php endif; ?>

  <section class="panel">
    <h2 style="margin:0 0 10px;font-size:18px;">对接阶段</h2>
    <ul class="muted" style="line-height:1.75;margin:0;padding-left:18px;">
      <li><strong style="color:var(--green)">P0–P1</strong>：签名、连通、国家/仓/产品列表</li>
      <li><strong style="color:var(--green)">P2</strong>：订单页「创建燕文运单」→ 写回 <code>yanwen_tracking</code>（需配置 <code>YANWEN_CHANNEL_ID</code>）</li>
      <li><strong style="color:var(--green)">P4</strong>：前台账户「View tracking」轨迹节点</li>
      <li><strong>P3</strong>：标签打印（方法已预留，UI 待做）</li>
    </ul>
    <p class="muted" style="margin:12px 0 0;line-height:1.6;">
      一键创建前请先在本页拉「已开通产品」，把产品 id 写入 Serv00 <code>config.local.php</code> 的
      <code>YANWEN_CHANNEL_ID</code>，再到 <a href="orders.php">订单</a> 点「创建燕文运单」。
    </p>
    <p class="muted" style="margin:12px 0 0;">详细设计：仓库内 <code>docs/yanwen-api-integration.md</code></p>
  </section>
<?php crtlu_admin_footer(); ?>
