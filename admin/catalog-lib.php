<?php

/**
 * Shared catalog helpers for product admin.
 */

function crtlu_shop_root(): string
{
    return dirname(__DIR__);
}

function crtlu_catalog_primary_path(): string
{
    return crtlu_shop_root() . '/data/catalog.json';
}

/** @return list<string> */
function crtlu_catalog_mirror_paths(): array
{
    $root = crtlu_shop_root();
    $paths = [
        $root . '/data/catalog.json',
        $root . '/public/data/catalog.json',
    ];
    $dist = $root . '/dist/data/catalog.json';
    if (is_dir(dirname($dist))) {
        $paths[] = $dist;
    }
    return array_values(array_unique($paths));
}

/** @return list<string> product image root dirs to keep in sync */
function crtlu_product_image_roots(): array
{
    $root = crtlu_shop_root();
    $dirs = [
        $root . '/public/assets/products',
        $root . '/assets/products',
    ];
    $dist = $root . '/dist/assets/products';
    if (is_dir($dist) || is_dir(dirname($dist))) {
        $dirs[] = $dist;
    }
    return $dirs;
}

function crtlu_load_catalog(): array
{
    $path = crtlu_catalog_primary_path();
    if (!is_readable($path)) {
        return ['series' => [], 'currency' => 'USD', 'updated' => date('Y-m-d')];
    }
    $data = json_decode((string)file_get_contents($path), true);
    if (!is_array($data)) {
        return ['series' => [], 'currency' => 'USD', 'updated' => date('Y-m-d')];
    }
    if (!isset($data['series']) || !is_array($data['series'])) {
        $data['series'] = [];
    }
    return $data;
}

function crtlu_save_catalog(array $data): void
{
    $data['updated'] = date('Y-m-d');
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Failed to encode catalog JSON.');
    }
    $json .= "\n";
    foreach (crtlu_catalog_mirror_paths() as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (file_put_contents($path, $json) === false) {
            throw new RuntimeException('Failed to write catalog: ' . $path);
        }
    }
}

function crtlu_find_series(array $catalog, string $id): ?array
{
    foreach ($catalog['series'] as $item) {
        if (($item['id'] ?? '') === $id) {
            return $item;
        }
    }
    return null;
}

function crtlu_replace_series(array &$catalog, array $series): void
{
    $id = (string)($series['id'] ?? '');
    foreach ($catalog['series'] as $i => $item) {
        if (($item['id'] ?? '') === $id) {
            $catalog['series'][$i] = $series;
            return;
        }
    }
    $catalog['series'][] = $series;
}

/** URL/folder-safe slug from free text. */
function crtlu_slugify(string $text): string
{
    $text = strtolower(trim($text));
    // Keep ascii letters/digits/hyphen; strip CJK to hyphen via unicode property if available
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? $text;
    $text = preg_replace('/[^a-z0-9\-]+/i', '-', $text) ?? $text;
    $text = preg_replace('/-+/', '-', $text) ?? $text;
    $text = strtolower(trim($text, '-'));
    return $text !== '' ? $text : 'product';
}

/** Ensure product id is unique in catalog. */
function crtlu_unique_series_id(array $catalog, string $desired): string
{
    $base = crtlu_slugify($desired);
    if ($base === '') {
        $base = 'product';
    }
    $id = $base;
    $n = 2;
    while (crtlu_find_series($catalog, $id) !== null) {
        $id = $base . '-' . $n;
        $n++;
    }
    return $id;
}

/** Create empty product image folders for a series folder name. */
function crtlu_ensure_product_folders(string $folder): void
{
    $folder = trim($folder, '/');
    if ($folder === '') {
        return;
    }
    foreach (crtlu_product_image_roots() as $root) {
        $dir = rtrim($root, '/') . '/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/** Derive assets/products/<folder> from catalog image path. */
function crtlu_series_folder(array $series): string
{
    $image = (string)($series['image'] ?? '');
    if (preg_match('#assets/products/([^/]+)/#', $image, $m)) {
        return $m[1];
    }
    $id = preg_replace('/[^a-z0-9\-]+/i', '-', (string)($series['id'] ?? 'product'));
    return strtolower(trim((string)$id, '-')) ?: 'product';
}

function crtlu_public_url(string $relative): string
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    return '/' . $relative;
}

function crtlu_cache_bust(string $url): string
{
    $path = crtlu_shop_root() . '/public/' . ltrim(str_replace('\\', '/', $url), '/');
    if (!is_file($path)) {
        $path = crtlu_shop_root() . '/' . ltrim(str_replace('\\', '/', $url), '/');
    }
    $v = is_file($path) ? (string)filemtime($path) : (string)time();
    return crtlu_public_url($url) . '?v=' . $v;
}

/** Front-store base URL for “前台预览” links (Astro, not the PHP admin host). */
function crtlu_storefront_base(): string
{
    $base = 'http://127.0.0.1:4322';
    if (function_exists('crtlu_config')) {
        $configured = crtlu_config('CRTLU_BASE_URL', $base);
        if (is_string($configured) && $configured !== '') {
            $base = $configured;
        }
    }
    return rtrim($base, '/');
}

function crtlu_storefront_product_url(string $productId): string
{
    return crtlu_storefront_base() . '/products/' . rawurlencode($productId) . '/';
}

function crtlu_upload_limit_label(): string
{
    return (string)ini_get('upload_max_filesize') ?: '2M';
}

function crtlu_upload_error_message(int $code, string $fieldLabel = '图片'): string
{
    $limit = crtlu_upload_limit_label();
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
            "{$fieldLabel}超过 PHP 上传限制（当前 upload_max_filesize={$limit}）。请压缩后再传，或用 admin/serve.sh 启动后台（已提高到 64M）。",
        UPLOAD_ERR_PARTIAL => "{$fieldLabel}只上传了一部分，请重试。",
        UPLOAD_ERR_NO_FILE => "请选择{$fieldLabel}文件。",
        UPLOAD_ERR_NO_TMP_DIR => '服务器临时目录不可用。',
        UPLOAD_ERR_CANT_WRITE => '无法写入临时文件。',
        UPLOAD_ERR_EXTENSION => '上传被 PHP 扩展中断。',
        default => "{$fieldLabel}上传失败（错误码 {$code}）。",
    };
}

