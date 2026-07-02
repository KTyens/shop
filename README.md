# CRTL U Digital Shop

Independent ecommerce storefront for `shop.crtlu.me`.

## Architecture

- Astro static frontend deployed to Cloudflare Pages.
- PHP API and admin tools deployed to Serv00.
- MySQL/MariaDB stores orders, members, addresses, login codes, and email queue.
- Stripe Checkout handles payment.
- Yanwen fulfillment is handled through admin CSV export first.

## Frontend

Run from this folder:

```bash
npm run build
```

Cloudflare Pages environment variable should point to the Serv00-backed API domain, for example:

```text
PUBLIC_CRTLU_API_BASE_URL=https://api.crtlu.me/api
```

If this variable is not set, the frontend uses same-origin `/api`, which only works when the PHP API is hosted under the same domain or proxied.

## Backend

Serv00 keeps the PHP backend:

```text
api/
admin/
data/
database/
```

Copy `api/config.local.example.php` to `api/config.local.php` on Serv00 and fill real Stripe, database, admin, and CORS values there.

Never commit or package:

```text
api/config.local.php
.env
```

## Stripe

Webhook endpoint:

```text
https://api.crtlu.me/api/stripe-webhook.php
```

Event:

```text
checkout.session.completed
```

## Documentation

Read these before changing the project:

- `docs/agent-handoff.md`
- `docs/DEPLOYMENT.md`
- `docs/deployment-guide-zh.md` for a step-by-step Chinese deployment guide
- `docs/pricing-system.md`
- `docs/product-media-guidelines.md`
- `docs/published-products.md`
