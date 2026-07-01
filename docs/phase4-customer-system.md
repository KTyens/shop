# Phase 4 Customer System Notes

Date: 2026-07-01

## Implemented In This Phase

This phase adds the first production-safe layer for:

- Coupon validation and checkout discounting.
- Display currency preference.
- Locale preference storage.
- Member record creation from successful Stripe orders.
- Email notification queue and optional PHP `mail()` order confirmation.
- Admin order visibility for coupon, discount, display currency, and locale.

The site still charges Stripe Checkout in catalog currency, currently USD. Other currencies are display estimates only.

## Files

```text
data/coupons.json                  Coupon definitions
assets/shop-phase4.js              Shared frontend currency, locale, coupon state
api/promotions.php                 Coupon and discount calculation helpers
api/validate-coupon.php            Public coupon validation endpoint
api/create-checkout-session.php    Applies coupon-adjusted unit prices and metadata
api/notifications.php              Member, email queue, and table helpers
api/stripe-webhook.php             Writes member/order metadata and queues email
database/schema.sql                Phase 4 database tables and order columns
admin/orders.php                   Shows coupon/discount/preferences
```

## Coupon Configuration

Coupons are stored in:

```text
shop/data/coupons.json
```

Current examples:

```text
WELCOME5
VIP8
```

Supported fields:

- `code`: public coupon code.
- `label`: customer-facing success message.
- `type`: `percent` or `amount`.
- `percent_off`: percentage discount for `percent` coupons.
- `amount_off_cents`: fixed discount for `amount` coupons.
- `min_subtotal_cents`: minimum cart subtotal before shipping.
- `max_discount_cents`: maximum discount cap.
- `starts_at`, `ends_at`: optional date strings.
- `active`: true or false.

Discounts apply to product line-item prices before Stripe Checkout. Shipping remains unchanged.

## Database Upgrade

For a new database, import:

```text
shop/database/schema.sql
```

For an existing database that already has `orders` and `order_items`, run once:

```text
shop/database/phase4-migration.sql
```

Do not run `phase4-migration.sql` repeatedly unless you first remove the already-added columns/indexes. The webhook is backward compatible. If the new columns/tables are not present yet, normal order writing still works, but member/email/coupon records will not be stored.

New order columns:

```text
member_id
coupon_code
discount_total
display_currency
locale
```

New tables:

```text
members
email_notifications
coupon_redemptions
```

## Email Notifications

Set this in `api/config.local.php` if server-side mail is available:

```php
define('CRTLU_MAIL_FROM', 'support@crtlu.me');
```

The webhook always queues the email into `email_notifications` when that table exists. It only attempts PHP `mail()` when `CRTLU_MAIL_FROM` is non-empty.

## Member System Scope

This phase creates member records from paid orders by email. It does not yet add password login or a customer account dashboard.

Recommended next step:

1. Add passwordless email login.
2. Add `account.html` or `account.php`.
3. Show order history by authenticated member.
4. Add member-only coupon rules after login exists.

## Multi-Language Scope

This phase stores locale preference and adds a language selector in the cart. It does not fully translate the storefront catalog yet.

Recommended next step:

1. Create `data/i18n/en.json`, `ja.json`, `zh.json`.
2. Translate shared UI chrome first.
3. Add optional translated product fields in `catalog.json`.
4. Keep English fallback for missing translations.

## Multi-Currency Scope

Currency selector supports:

```text
USD, JPY, CNY, EUR
```

Non-USD prices are approximate display conversions. Stripe still charges USD. Update rates in:

```text
assets/shop-phase4.js
```

before major pricing pushes.
