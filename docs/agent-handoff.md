# Agent Handoff And Maintenance Guide

Last updated: 2026-07-01

This document is the working handoff for agents maintaining the CRTL U Digital independent store at `shop.crtlu.me`.

## Project Summary

The project is a lightweight ecommerce site for TV boxes and compact projectors. It is designed for simple Serv00 PHP hosting, with Cloudflare DNS in front and Stripe Checkout for payment.

Current live domain:

```text
https://shop.crtlu.me
```

Primary hosting target:

```text
Serv00 website root for shop.crtlu.me
```

The current implementation is mostly static HTML/CSS/JavaScript plus a small PHP API layer. There is no Node build step.

## Directory Map

```text
shop/
  index.html                         Homepage and featured product cards
  success.html                       Stripe success/order status page
  .htaccess                          HTTPS redirect and sensitive file protection
  README.md                          Setup overview

  products/
    index.html                       All-products listing page
    detail.html                      Generic product detail page driven by catalog.json
    h96max-plus.html                 Special detail page for H96Max H618 Plus and M1 Plus

  data/
    catalog.json                     Main product catalog and variant source
    coupons.json                     Storefront coupon rules used by checkout

  assets/
    hero-cinema.png                  Homepage hero visual
    i18n.js                          Storefront language switcher and translation dictionary
    shop-phase4.js                   Shared currency, locale, and coupon cart helper
    products/<product-id>/           Product images used by catalog.json

  api/
    config.php                       Config loader and PDO helper
    config.local.example.php         Safe template only
    config.local.php                 Live local config, do not package
    catalog.php                      Product catalog adapter for checkout/API
    products.php                     Public JSON product endpoint
    create-checkout-session.php      Stripe Checkout session creation
    stripe-webhook.php               Stripe webhook order writer
    order-status.php                 Success page order lookup
    health.php                       Configuration and database health check
    validate-coupon.php              Public coupon validation endpoint
    promotions.php                   Coupon/cart pricing helper
    notifications.php                Member/order email queue helper
    member-auth.php                  Member session and one-time-code helper
    account-request-code.php         Sends member login code
    account-login.php                Verifies member login code
    account.php                      Member profile, address, and order API
    account-logout.php               Ends member session

  admin/
    auth.php                         Basic auth helper
    orders.php                       Order dashboard and fulfillment status editor
    export-yanwen.php                CSV export for Yanwen fulfillment
    members.php                      Member list and purchase summary
    coupons.php                      Coupon editor for data/coupons.json
    emails.php                       Email queue viewer and retry tool

  database/
    schema.sql                       MySQL/MariaDB schema
    phase4-migration.sql             Existing-database upgrade for members/coupons/email queue
    phase5-migration.sql             Existing-database upgrade for login codes and addresses

  docs/
    pricing-system.md                Pricing formulas and confirmed cost references
    product-media-guidelines.md      Product image selection rules
    published-products.md            Published product and SKU matrix
    phase4-customer-system.md        Phase 4 customer/coupon/email/multicurrency notes
    phase5-advanced-commerce.md      Phase 5 member center and admin ops notes
    agent-handoff.md                 This document
```

There is also an unrelated `open-design-full/` folder in the workspace. Do not modify it for this shop unless the user explicitly asks.

## Source Of Truth

Use these files as the durable source of truth:

| Area | Source |
|---|---|
| Product lines, variants, specs, images | `shop/data/catalog.json` |
| Product pricing logic and margin assumptions | `shop/docs/pricing-system.md` |
| Already published products and missing products | `shop/docs/published-products.md` |
| Product image selection rules | `shop/docs/product-media-guidelines.md` |
| Coupon rules | `shop/data/coupons.json` |
| Storefront UI translations | `shop/assets/i18n.js` |
| Live server secrets | `shop/api/config.local.php` on server only |
| Database schema | `shop/database/schema.sql` |

Do not infer pricing from Amazon or marketplace screenshots unless the user explicitly asks. Supplier cost plus the pricing system is the default.

## Catalog Data Contract

`shop/data/catalog.json` has this top-level shape:

```json
{
  "updated": "2026-06-30",
  "currency": "usd",
  "series": [],
  "pending": []
}
```

Each `series` entry should include:

