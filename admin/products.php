<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/catalog-lib.php';
require __DIR__ . '/admin-shell.php';

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
<?php
crtlu_admin_header(
    '产品管理',
    '上架 / 编辑型号、价格与图片。线上后台：<code>https://api.crtlu.me/admin/products.php</code>。改 catalog 后前台若仍旧，需同步 Git 构建或确认 <code>data/catalog.json</code> 与 CF 一致。',
    [
        '.stats{display:flex;gap:16px;flex-wrap:wrap;margin:0 0 14px;color:var(--muted);font-size:13px}',
        '.stats strong{color:var(--green)}',
        '.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px}',
        '.card{display:grid;grid-template-rows:180px auto;border:1px solid var(--line);background:linear-gradient(180deg,rgba(16,29,38,.96),rgba(10,18,24,.96));overflow:hidden}',
        '.card:hover{border-color:rgba(93,231,255,.4)}',
        '.thumb{display:grid;place-items:center;background:#fff;overflow:hidden}',
        '.thumb img{width:100%;height:100%;object-fit:contain;background:#fff}',
        '.body{padding:12px 14px 14px;display:grid;gap:8px}',
        '.body h2{margin:0;font-size:16px;line-height:1.3}',
        '.meta{color:var(--muted);font-size:12px;line-height:1.45}',
        '.badge{display:inline-flex;align-items:center;min-height:22px;padding:0 8px;border-radius:999px;font-size:11px;font-weight:800}',
        '.badge.published{background:rgba(139,255,133,.14);color:var(--green)}',
        '.badge.draft{background:rgba(255,180,80,.14);color:#ffb450}',
        '.badge.other{background:rgba(255,255,255,.08);color:var(--muted)}',
        '.row{display:flex;justify-content:space-between;gap:8px;align-items:center}',
        '.actions{display:flex;gap:8px;flex-wrap:wrap}',
        '.actions a{min-height:32px;font-size:11px}',
        '.empty{padding:28px;text-align:center;color:var(--muted);border:1px dashed var(--line)}',
        '.hint{margin-top:14px;color:var(--muted);font-size:13px;line-height:1.6}',
        '.filters{align-items:end}',
    ]
);
?>
  <div style="margin:0 0 14px;">
    <a class="button primary" href="product-new.php">+ 上架新产品</a>
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
              <img src="<?= crtlu_h(crtlu_local_asset_url($img)) ?>" alt="<?= crtlu_h($name) ?>" loading="lazy">
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
<?php crtlu_admin_footer(); ?>
