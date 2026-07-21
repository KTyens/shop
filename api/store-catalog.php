<?php

/**
 * Live storefront catalog for dual-host deploys.
 *
 * - catalog.json on Serv00 is the operational source of truth for admin edits.
 * - Product images that exist on this server (just uploaded via admin) are rewritten
 *   to absolute API URLs with mtime cache-bust so shop.crtlu.me can show them immediately.
 * - Paths without a local file stay relative → Cloudflare Pages static assets.
 *
 * Prefer same-origin fetch via CF Pages /api proxy: /api/store-catalog.php
 */

require __DIR__ . '/config.php';

crtlu_apply_cors();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$root = dirname(__DIR__);
$catalogPath = $root . '/data/catalog.json';
if (!is_readable($catalogPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'Catalog not found.', 'series' => []], JSON_UNESCAPED_SLASHES);
    exit;
}

$raw = file_get_contents($catalogPath);
$data = json_decode((string)$raw, true);
if (!is_array($data)) {
    http_response_code(500);
    echo json_encode(['error' => 'Catalog JSON invalid.', 'series' => []], JSON_UNESCAPED_SLASHES);
    exit;
}

$https = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'
);
$host = (string)($_SERVER['HTTP_HOST'] ?? 'api.crtlu.me');
// When called through CF proxy Host is still api.crtlu.me (proxy target).
$mediaOrigin = ($https ? 'https' : 'http') . '://' . $host;

/**
 * If the asset file exists on this Serv00 host, return absolute media URL with mtime.
 * Otherwise keep the catalog-relative path for Cloudflare Pages.
 */
$resolveMedia = static function (string $path) use ($root, $mediaOrigin): string {
    $path = trim($path);
    if ($path === '') {
        return '';
    }
    // Already absolute (legacy or previous rewrite stored by mistake)
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $relative = ltrim(str_replace('\\', '/', $path), '/');
    // strip accidental query
    $relative = explode('?', $relative, 2)[0];

    $candidates = [
        $root . '/public/' . $relative,
        $root . '/' . $relative,
    ];
    foreach ($candidates as $file) {
        if (is_file($file)) {
            $v = (string)filemtime($file);
            return $mediaOrigin . '/' . $relative . '?v=' . $v;
        }
    }
    return $relative;
};

if (isset($data['series']) && is_array($data['series'])) {
    foreach ($data['series'] as $i => $series) {
        if (!is_array($series)) {
            continue;
        }
        if (!empty($series['image'])) {
            $data['series'][$i]['image'] = $resolveMedia((string)$series['image']);
        }
        if (isset($series['gallery']) && is_array($series['gallery'])) {
            $data['series'][$i]['gallery'] = array_values(array_filter(array_map(
                static fn($g) => $resolveMedia((string)$g),
                $series['gallery']
            )));
        }
    }
}

// Advertise that this payload is live from the API host (debug/ops).
$data['live_from'] = 'api';
$data['live_at'] = gmdate('c');

echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
