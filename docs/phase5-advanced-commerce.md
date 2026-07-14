# Phase 5 Advanced Commerce

Phase 5 adds a lightweight customer and operations layer without changing the stack away from static pages plus PHP APIs.

## Added Customer Features

- `account.html`
  - Email one-time-code sign-in
  - Customer profile preferences
  - Saved delivery address
  - Order history by verified email/member id
  - Yanwen tracking display after admin fulfillment updates

- Account APIs
  - `api/account-request-code.php`
  - `api/account-login.php`
  - `api/account.php`
  - `api/account-logout.php`
  - `api/member-auth.php`

Login codes are stored as password hashes in `member_login_codes`. They expire after 15 minutes and can be requested at most five times per email per 10 minutes.

Set `CRTLU_LOGIN_CODE_DEBUG` to `1` only in local development if you need the API to return the code for testing. Keep it `0` in production.

## Added Admin Features

All admin pages use the existing Basic Auth credentials from `CRTLU_ADMIN_USER` and `CRTLU_ADMIN_PASS`.

- `admin/members.php`
  - Customer list
  - Order count and revenue summary
  - Locale/currency preferences

- `admin/coupons.php`
  - Create, update, and delete storefront coupon codes
  - Persists to `data/coupons.json`

- `admin/emails.php`
  - View queued login-code and order-confirmation emails
  - Retry sending with PHP `mail()`
  - Mark messages as sent manually

## Database Upgrade

For existing deployments, import these in order:

1. `database/phase4-migration.sql`
2. `database/phase5-migration.sql`

Fresh installations can use `database/schema.sql`.

## Email Notes

The project still works if `mail()` is not configured: messages are queued into `email_notifications` and can be reviewed in `admin/emails.php`.

For production reliability, the next best upgrade is SMTP/API mail delivery through a provider such as Brevo, SendGrid, Mailgun, or Amazon SES.

## Owner Order Alerts

After Stripe webhook writes a paid order, the backend can notify the store owner by email and Telegram.

Configure these in `api/config.local.php` on Serv00:

```php
define('CRTLU_MAIL_FROM', 'support@crtlu.me');
define('CRTLU_ORDER_NOTIFY_EMAIL', 'owner@gmail.com');
define('CRTLU_TELEGRAM_BOT_TOKEN', '123456:replace_me');
define('CRTLU_TELEGRAM_CHAT_ID', '123456789');
```

`CRTLU_ORDER_NOTIFY_EMAIL` queues an `admin_order_alert` email and attempts PHP `mail()` when `CRTLU_MAIL_FROM` is set. Telegram is optional; leave the token and chat id empty to disable it.

## Current Boundaries

This is now a practical lightweight commerce account system, not a full Shopify clone. It intentionally does not yet include:

- Password login
- Customer self-service returns
- Real-time FX rates
- Full human-edited translation of every product-specific marketing paragraph
- Inventory reservation
- Automated Yanwen API integration

## Multilingual Storefront

The shared storefront language layer lives in `assets/i18n.js`. It currently supports:

```text
en, ja, zh-CN, zh-TW, es, pt, id, th, vi, ms
```

The main storefront pages, catalog, detail pages, cart drawer, account page, and success page are wired to the shared language switcher. Product names and descriptions still fall back to English unless localized fields such as `name_i18n` or `description_i18n` are added in `data/catalog.json`.
