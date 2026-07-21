<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/catalog-lib.php';

crtlu_require_admin();

$catalog = crtlu_load_catalog();
$message = '';
$error = '';

// Prefill from GET (optional)
$prefill = [
    'id' => trim((string)($_GET['id'] ?? '')),
    'name' => trim((string)($_GET['name'] ?? '')),
    'brand' => trim((string)($_GET['brand'] ?? '')),
    'category' => trim((string)($_GET['category'] ?? 'tv-box')),
    'tier' => trim((string)($_GET['tier'] ?? 'main')),
    'status' => 'published',
    'description' => '',
];

function crtlu_parse_new_variants(): array
{
    $ids = $_POST['v_id'] ?? [];
    $skus = $_POST['v_sku'] ?? [];
    $labels = $_POST['v_label'] ?? [];
    $prices = $_POST['v_price'] ?? [];
    $compares = $_POST['v_compare'] ?? [];
    $rmbs = $_POST['v_rmb'] ?? [];
    if (!is_array($ids)) {
        $ids = [];
    }
    $out = [];
    $count = max(count($ids), count($skus), count($labels), count($prices));
    for ($i = 0; $i < $count; $i++) {
        $vid = trim((string)($ids[$i] ?? ''));
        $label = trim((string)($labels[$i] ?? ''));
        $sku = trim((string)($skus[$i] ?? ''));
        if ($vid === '' && $label === '' && $sku === '') {
            continue;
        }
        if ($vid === '') {
            $base = $sku !== '' ? $sku : ($label !== '' ? $label : 'variant');
            $vid = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $base) ?? '') ?: ('var-' . ($i + 1));
        }
        $price = (float)str_replace(',', '', (string)($prices[$i] ?? '0'));
        $compare = (float)str_replace(',', '', (string)($compares[$i] ?? '0'));
        if ($compare <= 0) {
            $compare = $price;
        }
        $rmb = (float)str_replace(',', '', (string)($rmbs[$i] ?? '0'));
        $row = [
            'id' => $vid,
            'sku' => $sku !== '' ? $sku : strtoupper(str_replace('-', '-', $vid)),
            'label' => $label !== '' ? $label : ($sku !== '' ? $sku : 'Standard'),
            'price_cents' => (int)round(max(0, $price) * 100),
            'compare_at_cents' => (int)round(max(0, $compare) * 100),
        ];
        if ($rmb > 0) {
            $row['rmb_price'] = $rmb;
        }
        $out[] = $row;
    }
    return $out;
}

