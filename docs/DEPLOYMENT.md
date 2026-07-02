# Cloudflare Pages Frontend + Serv00 API Deployment

This project uses a split deployment:

- Cloudflare Pages serves the Astro static storefront from `dist/`.
- Serv00 serves the PHP API, admin pages, MySQL-backed order system, and Stripe webhook.

For a non-developer step-by-step Chinese guide, use `docs/deployment-guide-zh.md`.

Do not put Stripe secret keys or database credentials in Cloudflare Pages. Those values stay only in `api/config.local.php` on Serv00.

## Cloudflare Pages Frontend

Recommended build settings:

| Field | Value |
|---|---|
| Framework preset | `Astro` |
| Build command | `npm run build` |
| Build output directory | `dist` |
| Root directory | `shop` if deploying from the parent workspace, otherwise blank |
| Node version | Node 20 or newer |

Set this public environment variable in Cloudflare Pages:

| Variable | Value |
|---|---|
| `PUBLIC_CRTLU_API_BASE_URL` | Your Serv00 API base, for example `https://api.crtlu.me/api` |

If the variable is missing, the frontend falls back to same-origin `/api`, which only works when the PHP API is hosted under the same domain.

If `shop.crtlu.me` is assigned to Cloudflare Pages, put the Serv00 PHP backend on a separate subdomain such as `api.crtlu.me`, or add an explicit Cloudflare Worker proxy for `/api/*`.

## Serv00 Backend

Upload or keep these folders/files on Serv00:

```text
api/
admin/
data/
database/
.htaccess
```

Server-only config:

```text
api/config.local.php
```

Start from `api/config.local.example.php` and fill the real values on Serv00:

```php
define('CRTLU_BASE_URL', 'https://shop.crtlu.me');
define('CRTLU_ALLOWED_ORIGINS', 'https://shop.crtlu.me,http://localhost:4321,http://127.0.0.1:4321');
define('STRIPE_SECRET_KEY', 'sk_live_replace_me');
define('STRIPE_WEBHOOK_SECRET', 'whsec_replace_me');
define('CRTLU_DB_DSN', 'mysql:host=mysql.example.serv00.com;dbname=your_db;charset=utf8mb4');
define('CRTLU_DB_USER', 'your_db_user');
define('CRTLU_DB_PASS', 'your_db_password');
define('CRTLU_ADMIN_USER', 'admin');
define('CRTLU_ADMIN_PASS', 'change_this_password');
```

`CRTLU_ALLOWED_ORIGINS` must include the Cloudflare Pages production domain. Add preview or temporary domains only when you need to test account login or checkout from those domains. For example, if you are testing on `https://shop-crtlu.pages.dev` before `shop.crtlu.me` is ready, include it temporarily:

```php
define('CRTLU_ALLOWED_ORIGINS', 'https://shop-crtlu.pages.dev,https://shop.crtlu.me,http://localhost:4321,http://127.0.0.1:4321');
```

## Database

Fresh install:

```text
database/schema.sql
```

Existing install upgrade order:

```text
database/phase4-migration.sql
database/phase5-migration.sql
```

## Stripe

Checkout is created by Serv00:

```text
https://api.crtlu.me/api/create-checkout-session.php
```

Webhook endpoint:

```text
https://api.crtlu.me/api/stripe-webhook.php
```

Listen for:

```text
checkout.session.completed
```

The checkout success URL is generated from `CRTLU_BASE_URL`, so set it to the public storefront domain.

## Smoke Tests

After deployment, verify:

```text
https://api.crtlu.me/api/health.php
https://api.crtlu.me/api/products.php
https://api.crtlu.me/admin/orders.php
```

Then test from the Cloudflare Pages storefront:

- Product listing and detail pages render.
- Add to cart works.
- Checkout request reaches Serv00 and redirects to Stripe.
- Success page can query `order-status.php`.
- Account login code request reaches Serv00.

## Local Build Check

From `shop/`:

```bash
npm run build
```

Optional local API override:

```bash
PUBLIC_CRTLU_API_BASE_URL=https://api.crtlu.me/api npm run build
```

## Packaging Rules

Never package or commit:

```text
api/config.local.php
.env
.DS_Store
```

Cloudflare Pages needs only the static build output. Serv00 needs the PHP/backend files and data files.
