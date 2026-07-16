<?php

/**
 * Shared admin chrome: top nav for all modules.
 * Usage: after auth, call crtlu_admin_header('页面标题'); ... crtlu_admin_footer();
 */

function crtlu_admin_nav_items(): array
{
    return [
        ['href' => 'index.php', 'label' => 'Dashboard'],
        ['href' => 'products.php', 'label' => '产品'],
        ['href' => 'product-new.php', 'label' => '上架'],
        ['href' => 'orders.php', 'label' => '订单'],
        ['href' => 'members.php', 'label' => '会员'],
        ['href' => 'coupons.php', 'label' => '优惠券'],
        ['href' => 'emails.php', 'label' => '邮件'],
        ['href' => 'export-yanwen.php', 'label' => '燕文导出'],
    ];
}

function crtlu_admin_current_script(): string
{
    return basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
}

function crtlu_admin_header(string $title, string $subtitle = ''): void
{
    $current = crtlu_admin_current_script();
    $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $subEsc = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="zh-CN"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . $titleEsc . ' | CRTLU Admin</title>';
    echo '<style>
:root { color-scheme: dark; --bg:#071016; --panel:#0d171f; --line:rgba(255,255,255,.13); --text:#f5fbff; --muted:#91a1ae; --green:#8bff85; --cyan:#5de7ff; }
* { box-sizing: border-box; }
body { margin:0; font-family: system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; background:var(--bg); color:var(--text); }
a { color: inherit; }
.admin-top { border-bottom:1px solid var(--line); background:rgba(13,23,31,.96); position:sticky; top:0; z-index:20; }
.admin-top-inner { width:min(1280px,calc(100% - 28px)); margin:0 auto; padding:12px 0; display:flex; flex-wrap:wrap; gap:10px 14px; align-items:center; justify-content:space-between; }
.admin-brand { font-weight:900; letter-spacing:.04em; color:var(--green); text-decoration:none; text-transform:uppercase; font-size:13px; }
.admin-nav { display:flex; flex-wrap:wrap; gap:6px; }
.admin-nav a { display:inline-flex; align-items:center; min-height:32px; padding:0 10px; border:1px solid rgba(255,255,255,.14); background:var(--panel); text-decoration:none; font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; }
.admin-nav a.active, .admin-nav a:hover { border-color:rgba(93,231,255,.55); color:var(--cyan); }
.admin-main { width:min(1280px,calc(100% - 28px)); margin:0 auto; padding:22px 0 48px; }
.admin-page-title { margin:0 0 6px; font-size:clamp(28px,4vw,42px); line-height:1.05; }
.admin-page-sub { margin:0 0 18px; color:var(--muted); line-height:1.55; }
.muted { color: var(--muted); }
.button { display:inline-flex; align-items:center; min-height:34px; padding:0 12px; border:1px solid rgba(255,255,255,.18); text-decoration:none; background:#0d171f; font-weight:800; font-size:12px; text-transform:uppercase; cursor:pointer; color:inherit; }
.button.primary { background:linear-gradient(90deg,#7cff8c,var(--cyan)); color:#001014; border:0; }
</style></head><body>';
    echo '<header class="admin-top"><div class="admin-top-inner">';
    echo '<a class="admin-brand" href="index.php">CRTLU Admin</a><nav class="admin-nav" aria-label="Admin modules">';
    foreach (crtlu_admin_nav_items() as $item) {
        $href = htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8');
        $active = ($current === $item['href'] || ($item['href'] === 'products.php' && str_starts_with($current, 'product-'))) ? ' active' : '';
        // product-edit should highlight 产品
        if ($current === 'product-edit.php' && $item['href'] === 'products.php') {
            $active = ' active';
        }
        if ($current === 'product-new.php' && $item['href'] === 'product-new.php') {
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
    echo '<p class="muted" style="margin-top:28px;font-size:13px;line-height:1.6">入口：<a href="index.php">Dashboard</a> · 线上地址 <code>https://api.crtlu.me/admin/</code> · 前台 <a href="https://shop.crtlu.me/" target="_blank" rel="noopener">shop.crtlu.me</a></p>';
    echo '</main></body></html>';
}