```json
{
  "id": "hk1-rbox-k8s",
  "brand": "HK1",
  "name": "HK1 RBOX K8S",
  "category": "tv-box",
  "tier": "main",
  "status": "published",
  "detail_url": "products/detail.html?id=hk1-rbox-k8s",
  "description": "Short customer-facing description.",
  "image": "assets/products/hk1-rbox-k8s/main.jpg",
  "gallery": [
    "assets/products/hk1-rbox-k8s/main.jpg",
    "assets/products/hk1-rbox-k8s/detail-1.jpg"
  ],
  "specs": {
    "Chipset": "RK3528",
    "OS": "Android 13"
  },
  "variants": [
    {
      "id": "hk1-rbox-k8s-2-16",
      "sku": "CRT-HK1-K8S-2G16G",
      "label": "2GB RAM + 16GB ROM",
      "rmb_price": 140,
      "price_cents": 3899,
      "compare_at_cents": 4399
    }
  ]
}
```

Rules:

- `series.id` must match the product asset folder name when practical.
- `variant.id` is the cart and checkout product id. Keep it stable after publishing.
- Prices are cents in USD. `$38.99` is `3899`.
- `compare_at_cents` is the normal crossed-out price; `price_cents` is the active selling price.
- `image` is used by product cards.
- `gallery` is used by detail pages. Keep 5 or more images if source material exists.
- `detail_url` should usually be `products/detail.html?id=<series-id>`.
- `products/h96max-plus.html` is a custom page for H96Max H618 Plus and H96Max M1 Plus.

`api/catalog.php` loads variants from `catalog.json` and exposes them to checkout. It also contains older hardcoded fallback products. The JSON catalog currently overrides/adds the real published variants, so update `catalog.json` first.

## Frontend Pages

### Language Switching

File:

```text
shop/assets/i18n.js
```

The storefront now has a shared language switcher injected into the top nav/action area. It supports:

```text
en, ja, zh-CN, zh-TW, es, pt, id, th, vi, ms
```

Rules:

- Static text uses `data-i18n="<key>"`.
- Input placeholders use `data-i18n-placeholder="<key>"`.
- Dynamic JavaScript text should call `window.CRTLU_I18N.t(key, vars)`.
- Product names/descriptions can use optional localized catalog fields such as `name_i18n` and `description_i18n`.
- If a translation is missing, the system falls back to English.
- Locale is stored in localStorage key `crtlu-locale-v1`.
- `shop/assets/shop-phase4.js` reads the same locale for checkout metadata and the cart language selector.

Pages currently wired to the shared language system:

- `index.html`
- `products/index.html`
- `products/detail.html`
- `products/h96max-plus.html`
- `account.html`
- `success.html`

When adding a new storefront page, include `assets/i18n.js` before page scripts and add a `.nav-actions`, `#openCart`, or `.nav-links` target so the language switcher has a predictable insertion point.

### Account Page

File:

```text
shop/account.html
```

This page provides email one-time-code login, profile preferences, saved address, and order history. It calls:

```text
api/account-request-code.php
api/account-login.php
api/account.php
api/account-logout.php
```

The account page expects the Phase 4 and Phase 5 database migrations to be imported on existing deployments. If the database is not configured yet, the API returns a readable setup message instead of a blank failure.

### Homepage

File:

```text
shop/index.html
```

The homepage has a local JavaScript `PRODUCTS` array for featured cards. It is not automatically synced from `catalog.json`.

When featuring or replacing homepage products:

1. Update the `PRODUCTS` array in `shop/index.html`.
2. Use real product images from `assets/products/<id>/main.jpg`.
3. Keep the first viewport and product grid visually balanced.
4. Use `detailUrl` pointing to either the custom page or generic detail page.

Current homepage intent:

- First row: key TV box products.
- Second row: projector products so the row does not look empty.

### All Products Page

File:

```text
shop/products/index.html
```

This page fetches and renders `../data/catalog.json`. Product card images use `object-fit: contain`, so product photos should not be cropped.

### Generic Detail Page

File:

```text
shop/products/detail.html
```

This page reads `id` from the query string:

```text
products/detail.html?id=hk1-rbox-k8s
```

It renders:

- Main gallery.
- Thumbnail gallery.
- Variant selector.
- Add-to-cart behavior.
- Specs from `series.specs`.
- Full product image grid from `series.gallery`.

The detail page also uses `object-fit: contain` for product images.

## Product Media Rules

Follow `shop/docs/product-media-guidelines.md`.

Current practical standard:

- Use real product photos, not CSS placeholder graphics, once images exist.
- Prefer a product set image with device plus remote/accessories.
- Avoid product images dominated by third-party streaming service logos.
- Avoid spec-sheet screenshots as main images when real product photos are available.
- Keep at least 5 images per published detail page if source material exists.
- Current published product asset target is 8 gallery images per product line where possible.

