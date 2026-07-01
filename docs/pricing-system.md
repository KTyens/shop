# TV Box Pricing System

Last updated: 2026-06-30

This document is the pricing reference for MoonTV / shop.crtlu.me TV Box and projector products. Other agents should use this as the default pricing system unless the user provides a newer cost sheet.

## Scope

- Product category: Android TV Box / streaming box products, plus selected compact projectors.
- Current logistics baseline: Yanwen standard shipping from China.
- Current target market assumption: Japan first, then US, Canada, UK, EU, Australia, and New Zealand.
- Currency displayed on site: USD by default.

## Cost Inputs

Use this baseline unless the user gives a newer value:

| Cost Item | Default Value | Notes |
|---|---:|---|
| Product purchase cost | From supplier cost sheet | RMB |
| Standard Yanwen shipping | RMB 40 | China to Japan, about 500g, 26 x 16 x 10 cm |
| Projector Yanwen shipping | RMB 60 | China to Japan estimate confirmed by user on 2026-06-30 |
| Packaging and handling | RMB 5 | Box material, label, basic labor buffer |
| Payment fee buffer | Included in margin check | Stripe/card fees should be considered when validating margin |
| Reference exchange rate | 7.20 RMB/USD | Update only when material pricing changes |

If the supplier sheet includes a separate logistics value such as RMB 35, keep the public price unchanged unless the total landed cost changes by more than RMB 15.

For projectors, use RMB 60 shipping instead of the TV Box RMB 40 baseline unless the user provides a newer freight estimate.

## Pricing Formula

Default formula:

```text
landed_cost_rmb = purchase_cost_rmb + shipping_rmb + packaging_handling_rmb
suggested_price_usd = landed_cost_rmb / exchange_rate / 0.60
```

The `0.60` divisor targets about 40% gross margin before ad spend.

Use this stricter formula for products that will be pushed with paid ads:

```text
ad_ready_price_usd = landed_cost_rmb / exchange_rate / 0.56
```

This leaves more room for ad testing, refunds, payment fees, and support.

## Margin Target

For TV Box independent store sales:

- Normal target gross margin: 38% to 42%.
- Acceptable launch promotion gross margin: 34% to 38%.
- Avoid going below 33% unless it is a deliberate limited-time traffic product.
- Prefer psychological prices ending in `.99`.

## Current SKU Pricing

Corrected cost sheet from user, 2026-06-30:

| SKU | Purchase Cost RMB | Estimated Landed Cost RMB | Standard Price USD | Launch Promo USD | Role |
|---|---:|---:|---:|---:|---|
| H96Max H618 Plus 2+16G | 170 | 215 | 52.99 | 49.99 | Entry product |
| H96Max H618 Plus 4+32G | 230 | 275 | 69.99 | 64.99 | Main product |
| H96Max H618 Plus 4+64G | 300 | 345 | 84.99 | 79.99 | High-spec profit product |
| H96Max M1 Plus 2+16G | 165 | 210 | 49.99 | 47.99 | Entry product |
| H96Max M1 Plus 4+32G | 220 | 265 | 64.99 | 59.99 | Main product |
| H96Max M1 Plus 4+128G | 295 | 340 | 79.99 | 74.99 | High-storage profit product |

Projector additions from user, 2026-06-30. Japan freight baseline is RMB 60:

| SKU | Purchase Cost RMB | Estimated Landed Cost RMB | Standard Price USD | Launch Promo USD | Role |
|---|---:|---:|---:|---:|---|
| HY300 PRO Projector | 100 | 165 | 39.99 | 34.99 | Entry projector |
| HY320 MINI Projector | 160 | 225 | 54.99 | 49.99 | Main compact projector |
| A10 Projector | 250 | 315 | 74.99 | 69.99 | Higher projector option |

Primary products to feature:

1. H96Max M1 Plus 4+32G: standard price `64.99`, launch promo `59.99`.
2. H96Max H618 Plus 4+32G: standard price `69.99`, launch promo `64.99`.
3. H96Max M1 Plus 4+128G: standard price `79.99`, launch promo `74.99`.

## Shipping Policy

Default shipping strategy:

| Region | Customer-Facing Shipping Rule |
|---|---|
| Japan | Free over USD 69, otherwise USD 6.99 |
| United States / Canada | Free over USD 79, otherwise USD 9.99 |
| UK / EU | Free over USD 79, otherwise USD 9.99 to 12.99 |
| Australia / New Zealand | Free over USD 79, otherwise USD 8.99 |
| Remote or high-risk countries | Manual quote or flat USD 19.99 |

Use Yanwen as the default shipping method. UPS, DHL, and FedEx should be treated as paid express upgrades, not the default option, because express freight can destroy margin on USD 50 to 85 products.

## New Product Pricing Workflow

When adding a new TV Box product:

1. Read the supplier RMB purchase cost.
2. Add the current shipping baseline and packaging/handling:

   ```text
   landed_cost_rmb = purchase_cost_rmb + 40 + 5
   ```

3. Convert to USD using the current reference rate:

   ```text
   landed_cost_usd = landed_cost_rmb / 7.20
   ```

4. Calculate the base standard price:

   ```text
   base_price_usd = landed_cost_usd / 0.60
   ```

5. Round to a clean psychological price:

   - Under USD 50: use `x7.99`, `x9.99`, or `x4.99`.
   - USD 50 to 90: use `x9.99` or `x4.99`.
   - Higher-ticket products: keep at least 38% gross margin after rounding.

6. Set launch promo about USD 3 to 5 below the standard price.
7. Do not discount below 33% gross margin without user approval.

## Price Update Triggers

Recalculate prices if any of these change:

- Supplier purchase cost changes by more than RMB 15.
- Average Yanwen shipping changes by more than RMB 10.
- Exchange rate moves outside 6.90 to 7.50 RMB/USD.
- Stripe/payment provider fee changes materially.
- The store starts paid ads at scale.
- The store expands into regions where the real shipping cost is materially higher than Japan.

## Implementation Notes For Agents

- Treat `Standard Price USD` as the normal catalog price.
- Treat `Launch Promo USD` as the temporary sale price during launch or campaigns.
- If the storefront supports compare-at pricing, use standard price as compare-at and launch promo as active price.
- Do not overwrite this document with guessed marketplace prices. Marketplace checks can inform positioning, but supplier cost plus margin target is the source of truth.
- If the user provides a newer cost screenshot, create a new dated note or update this document with the new source date.