/**
 * Validate a single $_FILES entry and return its tmp path.
 *
 * @param array<string, mixed> $file
 */
function crtlu_require_uploaded_file(array $file, string $fieldLabel = '图片'): string
{
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        throw new RuntimeException(crtlu_upload_error_message($err, $fieldLabel));
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException("请选择{$fieldLabel}文件（未收到有效上传）。");
    }
    return $tmp;
}

/**
 * Process uploaded image into JPEG, optionally square white canvas.
 * Returns binary JPEG string.
 */
function crtlu_process_upload(string $tmpPath, bool $square = false, int $maxSide = 1600): string
{
    if (!is_file($tmpPath)) {
        throw new RuntimeException('Upload temp file missing.');
    }
    $info = @getimagesize($tmpPath);
    if ($info === false) {
        throw new RuntimeException('不是有效图片（支持 JPEG / PNG / WebP / GIF）。若是 HEIC/RAW 请先转成 JPG。');
    }
    $type = $info[2] ?? 0;
    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($tmpPath),
        IMAGETYPE_PNG => @imagecreatefrompng($tmpPath),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmpPath) : false,
        IMAGETYPE_GIF => @imagecreatefromgif($tmpPath),
        default => false,
    };
    if (!$src) {
        throw new RuntimeException('无法解码图片。请改用 JPG/PNG 后重试。');
    }

    $w = imagesx($src);
    $h = imagesy($src);
    if ($w < 1 || $h < 1) {
        throw new RuntimeException('Invalid image dimensions.');
    }

    // Flatten transparency onto white
    $flat = imagecreatetruecolor($w, $h);
    if ($flat === false) {
        throw new RuntimeException('内存不足，无法处理图片。请缩小后重试。');
    }
    $white = imagecolorallocate($flat, 255, 255, 255);
    imagefill($flat, 0, 0, $white);
    imagecopy($flat, $src, 0, 0, 0, 0, $w, $h);
    unset($src);

    if ($square) {
        $side = max($w, $h);
        $canvas = imagecreatetruecolor($side, $side);
        if ($canvas === false) {
            throw new RuntimeException('内存不足，无法生成方图。');
        }
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $flat, (int)(($side - $w) / 2), (int)(($side - $h) / 2), 0, 0, $w, $h);
        unset($flat);
        $flat = $canvas;
        $w = $h = $side;
    }

    if (max($w, $h) > $maxSide) {
        $scale = $maxSide / max($w, $h);
        $nw = max(1, (int)round($w * $scale));
        $nh = max(1, (int)round($h * $scale));
        $resized = imagecreatetruecolor($nw, $nh);
        if ($resized === false) {
            throw new RuntimeException('内存不足，无法缩放图片。');
        }
        $white = imagecolorallocate($resized, 255, 255, 255);
        imagefill($resized, 0, 0, $white);
        imagecopyresampled($resized, $flat, 0, 0, 0, 0, $nw, $nh, $w, $h);
        unset($flat);
        $flat = $resized;
    }

    ob_start();
    imagejpeg($flat, null, 90);
    $bin = (string)ob_get_clean();
    unset($flat);
    if ($bin === '') {
        throw new RuntimeException('Failed to encode JPEG.');
    }
    return $bin;
}

function crtlu_write_product_file(string $folder, string $filename, string $binary): void
{
    $folder = trim($folder, '/');
    $filename = basename($filename);
    foreach (crtlu_product_image_roots() as $root) {
        $dir = rtrim($root, '/') . '/' . $folder;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir . '/' . $filename;
        if (file_put_contents($path, $binary) === false) {
            throw new RuntimeException('Failed to write image: ' . $path);
        }
    }
}

function crtlu_delete_product_file(string $folder, string $filename): void
{
    $folder = trim($folder, '/');
    $filename = basename($filename);
    foreach (crtlu_product_image_roots() as $root) {
        $path = rtrim($root, '/') . '/' . $folder . '/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function crtlu_rebuild_gallery(array $series): array
{
    $folder = crtlu_series_folder($series);
    $main = 'assets/products/' . $folder . '/main.jpg';
    $gallery = [$main];
    $galleryInput = $series['gallery'] ?? [];
    if (!is_array($galleryInput)) {
        $galleryInput = [];
    }
    foreach ($galleryInput as $item) {
        $item = (string)$item;
        if ($item === '' || $item === $main) {
            continue;
        }
        // keep only files under this product folder
        if (!str_contains($item, 'assets/products/' . $folder . '/')) {
            continue;
        }
        if (!in_array($item, $gallery, true)) {
            $gallery[] = $item;
        }
    }
    $series['image'] = $main;
    $series['gallery'] = array_values($gallery);
    return $series;
}

function crtlu_next_detail_index(array $series): int
{
    $max = 0;
    foreach ($series['gallery'] ?? [] as $item) {
        if (preg_match('/detail-(\d+)\.jpg$/i', (string)$item, $m)) {
            $max = max($max, (int)$m[1]);
        }
    }
    return $max + 1;
}

function crtlu_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function crtlu_money_from_cents($cents): string
{
    return number_format(((int)$cents) / 100, 2, '.', '');
}
