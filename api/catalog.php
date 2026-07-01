<?php

function crtlu_products(): array
{
    $products = [
        'hk1-rbox-x4' => [
            'id' => 'hk1-rbox-x4',
            'sku' => 'CRT-HK1-RBOX-X4',
            'name' => 'HK1 RBOX X4',
            'brand' => 'HK1',
            'category' => 'tv-box',
            'price_cents' => 6999,
            'currency' => 'usd',
            'description' => 'Android TV box for official streaming apps and local media playback.',
        ],
        'h96-max-v58' => [
            'id' => 'h96-max-v58',
            'sku' => 'CRT-H96-MAX-V58',
            'name' => 'H96 MAX V58 4GB+32GB',
            'brand' => 'H96',
            'category' => 'premium',
            'price_cents' => 23899,
            'compare_at_cents' => 24399,
            'currency' => 'usd',
            'rmb_price' => 1000,
            'description' => 'RK3588 flagship Android TV box with Wi-Fi 6, Gigabit Ethernet, and 4GB RAM / 32GB storage.',
        ],
        'h96max-h618-plus-2-16' => [
            'id' => 'h96max-h618-plus-2-16',
            'sku' => 'CRT-H96-H618P-2G16G',
            'name' => 'H96Max H618 Plus 2GB+16GB',
            'brand' => 'H96',
            'category' => 'tv-box',
            'price_cents' => 4999,
            'compare_at_cents' => 5299,
            'currency' => 'usd',
            'rmb_price' => 170,
            'description' => 'Android 14 TV box with Allwinner H618, Wi-Fi 6, Bluetooth 5.4, and 2GB RAM / 16GB storage.',
        ],
        'h96max-h618-plus-4-32' => [
            'id' => 'h96max-h618-plus-4-32',
            'sku' => 'CRT-H96-H618P-4G32G',
            'name' => 'H96Max H618 Plus 4GB+32GB',
            'brand' => 'H96',
            'category' => 'tv-box',
            'price_cents' => 6499,
            'compare_at_cents' => 6999,
            'currency' => 'usd',
            'rmb_price' => 230,
            'description' => 'Android 14 TV box with Allwinner H618, Wi-Fi 6, Bluetooth 5.4, and 4GB RAM / 32GB storage.',
        ],
        'h96max-h618-plus-4-64' => [
            'id' => 'h96max-h618-plus-4-64',
            'sku' => 'CRT-H96-H618P-4G64G',
            'name' => 'H96Max H618 Plus 4GB+64GB',
            'brand' => 'H96',
            'category' => 'tv-box',
            'price_cents' => 7999,
            'compare_at_cents' => 8499,
            'currency' => 'usd',
            'rmb_price' => 300,
            'description' => 'Android 14 TV box with Allwinner H618, Wi-Fi 6, Bluetooth 5.4, and 4GB RAM / 64GB storage.',
        ],
        'h96max-m1-plus-2-16' => [
            'id' => 'h96max-m1-plus-2-16',
            'sku' => 'CRT-H96-M1P-2G16G',
            'name' => 'H96Max M1 Plus 2GB+16GB',
            'brand' => 'H96',
            'category' => 'tv-box',
            'price_cents' => 4799,
            'compare_at_cents' => 4999,
            'currency' => 'usd',
            'rmb_price' => 165,
            'description' => 'Android 14 TV box with Rockchip RK3528, Wi-Fi 6, Bluetooth 5.4, and 2GB RAM / 16GB storage.',
        ],
        'h96max-m1-plus-4-32' => [
            'id' => 'h96max-m1-plus-4-32',
            'sku' => 'CRT-H96-M1P-4G32G',
            'name' => 'H96Max M1 Plus 4GB+32GB',
            'brand' => 'H96',
            'category' => 'tv-box',
            'price_cents' => 5999,
            'compare_at_cents' => 6499,
            'currency' => 'usd',
            'rmb_price' => 220,
            'description' => 'Android 14 TV box with Rockchip RK3528, Wi-Fi 6, Bluetooth 5.4, and 4GB RAM / 32GB storage.',
        ],
        'h96max-m1-plus-4-128' => [
            'id' => 'h96max-m1-plus-4-128',
            'sku' => 'CRT-H96-M1P-4G128G',
            'name' => 'H96Max M1 Plus 4GB+128GB',
            'brand' => 'H96',
            'category' => 'tv-box',
            'price_cents' => 7499,
            'compare_at_cents' => 7999,
            'currency' => 'usd',
            'rmb_price' => 295,
            'description' => 'Android 14 TV box with Rockchip RK3528, Wi-Fi 6, Bluetooth 5.4, and 4GB RAM / 128GB storage.',
        ],
        'mecool-km2-plus' => [
            'id' => 'mecool-km2-plus',
            'sku' => 'CRT-MECOOL-KM2-PLUS',
            'name' => 'Mecool KM2 Plus',
            'brand' => 'Mecool',
            'category' => 'google-tv',
            'price_cents' => 12999,
            'currency' => 'usd',
            'description' => 'Google TV style streamer for family entertainment.',
        ],
        'rocktek-g2' => [
            'id' => 'rocktek-g2',
            'sku' => 'CRT-ROCKTEK-G2',
            'name' => 'Rocktek G2',
            'brand' => 'Rocktek',
            'category' => 'premium',
            'price_cents' => 16999,
            'currency' => 'usd',
            'description' => 'Premium streamer with stronger build and positioning.',
        ],
        'crtlu-p8-projector' => [
            'id' => 'crtlu-p8-projector',
            'sku' => 'CRT-P8-PROJECTOR',
            'name' => 'P8 Compact Projector',
            'brand' => 'CRTLU Select',
            'category' => 'projector',
            'price_cents' => 18999,
            'currency' => 'usd',
            'description' => 'Compact projector for bedroom and movie-night setups.',
        ],
        'aurora-mini-kit' => [
            'id' => 'aurora-mini-kit',
            'sku' => 'CRT-AURORA-MINI-KIT',
            'name' => 'Aurora Mini Cinema Kit',
            'brand' => 'CRTLU Select',
            'category' => 'projector',
            'price_cents' => 23999,
            'currency' => 'usd',
            'description' => 'Starter projector bundle concept for big-screen setups.',
        ],
    ];

    $dataPath = dirname(__DIR__) . '/data/catalog.json';
    if (is_readable($dataPath)) {
        $decoded = json_decode((string) file_get_contents($dataPath), true);
        foreach (($decoded['series'] ?? []) as $series) {
            foreach (($series['variants'] ?? []) as $variant) {
                if (empty($variant['id']) || empty($variant['sku'])) {
                    continue;
                }
                $products[$variant['id']] = [
                    'id' => $variant['id'],
                    'sku' => $variant['sku'],
                    'name' => trim(($series['name'] ?? 'Product') . ' ' . ($variant['label'] ?? '')),
                    'brand' => $series['brand'] ?? '',
                    'category' => $series['category'] ?? '',
                    'price_cents' => (int) ($variant['price_cents'] ?? 0),
                    'compare_at_cents' => $variant['compare_at_cents'] ?? null,
                    'currency' => $decoded['currency'] ?? 'usd',
                    'rmb_price' => $variant['rmb_price'] ?? null,
                    'description' => $series['description'] ?? '',
                ];
            }
        }
    }

    return $products;
}

function crtlu_public_products(): array
{
    return array_values(array_map(static function (array $product): array {
        return [
            'id' => $product['id'],
            'sku' => $product['sku'],
            'name' => $product['name'],
            'brand' => $product['brand'],
            'category' => $product['category'],
            'price_cents' => $product['price_cents'],
            'compare_at_cents' => $product['compare_at_cents'] ?? null,
            'currency' => $product['currency'],
            'rmb_price' => $product['rmb_price'] ?? null,
            'description' => $product['description'],
        ];
    }, crtlu_products()));
}