## Phase 4 And Phase 5 Deployment

For existing Serv00 deployments, import migrations in this order:

```text
database/phase4-migration.sql
database/phase5-migration.sql
```

For fresh installs, use:

```text
database/schema.sql
```

Keep `api/config.local.php` server-only. The package should include `api/config.local.example.php`, not the real config.

Optional production config:

```php
define('CRTLU_MAIL_FROM', 'support@crtlu.me');
define('CRTLU_LOGIN_CODE_DEBUG', '0');
```

Do not enable `CRTLU_LOGIN_CODE_DEBUG` in production. It is only for local testing because it returns the one-time login code in the API response.

## Admin URLs

All admin pages use the same Basic Auth credentials:

```text
admin/orders.php
admin/members.php
admin/coupons.php
admin/emails.php
admin/export-yanwen.php
```

`admin/coupons.php` writes to `data/coupons.json`, so the web server user must be able to update that file if coupon edits should be done from the browser.

Recommended product asset naming:

```text
shop/assets/products/<product-id>/
  main.jpg
  detail-1.jpg
  detail-2.jpg
  detail-3.jpg
  detail-4.jpg
  detail-5.jpg
  detail-6.jpg
  detail-7.jpg
```

## Adding A Product Line

1. Review `shop/docs/published-products.md` to avoid duplicates.
2. Confirm cost with the user or source sheet.
3. Price using `shop/docs/pricing-system.md`.
4. Create or update product images under:

   ```text
   shop/assets/products/<product-id>/
   ```

5. Add one `series` entry to `shop/data/catalog.json`.
6. Add all sellable configurations to `series.variants`.
7. Add specs to `series.specs`.
8. Set `detail_url` to:

   ```text
   products/detail.html?id=<product-id>
   ```

9. Update `shop/docs/published-products.md`.
10. If it should appear on the homepage, update the `PRODUCTS` array in `shop/index.html`.
11. Run validation commands from this document.

Do not publish out-of-stock products unless the user explicitly asks.

## Pricing Rules

Use `shop/docs/pricing-system.md` as the source.

Current baseline:

- TV box Yanwen shipping to Japan: RMB 40.
- Projector Yanwen shipping to Japan: RMB 60.
- Packaging/handling: RMB 5.
- Reference exchange rate: 7.20 RMB/USD.
- Normal target gross margin: 38% to 42%.
- Launch promo can be lower but should usually stay above 33% gross margin.

When updating prices:

- Update `shop/data/catalog.json`.
- Update `shop/docs/published-products.md`.
- Update `shop/index.html` if the product is featured on homepage.
- Keep active price in `price_cents`.
- Keep normal price in `compare_at_cents`.

## Cart And Checkout

Cart state is stored in browser localStorage with key:

```text
crtlu-cart-v1
```

Checkout endpoint:

```text
shop/api/create-checkout-session.php
```

Checkout flow:

1. Frontend sends cart item ids and quantities.
2. `api/create-checkout-session.php` validates items against `crtlu_products()`.
3. It creates a Stripe Checkout session.
4. Stripe redirects to `success.html?session_id=...`.
5. Stripe webhook writes the order to MySQL after `checkout.session.completed`.
6. `success.html` can call `api/order-status.php` to look up order state.

Current Stripe webhook endpoint:

```text
https://shop.crtlu.me/api/stripe-webhook.php
```

Stripe event to listen for:

```text
checkout.session.completed
```

Known implementation note:

- Checkout currently uses a fixed Yanwen shipping option of USD 12.00.
- `create-checkout-session.php` has a hardcoded allowed-country list. If the user wants Japan checkout live, verify whether `JP` must be added before testing real orders.

## Backend And Database

Config loader:

```text
shop/api/config.php
```

Live config file on server:

```text
shop/api/config.local.php
```

Safe template:

```text
shop/api/config.local.example.php
```

Required config keys:

```php
CRTLU_BASE_URL
STRIPE_SECRET_KEY
STRIPE_WEBHOOK_SECRET
CRTLU_DB_DSN
CRTLU_DB_USER
CRTLU_DB_PASS
CRTLU_ADMIN_USER
CRTLU_ADMIN_PASS
```

Database tables:

- `orders`
- `order_items`

Schema file:

```text
shop/database/schema.sql
```

The schema uses `LONGTEXT` for `shipping_address_json` instead of a native JSON column for broader MariaDB compatibility on Serv00.

## Admin And Fulfillment

Admin order dashboard:

```text
https://shop.crtlu.me/admin/orders.php
```

Authentication:

