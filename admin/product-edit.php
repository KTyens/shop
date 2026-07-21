<?php

require __DIR__ . '/auth.php';
require __DIR__ . '/catalog-lib.php';

crtlu_require_admin();

$id = trim((string)($_GET['id'] ?? $_POST['id'] ?? ''));
if ($id === '') {
    http_response_code(400);
    echo '缺少产品 ID';
    exit;
}

$catalog = crtlu_load_catalog();
$series = crtlu_find_series($catalog, $id);
if ($series === null) {
    http_response_code(404);
    echo '未找到产品：' . crtlu_h($id);
    exit;
}

$message = '';
$error = '';
if (isset($_GET['created'])) {
    $message = '产品已创建成功。可在下方上传/调整主图与详情图，并完善规格参数。';
}

function crtlu_parse_variants_from_post(): array
{
    $ids = $_POST['v_id'] ?? [];
    $skus = $_POST['v_sku'] ?? [];
    $labels = $_POST['v_label'] ?? [];
    $prices = $_POST['v_price'] ?? [];
    $compares = $_POST['v_compare'] ?? [];
    $rmbs = $_POST['v_rmb'] ?? [];
    if (!is_array($ids)) {
        return [];
    }
    $out = [];
    foreach ($ids as $i => $vid) {
        $vid = trim((string)$vid);
        $label = trim((string)($labels[$i] ?? ''));
        $sku = trim((string)($skus[$i] ?? ''));
        if ($vid === '' && $label === '' && $sku === '') {
            continue;
        }
        if ($vid === '') {
            $vid = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $sku ?: $label) ?? '') ?: ('var-' . ($i + 1));
        }
        $price = (float)str_replace(',', '', (string)($prices[$i] ?? '0'));
        $compare = (float)str_replace(',', '', (string)($compares[$i] ?? '0'));
        $rmb = (float)str_replace(',', '', (string)($rmbs[$i] ?? '0'));
        $row = [
            'id' => $vid,
            'sku' => $sku !== '' ? $sku : strtoupper($vid),
            'label' => $label !== '' ? $label : $sku,
            'price_cents' => (int)round($price * 100),
            'compare_at_cents' => (int)round($compare * 100),
        ];
        if ($rmb > 0) {
            $row['rmb_price'] = $rmb;
        }
        $out[] = $row;
    }
    return $out;
}

