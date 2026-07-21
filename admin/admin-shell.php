<?php

/**
 * Shared admin chrome (Chinese UI).
 * After auth: crtlu_admin_header('标题', '副标题'); ... crtlu_admin_footer();
 */

function crtlu_admin_nav_items(): array
{
    return [
        ['href' => 'index.php', 'label' => '总览'],
        ['href' => 'products.php', 'label' => '产品'],
        ['href' => 'product-new.php', 'label' => '上架'],
        ['href' => 'orders.php', 'label' => '订单'],
        ['href' => 'members.php', 'label' => '会员'],
        ['href' => 'coupons.php', 'label' => '优惠券'],
        ['href' => 'emails.php', 'label' => '邮件'],
        ['href' => 'export-yanwen.php', 'label' => '燕文CSV'],
        ['href' => 'yanwen.php', 'label' => '燕文API'],
    ];
}

function crtlu_admin_status_label(string $status): string
{
    return match ($status) {
        'paid' => '已付款',
        'processing' => '处理中',
        'shipped' => '已发货',
        'delivered' => '已送达',
        'refunded' => '已退款',
        'published' => '已上架',
        'draft' => '草稿',
        'queued' => '排队中',
        'sent' => '已发送',
        'failed' => '失败',
        'active' => '启用',
        'inactive' => '停用',
        default => $status,
    };
}

function crtlu_admin_header(string $title, string $subtitle = '', array $extraCss = []): void
{
    $current = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $subEsc = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $titleEsc . ' | CRTLU 后台</title>';
    echo '<style>
:root { color-scheme: dark; --bg:#071016; --panel:#0d171f; --line:rgba(255,255,255,.13); --text:#f5fbff; --muted:#91a1ae; --green:#8bff85; --cyan:#5de7ff; --danger:#ff8f8f; }
* { box-sizing: border-box; }
body { margin:0; font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Noto Sans SC",sans-serif; background:var(--bg); color:var(--text); }
a { color: inherit; }
code { font-size: 12px; color: #b7c9d3; }
.admin-top { border-bottom:1px solid var(--line); background:rgba(13,23,31,.97); position:sticky; top:0; z-index:30; backdrop-filter: blur(8px); }
.admin-top-inner { width:min(1280px,calc(100% - 28px)); margin:0 auto; padding:12px 0; display:flex; flex-wrap:wrap; gap:10px 14px; align-items:center; justify-content:space-between; }
.admin-brand { font-weight:900; letter-spacing:.06em; color:var(--green); text-decoration:none; text-transform:uppercase; font-size:13px; }
.admin-nav { display:flex; flex-wrap:wrap; gap:6px; }
.admin-nav a { display:inline-flex; align-items:center; min-height:32px; padding:0 10px; border:1px solid rgba(255,255,255,.14); background:var(--panel); text-decoration:none; font-size:12px; font-weight:800; }
.admin-nav a.active, .admin-nav a:hover { border-color:rgba(93,231,255,.55); color:var(--cyan); }
.admin-main { width:min(1280px,calc(100% - 28px)); margin:0 auto; padding:22px 0 48px; }
.admin-page-title { margin:0 0 6px; font-size:clamp(26px,4vw,40px); line-height:1.08; }
.admin-page-sub { margin:0 0 18px; color:var(--muted); line-height:1.55; }
.muted { color: var(--muted); }
.button, button.button, .btn {
  display:inline-flex; align-items:center; justify-content:center; min-height:34px; padding:0 12px;
  border:1px solid rgba(255,255,255,.18); text-decoration:none; background:#0d171f; font-weight:800; font-size:12px; cursor:pointer; color:inherit;
}
.button.primary, .btn.primary, button.primary { background:linear-gradient(90deg,#7cff8c,var(--cyan)); color:#001014; border:0; }
.button.danger, .btn.danger, button.danger { background:rgba(255,100,100,.14); color:var(--danger); border-color:rgba(255,100,100,.35); }
.cards { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin:16px 0; }
.card { background:var(--panel); border:1px solid var(--line); padding:16px; }
.card strong { display:block; font-size:26px; margin-top:6px; }
.filters { display:flex; gap:8px; flex-wrap:wrap; margin:16px 0; }
.filters a { display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border:1px solid rgba(255,255,255,.18); text-decoration:none; background:var(--panel); }
.filters a.active { background:var(--cyan); color:#001014; font-weight:800; }
table { width:100%; border-collapse:collapse; background:var(--panel); border:1px solid var(--line); }
th, td { padding:12px; border-bottom:1px solid rgba(255,255,255,.12); text-align:left; vertical-align:top; font-size:14px; }
th { color:var(--green); }
input, select, textarea { min-height:34px; border:1px solid rgba(255,255,255,.18); background:#071016; color:#fff; padding:0 8px; font:inherit; }
textarea { min-height:90px; padding:8px; width:100%; }
label { display:grid; gap:5px; color:var(--muted); font-size:12px; }
.panel { background:var(--panel); border:1px solid var(--line); padding:16px; margin-bottom:16px; }
.message { color:var(--green); margin:10px 0; }
.message.error { color:var(--danger); }
.table-wrap { overflow-x:auto; }
.status-pill, .plug-pill { display:inline-flex; padding:4px 8px; border:1px solid rgba(139,255,133,.3); color:var(--green); background:rgba(139,255,133,.08); font-size:12px; font-weight:800; }
.plug-pill { border-color:rgba(93,231,255,.32); color:#8feeff; background:rgba(93,231,255,.08); }
@media (max-width:900px) { .cards { grid-template-columns:1fr; } table { min-width:980px; } }
' . implode("\n", $extraCss) . '
</style></head><body>';
    echo '<header class="admin-top"><div class="admin-top-inner">';
    echo '<a class="admin-brand" href="index.php">CRTLU 后台</a><nav class="admin-nav" aria-label="后台模块">';
    foreach (crtlu_admin_nav_items() as $item) {
        $href = htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $active = '';
        if ($current === $item['href']) {
            $active = ' active';
        }
        if (in_array($current, ['product-edit.php', 'products.php'], true) && $item['href'] === 'products.php') {
            $active = ' active';
        }
        if ($current === 'product-new.php' && $item['href'] === 'product-new.php') {
            $active = ' active';
        }
        if (str_starts_with($current, 'yanwen') && $item['href'] === 'yanwen.php') {
            $active = ' active';
        }
        echo '<a class="' . trim($active) . '" href="' . $href . '">' . $label . '</a>';
    }
    echo '</nav></div></header>';
    echo '<main class="admin-main">';
    echo '<h1 class="admin-page-title">' . $titleEsc . '</h1>';
    if ($subtitle !== '') {
        echo '<p class="admin-page-sub">' . $subEsc . '</p>';
    }
}

function crtlu_admin_footer(): void
{
    echo '<p class="muted" style="margin-top:28px;font-size:13px;line-height:1.65">';
    echo '线上入口：<a href="https://api.crtlu.me/admin/"><code>https://api.crtlu.me/admin/</code></a> · ';
    echo '前台：<a href="https://shop.crtlu.me/" target="_blank" rel="noopener">shop.crtlu.me</a> · ';
    echo '账号密码在 Serv00 的 <code>api/config.local.php</code>';
    echo '</p></main></body></html>';
}