- HTTP Basic Auth.
- Credentials come from `CRTLU_ADMIN_USER` and `CRTLU_ADMIN_PASS`.

Supported order statuses:

```text
paid
processing
shipped
delivered
refunded
```

Yanwen export:

```text
https://shop.crtlu.me/admin/export-yanwen.php
```

This exports pending paid/processing shipments for manual fulfillment. Direct Yanwen API integration has not been implemented.

## Deployment

The deployment target is the web root for `shop.crtlu.me`, for example:

```text
/usr/home/CRTLU/domains/shop.crtlu.me/public_html
```

Upload the contents of `shop/`, not the parent workspace.

Do not upload or package:

```text
shop/api/config.local.php
.DS_Store
```

The repository `.htaccess` already:

- Disables directory indexes.
- Redirects HTTP to HTTPS.
- Blocks direct access to `.sql`, example PHP config, README, and `.DS_Store`.

Package command from inside `shop/`:

```bash
zip -r ../crtlu-shop-h96-update-20260630.zip . -x '*.DS_Store' 'api/config.local.php'
```

After packaging, verify the live config is excluded:

```bash
unzip -l ../crtlu-shop-h96-update-20260630.zip | rg 'api/config\.local\.php$'
```

Expected result: no output.

## Smoke Tests

Run after product/catalog/frontend edits:

```bash
node -e 'const fs=require("fs"); const d=JSON.parse(fs.readFileSync("shop/data/catalog.json","utf8")); const short=d.series.filter(s=>(s.gallery||[]).length<5).map(s=>`${s.id}|${s.name}|${(s.gallery||[]).length}`); const missing=[]; for(const s of d.series){ for(const img of [s.image,...(s.gallery||[])].filter(Boolean)) if(!fs.existsSync("shop/"+img)) missing.push(`${s.id}:${img}`); } console.log("series",d.series.length,"variants",d.series.reduce((n,s)=>n+s.variants.length,0),"short",short.length,"missing",missing.length); if(short.length) console.log(short.join("\n")); if(missing.length) console.log(missing.join("\n"));'
```

Expected current result:

```text
series 29 variants 77 short 0 missing 0
```

Run after editing inline scripts in HTML:

```bash
node -e 'const fs=require("fs"); for (const f of ["shop/index.html","shop/products/index.html","shop/products/detail.html","shop/products/h96max-plus.html","shop/success.html"]) { const html=fs.readFileSync(f,"utf8"); const scripts=[...html.matchAll(/<script>([\s\S]*?)<\/script>/g)].map(m=>m[1]); scripts.forEach((s,i)=>{ try { new Function(s); } catch(e) { throw new Error(f+" script "+i+": "+e.message); } }); console.log("script ok", f, scripts.length); }'
```

Optional local static server check from inside `shop/`:

```bash
python3 -m http.server 8766
```

Then check:

```text
http://127.0.0.1:8766/
http://127.0.0.1:8766/products/
http://127.0.0.1:8766/products/detail.html?id=hk1-rbox-k8s
```

Run after upload:

```text
https://shop.crtlu.me/api/products.php
https://shop.crtlu.me/api/health.php
https://shop.crtlu.me/admin/orders.php
```

`api/health.php` should return `"ok": true`.

## Current Product State

As of 2026-06-30:

- Product series: 29.
- Published variants: 77.
- Product image files under `shop/assets/products`: 248.
- All published product galleries have at least 5 images.
- Most published product galleries have 8 images.

See `shop/docs/published-products.md` for the full matrix.

Current known not-published items:

- `T95 5G Version 4+128G`: cost not visible in source reference.
- `HK1MAX RK3318`: out of stock.
- `H96 MAX V56 2+16G / 8+128G`: out of stock, cost set to 0.

## Security Rules

- Never print or commit Stripe secret keys, webhook secrets, database passwords, or admin passwords.
- Never include `api/config.local.php` in upload ZIPs.
- Keep `api/config.local.example.php` generic.
- If a log or screenshot contains secrets, redact before sharing.
- Do not weaken `.htaccess` protections without a specific reason.

## Common Mistakes To Avoid

- Updating `catalog.json` prices but forgetting homepage `PRODUCTS` prices.
- Adding a product to `catalog.json` without adding matching image files.
- Using a spec sheet or logo-heavy marketing image as a product card main image.
- Publishing a product that the user marked out of stock.
- Changing variant ids after a product has been published.
- Forgetting to update `published-products.md` after adding/removing variants.
- Packaging the whole workspace instead of only the contents of `shop/`.
