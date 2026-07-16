# SEO Keyword Strategy

Last updated: 2026-07-16

This file records the storefront keyword and AI-discovery system for `shop.crtlu.me`.

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
| wireless HDMI | 无线HDMI |
| mini wireless keyboard | 迷你无线键盘 |

## Catalog Expansion (2026-07)

Add model/brand discovery terms when writing titles and meta (accurate products only):

```text
MECOOL, MECOOL KM2 Plus, MECOOL KM7 Plus
X96, X96Q, X96Q PRO, X96 S400, X96 X4, X96 Max+ Ultra
X98 PLUS, X98Q, X98K
Wireless HDMI WX50, WX100
I8 mini keyboard, T18+ mini keyboard
H96Max H618 Plus, H96Max M1 Plus, H96 MAX V58, H96Max M12
HK1 RBOX K8S, X4S, W2, H8…
Tanix, T95, TX98, X5 YB962
```

Chipset modifiers when accurate:

```text
Amlogic S905X4, S905W2, S905X3
Allwinner H618, H313
Rockchip RK3528, RK3588
Android 10 / 11 / 14 TV Box
Wi-Fi 6, dual-band, Gigabit Ethernet, 100M LAN
AV1 decode, 4K HDR
```

## Brand And Discovery Terms (comparison searches)

```text
UBOX12, UBOX, UNBLOCK TECH, 安博盒子, 台湾安博, 华人电视,
电视机顶盒, UNBLOCK TV BOX, EVPAD, 小云电视盒子, 易橎
```

Use cautiously. Position as legal hardware for official apps — never imply bundled paid channels or IPTV.

## Market Modifiers

```text
TV Box Japan / Android TV Box Japan
Android TV Box USA / Canada / UK / EU / Australia
global shipping TV box
Yanwen tracked shipping
```

## Implementation Map

| File | Role |
|---|---|
| `src/lib/seo.ts` | Keyword lists, Product/Org/WebSite/FAQ/Breadcrumb JSON-LD |
| `src/layouts/Layout.astro` | Canonical, robots, OG/Twitter, JSON-LD, link to `llms.txt` |
| `src/pages/index.astro` | Home title/description + WebSite + Org + FAQ + ItemList |
| `src/pages/products/index.astro` | Catalog title/description + ItemList |
| `src/pages/products/[slug].astro` | Product title/description + Product + FAQ + Breadcrumb |
| `src/pages/robots.txt.ts` | Allow index + explicit AI crawlers |
| `src/pages/sitemap.xml.ts` | All published product URLs |
| `public/llms.txt` | **AI agent summary** (what we sell / policies / key URLs) |
| `public/data/catalog.json` | Machine-readable product data for tools & agents |

## Google SEO Priorities

1. Accurate **title + meta description** per product (name, config, category, price from).
2. **Product JSON-LD** with AggregateOffer, shippingDetails, InStock.
3. **Sitemap** freshness after catalog changes (`npm run build` + deploy).
4. Helpful FAQ on PDP + homepage (also used by AI overviews).
5. Avoid keyword stuffing; write clear buying copy.

## AI / Agent Discoverability

| Mechanism | Purpose |
|---|---|
| `llms.txt` | Short, citable store facts for ChatGPT/Claude/Perplexity-style tools |
| `robots.txt` Allow for GPTBot, ClaudeBot, Google-Extended, PerplexityBot, etc. | Do not block training/browse bots unless policy changes |
| `catalog.json` public URL | Structured inventory agents can fetch |
| Schema.org Product / Offer / FAQ / Organization | Rich results + grounded citations |
| BreadcrumbList | Clear site hierarchy in SERP and parsers |

Optional later (not required for launch):

- Google Search Console property + sitemap submit
- Merchant Center / product feed if running Google Shopping ads
- Comparison / buying-guide articles (content SEO)
- `hreflang` only if full localized URL trees exist

## Safety Rules

Avoid:

- Free paid channels / lifetime IPTV claims
- Third-party streaming brand logos as the main promise
- Unauthorized content implications

Prefer:

- “Official apps only”
- “Bring your own subscriptions”
- “Legal streaming device / hardware”
