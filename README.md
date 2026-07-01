# CRTL U Digital Shop

This folder is a lightweight independent store for `shop.crtlu.me`.

## What is included

- Premium static storefront in `index.html`
- Generated hero visual in `assets/hero-cinema.png`
- Cart UI with local storage
- Shared language switcher in `assets/i18n.js`
- Public product catalog endpoint in `api/products.php`
- Stripe Checkout endpoint in `api/create-checkout-session.php`
- Stripe webhook order creation in `api/stripe-webhook.php`
- Order status lookup in `api/order-status.php`
- Configuration health check in `api/health.php`
- Basic password-protected order admin in `admin/orders.php`
- Yanwen CSV export in `admin/export-yanwen.php`
- MySQL schema in `database/schema.sql`
- HTTPS redirect and sensitive-file protection in `.htaccess`

## Serv00 setup

1. Point `shop.crtlu.me` to the Serv00 website in Cloudflare DNS.
2. Upload the `shop/` folder contents to the web root for that subdomain.
3. Create a MySQL database and import `database/schema.sql`.
4. Copy `api/config.local.example.php` to `api/config.local.php`.
5. Fill `STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`, and database credentials.
6. In Stripe, add a webhook endpoint:

```text
https://shop.crtlu.me/api/stripe-webhook.php
```

Listen for:

```text
checkout.session.completed
```

## Admin

Orders are available at:

```text
https://shop.crtlu.me/admin/orders.php
```

Use `CRTLU_ADMIN_USER` and `CRTLU_ADMIN_PASS` in `config.local.php`.

Pending paid/processing shipments can be exported for Yanwen at:

```text
https://shop.crtlu.me/admin/export-yanwen.php
```

## Fulfillment

The current version stores paid orders and Yanwen tracking numbers. Use the CSV export first; a direct Yanwen API integration can be added after the order workflow is stable.

## Language switcher

The storefront language switcher is in `assets/i18n.js` and is loaded by the main storefront pages. Supported locales are:

```text
en, ja, zh-CN, zh-TW, es, pt, id, th, vi, ms
```

Core navigation, cart, checkout, account, catalog, detail, and success-page UI text is wired through `data-i18n` or `CRTLU_I18N.t()`. Product-specific copy can be localized later with `name_i18n` and `description_i18n` fields in `data/catalog.json`; missing translations fall back to English.

## Smoke tests after upload

Open:

```text
https://shop.crtlu.me/api/products.php
https://shop.crtlu.me/api/health.php
https://shop.crtlu.me/admin/orders.php
```

`api/health.php` should return `"ok": true`. If not, it will show which configuration group is missing without exposing secrets.

Do not upload `config.local.example.php` as the live config. Keep the real file named:

```text
public_html/api/config.local.php
```
