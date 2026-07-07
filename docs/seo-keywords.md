# SEO Keyword Strategy

Last updated: 2026-07-07

This file records the storefront keyword system for `shop.crtlu.me`.

## Primary Head Terms

Use these terms naturally in page titles, headings, descriptions, product copy, and structured data:

| English | Chinese |
|---|---|
| Android TV Box | 电视盒子 |
| Google TV Box | 电视机顶盒 |
| Google TV streamer | 网络机顶盒 |
| 4K TV Box | 华人电视 |
| 4K streaming device | 安卓电视盒子 |
| streaming box | 4K 电视盒子 |
| TV set-top box | 安卓机顶盒 |
| Android set-top box | 家庭影院投影仪 |
| home cinema projector | 便携投影仪 |
| compact projector | 小型投影仪 |

## Brand And Discovery Terms

The user asked to include these related brand/search terms:

```text
UBOX12, UBOX, UNBLOCK TECH, 安博盒子, 台湾安博, 华人电视,
电视机顶盒, UNBLOCK TV BOX, EVPAD, 小云电视盒子, 易橎
```

Use these cautiously. Some users search for these terms when comparing TV boxes, but the storefront should not imply bundled paid channels, pirated IPTV, or unauthorized content.

Recommended positioning:

- "Android TV box alternatives and home cinema devices"
- "TV set-top boxes for official apps and user-owned subscriptions"
- "No preloaded paid content"
- "Legal streaming with your own subscriptions"

Avoid:

- Claiming free paid channels.
- Claiming lifetime IPTV access.
- Using third-party streaming app logos as the main SEO or product promise.
- Writing copy that suggests the product includes unauthorized content.

## Technical Modifiers

Use product-specific modifiers when accurate:

```text
Android 14 TV Box
Android 13 TV Box
Wi-Fi 6 TV Box
Bluetooth 5.4 TV Box
4K HDR TV Box
8K TV Box
RK3528 TV Box
RK3588 TV Box
Allwinner H618 TV Box
Allwinner H313 TV Box
Amlogic S905X3 TV Box
2GB 16GB TV Box
4GB 32GB TV Box
4GB 64GB TV Box
4GB 128GB TV Box
```

## Market Modifiers

Current target market assumptions from the pricing system:

```text
TV Box Japan
Android TV Box Japan
Google TV Box Japan
4K streaming device Japan
home cinema projector Japan
```

Secondary market terms can be added later for the United States, Canada, UK, EU, Australia, and New Zealand when logistics/pricing pages are expanded.

## Implementation

Current SEO implementation lives in:

- `src/lib/seo.ts`: shared keyword lists, URL helpers, JSON-LD helpers.
- `src/layouts/Layout.astro`: canonical, robots, Open Graph, Twitter card, keywords, JSON-LD rendering.
- `src/pages/index.astro`: homepage SEO title, description, keywords, Website/Organization schema.
- `src/pages/products/index.astro`: catalog SEO title, description, keywords, ItemList schema.
- `src/pages/products/[slug].astro`: product SEO title, description, keywords, Product schema.
- `src/pages/robots.txt.ts`: search crawler policy.
- `src/pages/sitemap.xml.ts`: generated sitemap from the published catalog.

Search engines ignore or downweight raw `meta keywords`, so do not rely on keyword stuffing. Stronger SEO work should add useful comparison and buying-guide pages.