function crtlu_parse_new_specs(): array
{
    $keys = $_POST['spec_key'] ?? [];
    $vals = $_POST['spec_val'] ?? [];
    if (!is_array($keys)) {
        return [];
    }
    $out = [];
    foreach ($keys as $i => $key) {
        $key = trim((string)$key);
        $val = trim((string)($vals[$i] ?? ''));
        if ($key === '' || $val === '') {
            continue;
        }
        $out[$key] = $val;
    }
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = trim((string)($_POST['name'] ?? ''));
        $brand = trim((string)($_POST['brand'] ?? ''));
        $category = trim((string)($_POST['category'] ?? 'tv-box'));
        $tier = trim((string)($_POST['tier'] ?? 'main'));
        $status = trim((string)($_POST['status'] ?? 'published'));
        $description = trim((string)($_POST['description'] ?? ''));
        $rawId = trim((string)($_POST['id'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('产品名称不能为空。');
        }
        if ($brand === '') {
            throw new RuntimeException('品牌不能为空。');
        }
        if (!in_array($category, ['tv-box', 'projector', 'accessory', 'premium'], true)) {
            // allow free text categories too, but normalize known ones
            if ($category === '') {
                $category = 'tv-box';
            }
        }
        if (!in_array($status, ['published', 'draft'], true)) {
            $status = 'draft';
        }

        $desiredId = $rawId !== '' ? $rawId : $name;
        $id = crtlu_unique_series_id($catalog, $desiredId);
        // If user forced an id that already exists (same after slugify of exact id)
        if ($rawId !== '' && crtlu_find_series($catalog, crtlu_slugify($rawId)) !== null && crtlu_slugify($rawId) !== $id) {
            // unique helper already bumped; keep unique
        }
        // Prefer exact slug of user id when free
        if ($rawId !== '') {
            $slug = crtlu_slugify($rawId);
            if (crtlu_find_series($catalog, $slug) === null) {
                $id = $slug;
            }
        }

        $variants = crtlu_parse_new_variants();
        if (!$variants) {
            throw new RuntimeException('至少填写一个规格/变体（SKU 或标签 + 售价）。');
        }
        // Prefix variant ids with series id if they look too generic
        foreach ($variants as &$v) {
            if (!str_starts_with((string)$v['id'], $id)) {
                $v['id'] = $id . '-' . crtlu_slugify((string)$v['id']);
            }
            if ($v['sku'] === '' || $v['sku'] === strtoupper((string)$v['id'])) {
                // keep
            }
        }
        unset($v);

        $specs = crtlu_parse_new_specs();
        $minPrice = min(array_map(static fn($v) => (int)$v['price_cents'], $variants));
        if (!isset($specs['starting_price'])) {
            $specs['starting_price'] = '$' . number_format($minPrice / 100, 2, '.', '');
        }
        if (!isset($specs['brand']) && $brand !== '') {
            $specs['brand'] = $brand;
        }
        if (!isset($specs['Model'])) {
            $specs['Model'] = $name;
        }
        $configs = array_map(static fn($v) => (string)$v['label'], $variants);
        if (!isset($specs['configurations'])) {
            $specs['configurations'] = implode(' / ', $configs);
        }

        $folder = $id;
        crtlu_ensure_product_folders($folder);
        $mainRel = 'assets/products/' . $folder . '/main.jpg';
        $gallery = [$mainRel];

        // Optional main upload on create
        $mainFile = $_FILES['main_image'] ?? null;
        if (is_array($mainFile) && (int)($mainFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $tmp = crtlu_require_uploaded_file($mainFile, '主图');
            $bin = crtlu_process_upload($tmp, true, 1400);
            crtlu_write_product_file($folder, 'main.jpg', $bin);
        }

        // Optional detail uploads
        $detailFiles = $_FILES['detail_images'] ?? null;
        $detailCount = 0;
        if (is_array($detailFiles) && isset($detailFiles['tmp_name'])) {
            $tmps = $detailFiles['tmp_name'];
            $errs = $detailFiles['error'] ?? [];
            if (!is_array($tmps)) {
                $tmps = [$tmps];
                $errs = [$errs];
            }
            foreach ($tmps as $i => $tmp) {
                $err = (int)($errs[$i] ?? UPLOAD_ERR_NO_FILE);
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($err !== UPLOAD_ERR_OK || !is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
                    continue;
                }
                $detailCount++;
                $filename = 'detail-' . $detailCount . '.jpg';
                $bin = crtlu_process_upload($tmp, false, 1600);
                crtlu_write_product_file($folder, $filename, $bin);
                $gallery[] = 'assets/products/' . $folder . '/' . $filename;
            }
        }

        $series = [
            'id' => $id,
            'brand' => $brand,
            'name' => $name,
            'category' => $category,
            'tier' => $tier !== '' ? $tier : 'main',
            'status' => $status,
            'detail_url' => '/products/' . $id . '/',
            'description' => $description !== '' ? $description : ($brand . ' ' . $name),
            'variants' => array_values($variants),
            'image' => $mainRel,
            'gallery' => array_values(array_unique($gallery)),
            'specs' => $specs,
        ];

        crtlu_replace_series($catalog, $series);
        crtlu_save_catalog($catalog);

        // Redirect to editor for further media work
        header('Location: product-edit.php?id=' . rawurlencode($id) . '&created=1');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $prefill = [
            'id' => trim((string)($_POST['id'] ?? '')),
            'name' => trim((string)($_POST['name'] ?? '')),
            'brand' => trim((string)($_POST['brand'] ?? '')),
            'category' => trim((string)($_POST['category'] ?? 'tv-box')),
            'tier' => trim((string)($_POST['tier'] ?? 'main')),
            'status' => trim((string)($_POST['status'] ?? 'published')),
            'description' => trim((string)($_POST['description'] ?? '')),
        ];
    }
}

$existingCategories = [];
foreach ($catalog['series'] ?? [] as $item) {
    $c = (string)($item['category'] ?? '');
    if ($c !== '') {
        $existingCategories[$c] = true;
    }
}
$categoryOptions = array_values(array_unique(array_merge(
    ['tv-box', 'projector', 'wireless-hdmi', 'accessory', 'premium'],
    array_keys($existingCategories)
)));
// Keep preferred order first
$preferredCats = ['tv-box', 'projector', 'wireless-hdmi', 'accessory', 'premium'];
usort($categoryOptions, static function (string $a, string $b) use ($preferredCats): int {
    $ia = array_search($a, $preferredCats, true);
    $ib = array_search($b, $preferredCats, true);
    $ia = $ia === false ? 99 : $ia;
    $ib = $ib === false ? 99 : $ib;
    return $ia === $ib ? strcmp($a, $b) : $ia <=> $ib;
});
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>上架新产品 | CRTLU Admin</title>
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
  --danger: #ff7777;
}
* { box-sizing: border-box; }
body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
main { width: min(920px, calc(100% - 28px)); margin: 0 auto; padding: 28px 0 60px; }
a { color: inherit; }
.topbar { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: flex-start; margin-bottom: 16px; }
h1 { margin: 0 0 6px; font-size: 28px; }
h2 { margin: 0 0 12px; font-size: 18px; }
.muted { color: var(--muted); font-size: 13px; line-height: 1.55; }
.links { display: flex; gap: 8px; flex-wrap: wrap; }
.btn, .links a {
  min-height: 36px; display: inline-flex; align-items: center; justify-content: center;
  padding: 0 12px; border: 1px solid rgba(255,255,255,.18); background: var(--panel);
  text-decoration: none; font-weight: 800; font-size: 12px; letter-spacing: .03em; text-transform: uppercase; color: #fff; cursor: pointer;
}
.btn.primary { background: linear-gradient(90deg, #7cff8c, var(--cyan)); color: #001014; border: 0; }
.panel { background: var(--panel); border: 1px solid var(--line); padding: 16px; margin-bottom: 14px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
label { display: grid; gap: 5px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
input, select, textarea {
  width: 100%; min-height: 36px; border: 1px solid rgba(255,255,255,.18); background: #071016; color: #fff; padding: 8px 10px; font: inherit;
}
textarea { min-height: 100px; resize: vertical; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; font-size: 13px; }
th { color: var(--green); font-size: 11px; text-transform: uppercase; }
.error { padding: 10px 12px; margin-bottom: 12px; border: 1px solid rgba(255,119,119,.4); color: #ffb8b8; background: rgba(255,80,80,.08); }
.hint { color: var(--muted); font-size: 12px; line-height: 1.55; margin-top: 8px; }
.row-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.steps { display: grid; gap: 6px; margin: 0 0 14px; padding: 12px 14px; border: 1px solid var(--line); background: rgba(93,231,255,.06); color: #c8d7df; font-size: 13px; line-height: 1.55; }
.steps strong { color: var(--cyan); }
.limit-pill {
  display: inline-flex; align-items: center; min-height: 22px; padding: 0 8px; margin-left: 6px;
  border-radius: 999px; background: rgba(255,180,80,.12); color: #ffb450; font-size: 11px; font-weight: 700;
}
@media (max-width: 800px) {
  .grid2 { grid-template-columns: 1fr; }
  table { display: block; overflow-x: auto; }
}
</style>
</head>
<body>
<main>
  <div class="topbar">
    <div>
      <h1>上架新产品</h1>
      <p class="muted">手动创建型号、规格价格，并可选上传主图/详情图。创建成功后进入编辑页继续完善。</p>
    </div>
    <nav class="links">
      <a href="products.php">← 产品列表</a>
      <a href="index.php">总览</a>
    </nav>
  </div>

  <?php if ($error): ?><div class="error"><?= crtlu_h($error) ?></div><?php endif; ?>

  <div class="steps">
    <div><strong>1.</strong> 填写名称、品牌、分类、描述与至少一个变体（售价 USD）。</div>
    <div><strong>2.</strong> 可选：上传主图（白底方图）与详情图（可多选）。</div>
    <div><strong>3.</strong> 提交后跳转到「编辑产品」，可继续改图、排序、删图、改规格。</div>
    <div><strong>定价提示：</strong> 落地成本 ≈ 采购 RMB + 日本运费 + 5；标准价 ≈ 落地 ÷ 7.20 ÷ 0.60；活动价可低约 $3–5。</div>
    <div>单文件上传上限：<span class="limit-pill"><?= crtlu_h(crtlu_upload_limit_label()) ?></span>（推荐用 <code>./admin/serve.sh</code> 启动后台）</div>
  </div>

  <form method="post" enctype="multipart/form-data" action="product-new.php">
    <section class="panel">
      <h2>基本信息</h2>
      <div class="grid2">
        <label>产品名称 *
          <input name="name" id="nameInput" value="<?= crtlu_h($prefill['name']) ?>" required placeholder="例如 Wireless HDMI WX50">
        </label>
        <label>品牌 *
          <input name="brand" value="<?= crtlu_h($prefill['brand']) ?>" required placeholder="例如 YS / MECOOL / H96">
        </label>
        <label>产品 ID（URL 用，英文小写-连字符）
          <input name="id" id="idInput" value="<?= crtlu_h($prefill['id']) ?>" placeholder="留空则根据名称自动生成，如 whdmi-wx50">
        </label>
        <label>分类
          <select name="category">
            <?php foreach ($categoryOptions as $c): ?>
              <option value="<?= crtlu_h($c) ?>" <?= $prefill['category'] === $c ? 'selected' : '' ?>><?= crtlu_h($c) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>层级 tier
          <input name="tier" value="<?= crtlu_h($prefill['tier']) ?>" placeholder="main / budget / flagship">
        </label>
        <label>状态
          <select name="status">
            <option value="published" <?= $prefill['status'] === 'published' ? 'selected' : '' ?>>published（前台显示）</option>
            <option value="draft" <?= $prefill['status'] === 'draft' ? 'selected' : '' ?>>draft（隐藏）</option>
          </select>
        </label>
      </div>
      <label style="margin-top:12px;">描述 description
        <textarea name="description" placeholder="英文描述，展示在产品页"><?= crtlu_h($prefill['description']) ?></textarea>
      </label>
      <p class="hint">ID 会用于前台路径 <code>/products/&lt;id&gt;/</code> 和图片目录 <code>assets/products/&lt;id&gt;/</code>。创建后不可在本页改 ID（可在 catalog 中手动改）。</p>
    </section>

    <section class="panel">
      <h2>规格 / 变体与价格（USD）</h2>
      <table>
        <thead>
          <tr>
            <th>变体 ID</th>
            <th>SKU</th>
            <th>规格标签</th>
            <th>售价 USD</th>
            <th>划线价 USD</th>
            <th>成本 RMB</th>
          </tr>
        </thead>
        <tbody id="variantBody">
          <tr>
            <td><input name="v_id[]" placeholder="可选，自动生成"></td>
            <td><input name="v_sku[]" placeholder="CRT-XXX-2G16G"></td>
            <td><input name="v_label[]" placeholder="2GB RAM + 16GB ROM" value="Standard"></td>
            <td><input name="v_price[]" type="number" step="0.01" min="0" value="49.99" required></td>
            <td><input name="v_compare[]" type="number" step="0.01" min="0" value="54.99"></td>
            <td><input name="v_rmb[]" type="number" step="0.01" min="0" placeholder="采购成本"></td>
          </tr>
        </tbody>
      </table>
      <div class="row-actions">
        <button class="btn" type="button" id="addVariant">+ 添加变体</button>
      </div>
    </section>

    <section class="panel">
      <h2>规格参数 specs（可选）</h2>
      <table>
        <thead><tr><th>参数名</th><th>参数值</th></tr></thead>
        <tbody id="specBody">
          <tr>
            <td><input name="spec_key[]" placeholder="Chipset"></td>
            <td><input name="spec_val[]" placeholder="Amlogic S905X4"></td>
          </tr>
          <tr>
            <td><input name="spec_key[]" placeholder="OS"></td>
            <td><input name="spec_val[]" placeholder="Android 14"></td>
          </tr>
        </tbody>
      </table>
      <div class="row-actions">
        <button class="btn" type="button" id="addSpec">+ 添加参数行</button>
      </div>
    </section>

    <section class="panel">
      <h2>图片（可选，可创建后再传）</h2>
      <div class="grid2">
        <label>主图（白底产品图）
          <input type="file" name="main_image" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif">
        </label>
        <label>详情 / 上架图（可多选）
          <input type="file" name="detail_images[]" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif" multiple>
        </label>
      </div>
      <p class="hint">主图会自动处理为白底方图。详情图保持介绍页比例。未上传也可先上架，之后在编辑页补图。</p>
    </section>

    <div class="row-actions">
      <button class="btn primary" type="submit">创建并上架</button>
      <a class="btn" href="products.php">取消</a>
    </div>
  </form>
</main>
<script>
(function () {
  const nameInput = document.getElementById('nameInput');
  const idInput = document.getElementById('idInput');
  let idTouched = false;
  idInput?.addEventListener('input', () => { idTouched = idInput.value.trim() !== ''; });
  nameInput?.addEventListener('input', () => {
    if (idTouched) return;
    const slug = nameInput.value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\u4e00-\u9fff]+/gi, '-')
      .replace(/[^a-z0-9\-]+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
    // If Chinese-only name collapses empty, leave blank for server-side unique id
    idInput.value = slug;
  });

  document.getElementById('addVariant')?.addEventListener('click', () => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input name="v_id[]" placeholder="可选"></td>
      <td><input name="v_sku[]" placeholder="SKU"></td>
      <td><input name="v_label[]" placeholder="规格标签"></td>
      <td><input name="v_price[]" type="number" step="0.01" min="0" value="0"></td>
      <td><input name="v_compare[]" type="number" step="0.01" min="0" value="0"></td>
      <td><input name="v_rmb[]" type="number" step="0.01" min="0"></td>`;
    document.getElementById('variantBody').appendChild(tr);
  });

  document.getElementById('addSpec')?.addEventListener('click', () => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input name="spec_key[]" placeholder="参数名"></td>
      <td><input name="spec_val[]" placeholder="参数值"></td>`;
    document.getElementById('specBody').appendChild(tr);
  });
})();
</script>
</body>
</html>