function crtlu_parse_specs_from_post(): array
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
    $action = (string)($_POST['action'] ?? 'save');
    try {
        $folder = crtlu_series_folder($series);

        if ($action === 'save_info') {
            $series['name'] = trim((string)($_POST['name'] ?? $series['name'] ?? ''));
            $series['brand'] = trim((string)($_POST['brand'] ?? $series['brand'] ?? ''));
            $series['category'] = trim((string)($_POST['category'] ?? $series['category'] ?? ''));
            $series['tier'] = trim((string)($_POST['tier'] ?? $series['tier'] ?? ''));
            $series['status'] = trim((string)($_POST['status'] ?? $series['status'] ?? 'published'));
            $series['description'] = trim((string)($_POST['description'] ?? $series['description'] ?? ''));
            if ($series['name'] === '') {
                throw new RuntimeException('产品名称不能为空。');
            }
            $series['variants'] = crtlu_parse_variants_from_post();
            if (!$series['variants']) {
                throw new RuntimeException('至少保留一个规格/变体。');
            }
            $series['specs'] = crtlu_parse_specs_from_post();
            $series = crtlu_rebuild_gallery($series);
            crtlu_replace_series($catalog, $series);
            crtlu_save_catalog($catalog);
            $message = '基本信息、规格与价格已保存。';
        } elseif ($action === 'upload_main') {
            $file = $_FILES['main_image'] ?? null;
            if (!is_array($file)) {
                throw new RuntimeException(
                    '未收到上传数据。若文件很大，可能超过 PHP 限制（当前 ' . crtlu_upload_limit_label() . '）。请用 admin/serve.sh 启动后台。'
                );
            }
            $tmp = crtlu_require_uploaded_file($file, '主图');
            $bin = crtlu_process_upload($tmp, true, 1400);
            crtlu_write_product_file($folder, 'main.jpg', $bin);
            $series = crtlu_rebuild_gallery($series);
            crtlu_replace_series($catalog, $series);
            crtlu_save_catalog($catalog);
            $message = '主图已替换（白底方图）。前台将通过实时 catalog（/api/store-catalog.php）立刻读到 api 上的新图。';
        } elseif ($action === 'upload_details') {
            $files = $_FILES['detail_images'] ?? null;
            if (!$files || !is_array($files['tmp_name'] ?? null)) {
                throw new RuntimeException(
                    '未收到详情图。若文件很大，可能超过 PHP 限制（当前 ' . crtlu_upload_limit_label() . '）。'
                );
            }
            $count = 0;
            $skipped = [];
            $tmps = $files['tmp_name'] ?? [];
            $errs = $files['error'] ?? [];
            $names = $files['name'] ?? [];
            // Normalize single-file upload shape
            if (!is_array($tmps)) {
                $tmps = [$tmps];
                $errs = [$errs];
                $names = [$names];
            }
            foreach ($tmps as $i => $tmp) {
                $err = (int)($errs[$i] ?? UPLOAD_ERR_NO_FILE);
                $label = (string)($names[$i] ?? ('文件' . ($i + 1)));
                if ($err === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                if ($err !== UPLOAD_ERR_OK || !is_string($tmp) || $tmp === '' || !is_uploaded_file($tmp)) {
                    $skipped[] = $label . '：' . crtlu_upload_error_message($err, '详情图');
                    continue;
                }
                try {
                    $idx = crtlu_next_detail_index($series);
                    $filename = 'detail-' . $idx . '.jpg';
                    $bin = crtlu_process_upload($tmp, false, 1600);
                    crtlu_write_product_file($folder, $filename, $bin);
                    $rel = 'assets/products/' . $folder . '/' . $filename;
                    $series['gallery'] = array_values(array_unique(array_merge(
                        is_array($series['gallery'] ?? null) ? $series['gallery'] : [],
                        [$rel]
                    )));
                    $count++;
                } catch (Throwable $uploadErr) {
                    $skipped[] = $label . '：' . $uploadErr->getMessage();
                }
            }
            if ($count === 0) {
                $extra = $skipped ? (' ' . implode(' ', $skipped)) : '';
                throw new RuntimeException('没有成功上传的详情图。' . $extra);
            }
            $series = crtlu_rebuild_gallery($series);
            crtlu_replace_series($catalog, $series);
            crtlu_save_catalog($catalog);
            $message = "已上传 {$count} 张详情图。前台实时 catalog 会立刻指向 api 上的新图。";
            if ($skipped) {
                $message .= ' 部分失败：' . implode(' ', $skipped);
            }
        } elseif ($action === 'set_main') {
            $src = trim((string)($_POST['image'] ?? ''));
            if ($src === '' || !str_contains($src, 'assets/products/' . $folder . '/')) {
                throw new RuntimeException('无效的图片路径。');
            }
            $srcFile = basename($src);
            $srcPath = crtlu_shop_root() . '/public/assets/products/' . $folder . '/' . $srcFile;
            if (!is_file($srcPath)) {
                $srcPath = crtlu_shop_root() . '/assets/products/' . $folder . '/' . $srcFile;
            }
            if (!is_file($srcPath)) {
                throw new RuntimeException('源图片不存在。');
            }
            $bin = crtlu_process_upload($srcPath, true, 1400);
            crtlu_write_product_file($folder, 'main.jpg', $bin);
            // If source was a detail image, keep it in gallery as detail; main is separate
            $series = crtlu_rebuild_gallery($series);
            crtlu_replace_series($catalog, $series);
            crtlu_save_catalog($catalog);
            $message = '已将该图设为主图。前台实时 catalog 会立刻更新。';
        } elseif ($action === 'delete_product') {
            $confirm = trim((string)($_POST['confirm_id'] ?? ''));
            if ($confirm !== $id) {
                throw new RuntimeException('请在确认框输入完整产品 ID「' . $id . '」后再删除。');
            }
            $folderToDelete = crtlu_series_folder($series);
            if (!crtlu_remove_series($catalog, $id)) {
                throw new RuntimeException('catalog 中未找到该产品，可能已被删除。');
            }
            crtlu_save_catalog($catalog);
            crtlu_delete_product_folder($folderToDelete);
            header('Location: products.php?deleted=' . rawurlencode($id));
            exit;
        } elseif ($action === 'delete_image') {
            $src = trim((string)($_POST['image'] ?? ''));
            if ($src === '' || str_ends_with($src, '/main.jpg')) {
                throw new RuntimeException('主图不能直接删除，请先上传新主图覆盖。');
            }
            if (!str_contains($src, 'assets/products/' . $folder . '/')) {
                throw new RuntimeException('无效的图片路径。');
            }
            $filename = basename($src);
            crtlu_delete_product_file($folder, $filename);
            $series['gallery'] = array_values(array_filter(
                is_array($series['gallery'] ?? null) ? $series['gallery'] : [],
                static fn($g) => (string)$g !== $src
            ));
            $series = crtlu_rebuild_gallery($series);
            crtlu_replace_series($catalog, $series);
            crtlu_save_catalog($catalog);
            $message = '已删除图片：' . $filename;
        } elseif ($action === 'delete_selected') {
            $selected = $_POST['selected_images'] ?? [];
            if (!is_array($selected) || !$selected) {
                throw new RuntimeException('请先勾选要删除的详情图。');
            }
            $mainRel = 'assets/products/' . $folder . '/main.jpg';
            $gallery = is_array($series['gallery'] ?? null) ? $series['gallery'] : [];
            $deleted = [];
            $skipped = [];
            foreach ($selected as $src) {
                $src = trim((string)$src);
                if ($src === '' || $src === $mainRel || str_ends_with($src, '/main.jpg')) {
                    $skipped[] = basename($src) . '（主图不可删）';
                    continue;
                }
                if (!str_contains($src, 'assets/products/' . $folder . '/')) {
                    $skipped[] = basename($src) . '（路径无效）';
                    continue;
                }
                $filename = basename($src);
                if ($filename === 'main.jpg' || str_starts_with($filename, 'main.')) {
                    $skipped[] = $filename . '（主图不可删）';
                    continue;
                }
                crtlu_delete_product_file($folder, $filename);
                $gallery = array_values(array_filter(
                    $gallery,
                    static fn($g) => (string)$g !== $src
                ));
                $deleted[] = $filename;
            }
            if (!$deleted) {
                throw new RuntimeException('没有成功删除的图片。' . ($skipped ? ' ' . implode('；', $skipped) : ''));
            }
            $series['gallery'] = $gallery;
            $series = crtlu_rebuild_gallery($series);
            crtlu_replace_series($catalog, $series);
            crtlu_save_catalog($catalog);
            $message = '已删除选中详情图 ' . count($deleted) . ' 张：' . implode('、', $deleted) . '。';
            if ($skipped) {
                $message .= ' 跳过：' . implode('；', $skipped);
            }
        } elseif ($action === 'delete_all_details') {
            $gallery = is_array($series['gallery'] ?? null) ? $series['gallery'] : [];
            $mainRel = 'assets/products/' . $folder . '/main.jpg';
            $toDelete = [];
            foreach ($gallery as $path) {
                $path = (string)$path;
                if ($path === '' || $path === $mainRel || str_ends_with($path, '/main.jpg')) {
                    continue;
                }
                if (!str_contains($path, 'assets/products/' . $folder . '/')) {
                    continue;
                }
                $filename = basename($path);
                if ($filename === 'main.jpg' || str_starts_with($filename, 'main.')) {
                    continue;
                }
                $toDelete[$filename] = true;
            }
            // Sweep leftover detail-*.jpg on disk that may not be in gallery
            foreach (crtlu_product_image_roots() as $root) {
                $dir = rtrim($root, '/') . '/' . $folder;
                if (!is_dir($dir)) {
                    continue;
                }
                foreach (glob($dir . '/detail-*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [] as $filePath) {
                    $toDelete[basename((string)$filePath)] = true;
                }
                // plain glob fallback if GLOB_BRACE unavailable
                foreach (glob($dir . '/detail-*.jpg') ?: [] as $filePath) {
                    $toDelete[basename((string)$filePath)] = true;
                }
            }
            foreach (array_keys($toDelete) as $filename) {
                crtlu_delete_product_file($folder, $filename);
            }
            $deleted = count($toDelete);
            $series['image'] = $mainRel;
            $series['gallery'] = [$mainRel];
            crtlu_replace_series($catalog, $series);
            crtlu_save_catalog($catalog);
            $message = $deleted > 0
                ? "已一键删除全部详情图（{$deleted} 张），主图已保留。"
                : '没有可删除的详情图（主图已保留）。';
        } elseif ($action === 'reorder') {
            $order = $_POST['order'] ?? [];
            if (!is_array($order) || !$order) {
                throw new RuntimeException('排序数据无效。');
            }
            $main = 'assets/products/' . $folder . '/main.jpg';
            $newGallery = [$main];
            foreach ($order as $path) {
                $path = (string)$path;
                if ($path === '' || $path === $main) {
                    continue;
                }
                if (!str_contains($path, 'assets/products/' . $folder . '/')) {
                    continue;
                }
                if (!in_array($path, $newGallery, true)) {
                    $newGallery[] = $path;
                }
            }
            // keep any existing gallery items not listed (safety)
            foreach ($series['gallery'] ?? [] as $path) {
                $path = (string)$path;
                if ($path !== '' && !in_array($path, $newGallery, true) && str_contains($path, 'assets/products/' . $folder . '/')) {
                    $newGallery[] = $path;
                }
            }
            $series['image'] = $main;
            $series['gallery'] = $newGallery;
            crtlu_replace_series($catalog, $series);
            crtlu_save_catalog($catalog);
            $message = '图片顺序已保存。';
        } else {
            throw new RuntimeException('未知操作。');
        }

        // reload
        $catalog = crtlu_load_catalog();
        $series = crtlu_find_series($catalog, $id) ?? $series;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $catalog = crtlu_load_catalog();
        $series = crtlu_find_series($catalog, $id) ?? $series;
    }
}

$folder = crtlu_series_folder($series);
$gallery = is_array($series['gallery'] ?? null) ? $series['gallery'] : [];
$variants = is_array($series['variants'] ?? null) ? $series['variants'] : [];
$specs = is_array($series['specs'] ?? null) ? $series['specs'] : [];
// Ensure at least one empty row for adding
if (!$variants) {
    $variants = [['id' => '', 'sku' => '', 'label' => '', 'price_cents' => 0, 'compare_at_cents' => 0, 'rmb_price' => 0]];
}
// detail-only list for reorder (exclude main duplicates for UI but keep main badge)
$detailItems = [];
foreach ($gallery as $g) {
    $g = (string)$g;
    if ($g === '' || str_ends_with($g, '/main.jpg')) {
        continue;
    }
    $detailItems[] = $g;
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>编辑 <?= crtlu_h((string)($series['name'] ?? $id)) ?> | CRTLU Admin</title>
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
main { width: min(1180px, calc(100% - 28px)); margin: 0 auto; padding: 28px 0 60px; }
a { color: inherit; }
.topbar { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; align-items: flex-start; margin-bottom: 16px; }
h1 { margin: 0 0 6px; font-size: 28px; line-height: 1.15; }
h2 { margin: 0 0 12px; font-size: 18px; }
.muted { color: var(--muted); font-size: 13px; line-height: 1.55; }
.links, .actions { display: flex; gap: 8px; flex-wrap: wrap; }
.btn, .links a {
  min-height: 36px; display: inline-flex; align-items: center; justify-content: center;
  padding: 0 12px; border: 1px solid rgba(255,255,255,.18); background: var(--panel);
  text-decoration: none; font-weight: 800; font-size: 12px; letter-spacing: .03em; text-transform: uppercase; color: #fff; cursor: pointer;
}
.btn.primary, button.primary { background: linear-gradient(90deg, #7cff8c, var(--cyan)); color: #001014; border: 0; }
.btn.danger, button.danger { color: var(--danger); }
.panel { background: var(--panel); border: 1px solid var(--line); padding: 16px; margin-bottom: 14px; }
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.grid3 { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 12px; }
label { display: grid; gap: 5px; color: var(--muted); font-size: 11px; text-transform: uppercase; letter-spacing: .05em; }
input, select, textarea {
  width: 100%; min-height: 36px; border: 1px solid rgba(255,255,255,.18); background: #071016; color: #fff; padding: 8px 10px; font: inherit;
}
textarea { min-height: 110px; resize: vertical; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 8px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; font-size: 13px; }
th { color: var(--green); font-size: 11px; text-transform: uppercase; }
.message { padding: 10px 12px; margin-bottom: 12px; border: 1px solid rgba(139,255,133,.35); color: var(--green); background: rgba(139,255,133,.08); }
.error { padding: 10px 12px; margin-bottom: 12px; border: 1px solid rgba(255,119,119,.4); color: #ffb8b8; background: rgba(255,80,80,.08); }
.main-preview {
  width: min(280px, 100%); aspect-ratio: 1; background: #fff; border: 1px solid var(--line); display: grid; place-items: center; overflow: hidden;
}
.main-preview img { width: 100%; height: 100%; object-fit: contain; }
.gallery {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;
}
.gcard {
  border: 1px solid var(--line); background: #0a141b; overflow: hidden; display: grid; grid-template-rows: 140px auto;
}
.gcard .img { background: #fff; display: grid; place-items: center; overflow: hidden; }
.gcard img { width: 100%; height: 100%; object-fit: contain; }
.gcard .meta { padding: 8px; display: grid; gap: 6px; }
.gcard .meta .name { color: var(--muted); font-size: 11px; word-break: break-all; }
.gcard form { display: flex; gap: 4px; flex-wrap: wrap; }
.gcard button, .gcard .btn { min-height: 28px; font-size: 10px; padding: 0 8px; }
.gcard .actions-row { display: flex; gap: 4px; flex-wrap: wrap; align-items: center; }
.gcard { position: relative; }
.gcard.selected { border-color: rgba(93,231,255,.65); box-shadow: 0 0 0 1px rgba(93,231,255,.35); }
.gcard .pick {
  position: absolute; top: 8px; left: 8px; z-index: 2;
  display: inline-flex; align-items: center; gap: 6px;
  min-height: 28px; padding: 0 8px; border-radius: 6px;
  background: rgba(0,0,0,.72); color: #fff; font-size: 11px; font-weight: 700;
  cursor: pointer; user-select: none;
}
.gcard .pick input { width: 15px; height: 15px; accent-color: #5de7ff; cursor: pointer; }
.bulk-bar {
  display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
  margin: 0 0 12px; padding: 10px 12px; border: 1px solid var(--line); background: rgba(93,231,255,.06);
}
.bulk-bar .count { color: var(--cyan); font-weight: 800; font-size: 13px; min-width: 7em; }
.badge { display: inline-block; padding: 2px 7px; border-radius: 999px; background: rgba(93,231,255,.15); color: var(--cyan); font-size: 10px; font-weight: 800; }
.row-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 10px; }
.hint { color: var(--muted); font-size: 12px; line-height: 1.55; margin-top: 8px; }
.limit-pill {
  display: inline-flex; align-items: center; min-height: 24px; padding: 0 8px; margin-left: 6px;
  border-radius: 999px; background: rgba(255,180,80,.12); color: #ffb450; font-size: 11px; font-weight: 700;
}
@media (max-width: 800px) {
  .grid2, .grid3 { grid-template-columns: 1fr; }
  table { display: block; overflow-x: auto; }
}
</style>
</head>
<body>
<main>
  <div class="topbar">
    <div>
      <h1>编辑：<?= crtlu_h((string)($series['name'] ?? $id)) ?></h1>
      <p class="muted">ID: <?= crtlu_h($id) ?> · 图片文件夹: <code><?= crtlu_h($folder) ?></code></p>
    </div>
    <nav class="links">
      <a href="products.php">← 产品列表</a>
      <a href="product-new.php">+ 上架产品</a>
      <a href="index.php">总览</a>
      <a href="<?= crtlu_h(crtlu_storefront_product_url($id)) ?>" target="_blank" rel="noopener">前台预览</a>
    </nav>
  </div>

  <?php if ($message): ?><div class="message"><?= crtlu_h($message) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="error"><?= crtlu_h($error) ?></div><?php endif; ?>

  <!-- Main image -->
  <section class="panel">
    <h2>主图（白底产品图） <span class="limit-pill">单文件 ≤ <?= crtlu_h(crtlu_upload_limit_label()) ?></span></h2>
    <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:flex-start;">
      <div class="main-preview">
        <img src="<?= crtlu_h(crtlu_local_asset_url((string)($series['image'] ?? ''))) ?>" alt="main">
      </div>
      <div style="flex:1;min-width:240px;">
        <form method="post" enctype="multipart/form-data" action="product-edit.php?id=<?= urlencode($id) ?>">
          <input type="hidden" name="id" value="<?= crtlu_h($id) ?>">
          <input type="hidden" name="action" value="upload_main">
          <label>上传新主图
            <input type="file" name="main_image" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif" required>
          </label>
          <p class="hint">
            会自动铺到白底方图（约 1400×1400）。支持 JPG/PNG/WebP/GIF。
            若提示文件过大，请用项目里的 <code>admin/serve.sh</code> 启动后台（上传上限 64M），不要用默认 2M 的 <code>php -S</code>。<br>
            <?= crtlu_h(crtlu_storefront_sync_hint()) ?>
          </p>
          <div class="row-actions">
            <button class="btn primary" type="submit">替换主图</button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- Gallery details -->
  <section class="panel">
    <h2>详情 / 上架图（介绍图） <span class="limit-pill">单文件 ≤ <?= crtlu_h(crtlu_upload_limit_label()) ?></span></h2>
    <p class="muted">可<strong>多选</strong>后批量删除；也可单张删除 / 上移下移 / 设为主图；或一键清空全部详情图（主图不受影响）。</p>

    <form method="post" enctype="multipart/form-data" action="product-edit.php?id=<?= urlencode($id) ?>" style="margin:12px 0 16px;">
      <input type="hidden" name="id" value="<?= crtlu_h($id) ?>">
      <input type="hidden" name="action" value="upload_details">
      <label>批量上传详情图
        <input type="file" name="detail_images[]" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif" multiple required>
      </label>
      <div class="row-actions">
        <button class="btn primary" type="submit">上传详情图</button>
        <?php if ($detailItems): ?>
          <button
            class="btn danger"
            type="submit"
            form="delete-all-details"
            formaction="product-edit.php?id=<?= urlencode($id) ?>"
            onclick="return confirm('确定删除本产品全部详情图？\n共 <?= count($detailItems) ?> 张，主图会保留。此操作不可撤销。');"
          >一键删除全部详情图（<?= count($detailItems) ?>）</button>
        <?php endif; ?>
      </div>
    </form>
    <?php if ($detailItems): ?>
      <form id="delete-all-details" method="post" action="product-edit.php?id=<?= urlencode($id) ?>" style="display:none;">
        <input type="hidden" name="id" value="<?= crtlu_h($id) ?>">
        <input type="hidden" name="action" value="delete_all_details">
      </form>
    <?php endif; ?>

    <?php if (!$detailItems): ?>
      <p class="muted">暂无详情图。上传后会出现在下方，可勾选多选删除，或单张删除 / 一键清空。</p>
    <?php else: ?>
      <form method="post" id="reorderForm" action="product-edit.php?id=<?= urlencode($id) ?>">
        <input type="hidden" name="id" value="<?= crtlu_h($id) ?>">
        <input type="hidden" name="action" value="reorder" id="reorderAction">

        <div class="bulk-bar" id="bulkBar">
          <label style="display:inline-flex;align-items:center;gap:8px;color:var(--muted);font-size:12px;text-transform:none;letter-spacing:0;min-height:auto;">
            <input type="checkbox" id="selectAllDetails" style="width:16px;height:16px;accent-color:#5de7ff;">
            全选
          </label>
          <button class="btn" type="button" id="selectNoneDetails">取消全选</button>
          <button class="btn" type="button" id="invertSelectDetails">反选</button>
          <span class="count" id="selectedCount">已选 0 张</span>
          <button
            class="btn danger"
            type="submit"
            id="deleteSelectedBtn"
            formaction="product-edit.php?id=<?= urlencode($id) ?>"
            disabled
          >删除选中</button>
        </div>

        <div class="gallery" id="gallerySortable">
          <?php foreach ($detailItems as $idx => $path):
            $file = basename((string)$path);
            $pathEsc = crtlu_h((string)$path);
          ?>
            <div class="gcard" data-path="<?= $pathEsc ?>">
              <label class="pick">
                <input type="checkbox" name="selected_images[]" value="<?= $pathEsc ?>" class="detail-check">
                选择
              </label>
              <div class="img"><img src="<?= crtlu_h(crtlu_local_asset_url((string)$path)) ?>" alt="<?= crtlu_h($file) ?>" loading="lazy"></div>
              <div class="meta">
                <div class="name">#<?= $idx + 1 ?> · <?= crtlu_h($file) ?></div>
                <input type="hidden" name="order[]" value="<?= $pathEsc ?>">
                <div class="actions-row">
                  <button class="btn" type="button" data-move="-1" title="上移">上移</button>
                  <button class="btn" type="button" data-move="1" title="下移">下移</button>
                  <button class="btn" type="submit" form="set-main-<?= $idx ?>" formaction="product-edit.php?id=<?= urlencode($id) ?>">设为主图</button>
                  <button class="btn danger" type="submit" form="del-img-<?= $idx ?>" formaction="product-edit.php?id=<?= urlencode($id) ?>" onclick="return confirm('确定删除这张详情图？');">删除</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="row-actions">
          <button class="btn primary" type="submit" id="saveOrderBtn" formaction="product-edit.php?id=<?= urlencode($id) ?>">保存图片顺序</button>
          <button
            class="btn danger"
            type="submit"
            form="delete-all-details"
            formaction="product-edit.php?id=<?= urlencode($id) ?>"
            onclick="return confirm('确定删除本产品全部详情图？\n共 <?= count($detailItems) ?> 张，主图会保留。此操作不可撤销。');"
          >一键删除全部详情图（<?= count($detailItems) ?>）</button>
        </div>
      </form>
      <?php foreach ($detailItems as $idx => $path): ?>
        <form id="set-main-<?= $idx ?>" method="post" action="product-edit.php?id=<?= urlencode($id) ?>" style="display:none;">
          <input type="hidden" name="id" value="<?= crtlu_h($id) ?>">
          <input type="hidden" name="action" value="set_main">
          <input type="hidden" name="image" value="<?= crtlu_h((string)$path) ?>">
        </form>
        <form id="del-img-<?= $idx ?>" method="post" action="product-edit.php?id=<?= urlencode($id) ?>" style="display:none;">
          <input type="hidden" name="id" value="<?= crtlu_h($id) ?>">
          <input type="hidden" name="action" value="delete_image">
          <input type="hidden" name="image" value="<?= crtlu_h((string)$path) ?>">
        </form>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <!-- Info / variants / specs -->
  <section class="panel">
    <h2>基本信息 · 价格 · 规格参数</h2>
    <form method="post">
      <input type="hidden" name="id" value="<?= crtlu_h($id) ?>">
      <input type="hidden" name="action" value="save_info">

      <div class="grid2" style="margin-bottom:12px;">
        <label>名称
          <input name="name" value="<?= crtlu_h((string)($series['name'] ?? '')) ?>" required>
        </label>
        <label>品牌
          <input name="brand" value="<?= crtlu_h((string)($series['brand'] ?? '')) ?>">
        </label>
        <label>分类
          <select name="category">
            <?php
              $cat = (string)($series['category'] ?? 'tv-box');
              $catOptions = [
                'tv-box' => 'tv-box（电视盒子）',
                'projector' => 'projector（投影仪）',
                'wireless-hdmi' => 'wireless-hdmi（无线HDMI）',
                'accessory' => 'accessory（配件）',
                'premium' => 'premium',
              ];
              if ($cat !== '' && !isset($catOptions[$cat])) {
                $catOptions[$cat] = $cat;
              }
              foreach ($catOptions as $val => $label):
            ?>
              <option value="<?= crtlu_h($val) ?>" <?= $cat === $val ? 'selected' : '' ?>><?= crtlu_h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>层级 tier
          <input name="tier" value="<?= crtlu_h((string)($series['tier'] ?? '')) ?>" placeholder="main / budget / flagship">
        </label>
        <label>状态
          <select name="status">
            <?php $st = (string)($series['status'] ?? 'published'); ?>
            <option value="published" <?= $st === 'published' ? 'selected' : '' ?>>published（前台显示）</option>
            <option value="draft" <?= $st === 'draft' ? 'selected' : '' ?>>draft（隐藏）</option>
          </select>
        </label>
      </div>
      <label style="margin-bottom:14px;">描述 description
        <textarea name="description"><?= crtlu_h((string)($series['description'] ?? '')) ?></textarea>
      </label>

      <h2 style="margin-top:8px;">变体 / 价格（USD 用小数，如 49.99）</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>SKU</th>
            <th>规格标签</th>
            <th>售价 USD</th>
            <th>划线价 USD</th>
            <th>成本 RMB</th>
          </tr>
        </thead>
        <tbody id="variantBody">
          <?php foreach ($variants as $v): ?>
            <tr>
              <td><input name="v_id[]" value="<?= crtlu_h((string)($v['id'] ?? '')) ?>"></td>
              <td><input name="v_sku[]" value="<?= crtlu_h((string)($v['sku'] ?? '')) ?>"></td>
              <td><input name="v_label[]" value="<?= crtlu_h((string)($v['label'] ?? '')) ?>"></td>
              <td><input name="v_price[]" type="number" step="0.01" min="0" value="<?= crtlu_h(crtlu_money_from_cents($v['price_cents'] ?? 0)) ?>"></td>
              <td><input name="v_compare[]" type="number" step="0.01" min="0" value="<?= crtlu_h(crtlu_money_from_cents($v['compare_at_cents'] ?? $v['price_cents'] ?? 0)) ?>"></td>
              <td><input name="v_rmb[]" type="number" step="0.01" min="0" value="<?= crtlu_h((string)($v['rmb_price'] ?? '')) ?>"></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="row-actions">
        <button class="btn" type="button" id="addVariant">+ 添加变体行</button>
      </div>

      <h2 style="margin-top:22px;">规格参数 specs</h2>
      <table>
        <thead>
          <tr><th>参数名</th><th>参数值</th></tr>
        </thead>
        <tbody id="specBody">
          <?php
            if (!$specs) {
                $specs = ['' => ''];
            }
            foreach ($specs as $sk => $sv):
          ?>
            <tr>
              <td><input name="spec_key[]" value="<?= crtlu_h((string)$sk) ?>" placeholder="Chipset"></td>
              <td><input name="spec_val[]" value="<?= crtlu_h((string)$sv) ?>" placeholder="Allwinner H618"></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="row-actions">
        <button class="btn" type="button" id="addSpec">+ 添加参数行</button>
        <button class="btn primary" type="submit">保存信息 / 价格 / 规格</button>
      </div>
      <p class="hint">空的参数行会忽略。名称/价格/规格/图片改完后，前台通过 <code>/api/store-catalog.php</code> 读 Serv00 实时 catalog（刷新详情页/列表即可，不必等 Git 推送）。静态 SEO 页长期归档仍建议 build + push。</p>
    </form>
  </section>

  <section class="panel" style="border-color:rgba(255,100,100,.35);">
    <h2 style="color:var(--danger,#ff8f8f);">危险操作 · 删除产品</h2>
    <p class="muted">
      将从 <code>data/catalog.json</code> 移除本型号，并尽量删除本机
      <code>assets/products/<?= crtlu_h($folder) ?>/</code> 图片目录（Serv00 上的覆盖图）。
      Cloudflare 上已发布的静态页/图不会自动删掉，需之后 Git 同步清理。
    </p>
    <form method="post" action="product-edit.php?id=<?= urlencode($id) ?>" onsubmit="return confirm('确定永久删除产品 <?= crtlu_h($id) ?>？\n此操作会从 catalog 移除该型号。');">
      <input type="hidden" name="id" value="<?= crtlu_h($id) ?>">
      <input type="hidden" name="action" value="delete_product">
      <label>输入产品 ID 确认删除
        <input type="text" name="confirm_id" placeholder="<?= crtlu_h($id) ?>" autocomplete="off" required style="min-width:min(100%,280px);">
      </label>
      <div class="row-actions" style="margin-top:12px;">
        <button class="btn danger" type="submit">删除此产品</button>
      </div>
    </form>
  </section>
</main>
<script>
(function () {
  const gallery = document.getElementById('gallerySortable');
  const actionInput = document.getElementById('reorderAction');
  const form = document.getElementById('reorderForm');
  const selectAll = document.getElementById('selectAllDetails');
  const selectNone = document.getElementById('selectNoneDetails');
  const invertSelect = document.getElementById('invertSelectDetails');
  const selectedCount = document.getElementById('selectedCount');
  const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
  const saveOrderBtn = document.getElementById('saveOrderBtn');

  function checks() {
    return [...document.querySelectorAll('.detail-check')];
  }

  function updateSelectionUi() {
    const list = checks();
    const n = list.filter((c) => c.checked).length;
    if (selectedCount) selectedCount.textContent = '已选 ' + n + ' 张';
    if (deleteSelectedBtn) deleteSelectedBtn.disabled = n === 0;
    if (selectAll) selectAll.checked = list.length > 0 && n === list.length;
    list.forEach((c) => {
      const card = c.closest('.gcard');
      if (card) card.classList.toggle('selected', c.checked);
    });
  }

  if (gallery) {
    gallery.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-move]');
      if (!btn) return;
      const card = btn.closest('.gcard');
      if (!card) return;
      const delta = Number(btn.getAttribute('data-move') || 0);
      const cards = [...gallery.querySelectorAll('.gcard')];
      const idx = cards.indexOf(card);
      const target = idx + delta;
      if (target < 0 || target >= cards.length) return;
      if (delta < 0) {
        gallery.insertBefore(card, cards[target]);
      } else {
        gallery.insertBefore(cards[target], card);
      }
      // re-number labels
      [...gallery.querySelectorAll('.gcard')].forEach((c, i) => {
        const name = c.querySelector('.name');
        if (!name) return;
        const file = (c.dataset.path || '').split('/').pop();
        name.textContent = '#' + (i + 1) + ' · ' + file;
      });
    });

    gallery.addEventListener('change', (e) => {
      if (e.target && e.target.classList && e.target.classList.contains('detail-check')) {
        updateSelectionUi();
      }
    });
  }

  selectAll?.addEventListener('change', () => {
    const on = !!selectAll.checked;
    checks().forEach((c) => { c.checked = on; });
    updateSelectionUi();
  });
  selectNone?.addEventListener('click', () => {
    checks().forEach((c) => { c.checked = false; });
    updateSelectionUi();
  });
  invertSelect?.addEventListener('click', () => {
    checks().forEach((c) => { c.checked = !c.checked; });
    updateSelectionUi();
  });

  saveOrderBtn?.addEventListener('click', () => {
    if (actionInput) actionInput.value = 'reorder';
  });

  deleteSelectedBtn?.addEventListener('click', (e) => {
    const n = checks().filter((c) => c.checked).length;
    if (n === 0) {
      e.preventDefault();
      alert('请先勾选要删除的详情图。');
      return;
    }
    if (!confirm('确定删除选中的 ' + n + ' 张详情图？主图会保留。此操作不可撤销。')) {
      e.preventDefault();
      return;
    }
    if (actionInput) actionInput.value = 'delete_selected';
  });

  form?.addEventListener('submit', () => {
    // default keep current actionInput value
  });

  updateSelectionUi();

  document.getElementById('addVariant')?.addEventListener('click', () => {
    const body = document.getElementById('variantBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input name="v_id[]" value=""></td>
      <td><input name="v_sku[]" value=""></td>
      <td><input name="v_label[]" value=""></td>
      <td><input name="v_price[]" type="number" step="0.01" min="0" value="0"></td>
      <td><input name="v_compare[]" type="number" step="0.01" min="0" value="0"></td>
      <td><input name="v_rmb[]" type="number" step="0.01" min="0" value=""></td>`;
    body.appendChild(tr);
  });

  document.getElementById('addSpec')?.addEventListener('click', () => {
    const body = document.getElementById('specBody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input name="spec_key[]" value="" placeholder="Chipset"></td>
      <td><input name="spec_val[]" value="" placeholder="Allwinner H618"></td>`;
    body.appendChild(tr);
  });
})();
</script>
</body>
</html>
