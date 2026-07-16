<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/catalog-lib.php';

crtlu_require_admin();

$catalog = crtlu_load_catalog();
$series = $catalog['series'] ?? [];

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));

$filtered = array_values(array_filter($series, static function (array $item) use ($q, $status, $category): bool {
    if ($status !== '' && ($item['status'] ?? '') !== $status) {
        return false;
    }
    if ($category !== '' && ($item['category'] ?? '') !== $category) {
        return false;
    }
    if ($q === '') {
        return true;
    }
    $hay = strtolower(implode(' ', [
        $item['id'] ?? '',
        $item['name'] ?? '',
        $item['brand'] ?? '',
        $item['image'] ?? '',
    ]));
    return str_contains($hay, strtolower($q));
}));

$categories = [];
foreach ($series as $item) {
    $c = (string)($item['category'] ?? '');
    if ($c !== '') {
        $categories[$c] = true;
    }
}
$categories = array_keys($categories);
sort($categories);

$countPublished = count(array_filter($series, static fn($s) => ($s['status'] ?? '') === 'published'));
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>产品管理 | CRTLU Admin</title>
<style>
:root {
  color-scheme: dark;
  --bg: #071016;
  --panel: #0d171f;
  --line: rgba(255,255,255,.13);
  --text: #f5fbff;
  --muted: #91a1ae;
  --green: #8bff85;
  --cyan: #5de7ff;
}
* { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
main { width: min(1240px, calc(100% - 28px)); margin: 0 auto; padding: 28px 0 48px; }
a { color: inherit; }
.topbar { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: flex-start; margin-bottom: 18px; }
h1 { margin: 0 0 6px; font-size: 32px; }
.muted { color: var(--muted); }
.links { display: flex; gap: 8px; flex-wrap: wrap; }
.links a, .btn {
  min-height: 36px; display: inline-flex; align-items: center; justify-content: center;
  padding: 0 12px; border: 1px solid rgba(255,255,255,.18); background: var(--panel);
  text-decoration: none; font-weight: 800; font-size: 12px; letter-spacing: .03em; text-transform: uppercase; color: #fff;
}
.btn.primary, .links a.primary { background: linear-gradient(90deg, #7cff8c, var(--cyan)); color: #001014; border: 0; }
.panel { background: var(--panel); border: 1px solid var(--line); padding: 14px; margin-bottom: 16px; }
.filters { display: flex; gap: 10px; flex-wrap: wrap; align-items: end; }
label { display: grid; gap: 5px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .06em; }
input, select {
  min-height: 36px; border: 1px solid rgba(255,255,255,.18); background: #071016; color: #fff; padding: 0 10px; min-width: 160px;
}
.stats { display: flex; gap: 16px; flex-wrap: wrap; margin: 0 0 14px; color: var(--muted); font-size: 13px; }
.stats strong { color: var(--green); }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
.card {
  display: grid; grid-template-rows: 180px auto; border: 1px solid var(--line); background: linear-gradient(180deg, rgba(16,29,38,.96), rgba(10,18,24,.96));
  overflow: hidden;
}
.card:hover { border-color: rgba(93,231,255,.4); }
.thumb {
  display: grid; place-items: center; background: #fff; overflow: hidden;
}
.thumb img { width: 100%; height: 100%; object-fit: contain; background: #fff; }
.body { padding: 12px 14px 14px; display: grid; gap: 8px; }
.body h2 { margin: 0; font-size: 16px; line-height: 1.3; }
.meta { color: var(--muted); font-size: 12px; line-height: 1.45; }
.badge {
  display: inline-flex; align-items: center; min-height: 22px; padding: 0 8px; border-radius: 999px;
  font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
}
.badge.published { background: rgba(139,255,133,.14); color: var(--green); }
.badge.draft { background: rgba(255,180,80,.14); color: #ffb450; }
.badge.other { background: rgba(255,255,255,.08); color: var(--muted); }
.row { display: flex; justify-content: space-between; gap: 8px; align-items: center; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; }
.actions a { min-height: 32px; font-size: 11px; }
.empty { padding: 28px; text-align: center; color: var(--muted); border: 1px dashed var(--line); }
.hint { margin-top: 14px; color: var(--muted); font-size: 13px; line-height: 1.6; }
</style>
</head>
<body>
<main>
  <div class="topbar">
    <div>
      <h1>产品管理</h1>
      <p class="muted">手动上架新产品，或编辑已有型号的名称、规格、价格与图片。改完后前端需 <code>npm run build</code> 或 dev 热更新后才能完整反映。</p>
    </div>
    <nav class="links" aria-label="Admin nav">
      <a href="index.php">Dashboard</a>
      <a class="primary" href="products.php">产品</a>
      <a class="primary" href="product-new.php" style="background:linear-gradient(90deg,#7cff8c,#5de7ff);color:#001014;border:0;">+ 上架产品</a>
      <a href="orders.php">订单</a>
      <a href="coupons.php">优惠券</a>
    </nav>
  </div>

  <div style="margin:0 0 14px;">
    <a class="btn primary" href="product-new.php" style="display:inline-flex;min-height:38px;padding:0 16px;align-items:center;justify-content:center;background:linear-gradient(90deg,#7cff8c,#5de7ff);color:#001014;border:0;font-weight:900;text-decoration:none;text-transform:uppercase;font-size:12px;letter-spacing:.04em;">+ 上架新产品</a>
  </div>

  <div class="stats">
    <span>共 <strong><?= count($series) ?></strong> 个型号</span>
    <span>已上架 <strong><?= (int)$countPublished ?></strong></span>
    <span>当前筛选 <strong><?= count($filtered) ?></strong></span>
  </div>

  <section class="panel">
    <form class="filters" method="get">
      <label>搜索
        <input type="search" name="q" value="<?= crtlu_h($q) ?>" placeholder="名称 / 品牌 / ID">
      </label>
      <label>状态
        <select name="status">
          <option value="">全部</option>
          <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>published</option>
          <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>draft</option>
        </select>
      </label>
      <label>分类
        <select name="category">
          <option value="">全部</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= crtlu_h($c) ?>" <?= $category === $c ? 'selected' : '' ?>><?= crtlu_h($c) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn primary" type="submit">筛选</button>
      <a class="btn" href="products.php">重置</a>
    </form>
  </section>

  <?php if (!$filtered): ?>
    <div class="empty">没有匹配的产品。</div>
  <?php else: ?>
    <section class="grid">
      <?php foreach ($filtered as $item):
        $id = (string)($item['id'] ?? '');
        $name = (string)($item['name'] ?? $id);
        $brand = (string)($item['brand'] ?? '');
        $st = (string)($item['status'] ?? 'draft');
        $folder = crtlu_series_folder($item);
        $img = (string)($item['image'] ?? '');
        $galCount = is_array($item['gallery'] ?? null) ? count($item['gallery']) : 0;
        $varCount = is_array($item['variants'] ?? null) ? count($item['variants']) : 0;
        $badgeClass = $st === 'published' ? 'published' : ($st === 'draft' ? 'draft' : 'other');
      ?>
        <article class="card">
          <div class="thumb">
            <?php if ($img): ?>
              <img src="<?= crtlu_h(crtlu_cache_bust($img)) ?>" alt="<?= crtlu_h($name) ?>" loading="lazy">
            <?php else: ?>
              <span class="muted">无主图</span>
            <?php endif; ?>
          </div>
          <div class="body">
            <div class="row">
              <h2><?= crtlu_h($name) ?></h2>
              <span class="badge <?= $badgeClass ?>"><?= crtlu_h($st) ?></span>
            </div>
            <div class="meta">
              <?= crtlu_h($brand) ?> · <?= crtlu_h((string)($item['category'] ?? '')) ?><br>
              ID: <?= crtlu_h($id) ?><br>
              文件夹: <?= crtlu_h($folder) ?> · 图 <?= (int)$galCount ?> · 规格 <?= (int)$varCount ?>
            </div>
            <div class="actions">
              <a class="btn primary" href="product-edit.php?id=<?= urlencode($id) ?>">编辑</a>
              <a class="btn" href="<?= crtlu_h(crtlu_storefront_product_url($id)) ?>" target="_blank" rel="noopener">前台预览</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

  <p class="hint">
    图片写入 <code>public/assets/products/&lt;型号文件夹&gt;/</code> 与 <code>data/catalog.json</code>。<br>
    本地启动后台（推荐，上传上限 64M）：
    <code>./admin/serve.sh</code>
    然后打开 <code>http://127.0.0.1:8088/admin/products.php</code>。<br>
    「前台预览」会打开 Astro 前台（默认 <code>http://127.0.0.1:4322</code>），请先另开终端跑
    <code>npm run dev -- --host 127.0.0.1 --port 4322</code>。
  </p>
</main>
</body>
</html>
