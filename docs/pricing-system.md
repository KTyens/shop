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

### MECOOL KM series (added 2026-07-15, costs corrected)

Purchase costs confirmed by user 2026-07-15:
- KM7 Plus 2+16G: **RMB 505**
- KM2 Plus 2+16G: **RMB 690**

Pricing: `landed = cost + 40 + 5`, `standard ≈ landed / 7.20 / 0.60`, launch ≈ $5 under standard.

| SKU | Purchase Cost RMB | Landed RMB | Standard USD (compare-at) | Launch Promo USD (sell) | Gross margin @ launch |
|---|---:|---:|---:|---:|---:|
| MECOOL KM7 Plus 2+16G | 505 | 550 | 129.99 | 124.99 | ≈38.9% |
| MECOOL KM2 Plus 2+16G | 690 | 735 | 174.99 | 169.99 | ≈39.9% |

Supplier media: `KJ/03-产品图片/TV BOX/深圳市广进环宇科技有限公司/KM2 Plus` and `KM7 Plus`.

### Wireless HDMI WX50 / WX100 (added 2026-07-15)

Cost source: `成本表.xlsx` → `试算(日元)` rows `WHDMI-wx50-YS` / `WHDMI-wx100-YS`.
Japan logistics from sheet: **RMB 40**; packaging RMB 5.

| SKU | Purchase Cost RMB | Japan ship RMB | Landed RMB | Standard USD | Launch Promo USD |
|---|---:|---:|---:|---:|---:|
| WX50 (CRT-WHDMI-WX50) | 172 | 40 | 217 | 54.99 | 49.99 |
| WX100 (CRT-WHDMI-WX100) | 182 | 40 | 227 | 59.99 | 54.99 |

Media: ziniao `02-图片/无线HDMI` (主图/白底 + 详情).

### Mini keyboards I8 / T18+ (added 2026-07-15)

Cost source: `成本表.xlsx` → `试算(日元)` rows `YS-I8-13` / `T18+61`.
Japan logistics from sheet: **RMB 31** (I8 light parcel) / **RMB 40** (T18+); packaging RMB 5.

| SKU | Purchase Cost RMB | Japan ship RMB | Landed RMB | Standard USD | Launch Promo USD |
|---|---:|---:|---:|---:|---:|
| I8 Mini Keyboard (CRT-KB-I8) | 13 | 31 | 49 | 11.99 | 9.99 |
| T18+ Full Touchpad Keyboard (CRT-KB-T18P) | 61 | 40 | 106 | 24.99 | 19.99 |

Media: `KJ/03-产品图片/TV BOX/i8` and `KJ/03-产品图片/TV BOX/深圳市无限电科技有限公司` (主图白底 + 详情/宣传).

### X96 / X98 series (added / revised 2026-07-15)

Cost sources: `成本表.xlsx` 试算 + **user-confirmed costs 2026-07-15** for S400 / X4 / X98Q 2+16.  
Japan ship default **RMB 40** + packaging **RMB 5**. Formula: `landed/7.20/0.60` → standard `.99`, launch ≈ $5 under.

| Product | SKU | Cost RMB | Sell USD | Compare USD | Notes |
|---|---|---:|---:|---:|---|
| X96Q 1+8 / 2+16 | CRT-X96Q-* | 85 / 100 | 25.99 / 28.99 | +$5 | 试算 |
| X96Q PRO 2+16 | CRT-X96QP-2G16G | 110 | 30.99 | 35.99 | YB827 media; user keep |
| X96 S400 1+8 / 2+16 | CRT-X96-S400-* | **95 / 110** | 27.99 / 30.99 | +$5 | **user cost** |
| X98 PLUS 2+16 / 4+32 / 4+64 | CRT-X98P-* | 150 / 180 / 235 | 40.99 / 47.99 / 59.99 | +$5 | 试算 |
| X96 Max+ Ultra 4+32 / 4+64 | CRT-X96MU-* | 240 / 288 | 60.99 / 72.99 | +$5 | 试算 |
| X96 X4 100M 4+32 / 4+64 | CRT-X96X4-100M-* | **273 / 318** | 68.99 / 79.99 | +$5 | **user cost** |
| X96 X4 1000M 4+32 / 4+64 | CRT-X96X4-1G-* | **283 / 328** | 70.99 / 81.99 | +$5 | **user cost** (64G was 318→328) |
| X98Q 1+8 / 2+16 | CRT-X98Q-* | 68 / **160** | 21.99 / 42.99 | +$5 | 1+8 试算/订单；2+16 **user** |
| X98K 4+32 / 4+64 | CRT-X98K-* | 140 / 205 | 37.99 / 52.99 | +$5 | 试算；2+16 cost 0 skipped |

Not listed: **X96 MINI / X96 AIR** (no media folder). Specs refined from supplier xlsx for S400 / X4 / X98Q / X96Q PRO.

Media root: `KJ/03-产品图片/TV BOX/Gurobaru Konekuto/X96/`.

### Sitewide promo adjustment (2026-07-16)

- **Rule applied:** sell price **unchanged if &lt; $40**; otherwise **≥10% off** snapped to `.99` (compare-at also reduced ~10% or previous sell).
- **Keyboards and entry SKUs under $40** kept (I8, T18+, X96Q/S400 low configs, etc.).
- Source of truth: `data/catalog.json` after this cut.

### Portable thermal printers (added 2026-07-20)

Cost sources: SKU archives and FBM calculations under `/Users/apple/Desktop/Codex Projects/跨境电商/印小签/`. These products contain rechargeable batteries and charge by USB Type-C. They do not require the storefront UK/EU/US wall-plug selector.

The supplier files do not yet contain final Yanwen quotes. The catalog therefore uses conservative planning estimates: RMB 55 for P15/P50 and RMB 110 for the heavier X8T set, plus the standard RMB 5 handling allowance. Recalculate when actual packed-parcel quotes are available.

| Product | SKU | Cost RMB | Estimated Ship RMB | Landed RMB | Active USD | Compare-at USD |
|---|---|---:|---:|---:|---:|---:|
| P15 Portable Label Printer, white | CRT-PRINT-P15-WHITE | 80 | 55 | 140 | 34.99 | 39.99 |
| P50 Wide Label Printer, blue | CRT-PRINT-P50-BLUE | 70 | 55 | 130 | 39.99 | 44.99 |
| X8T A4 Tattoo Transfer Printer Set, white | CRT-PRINT-X8T-WHITE | 180 | 110 | 295 | 79.99 | 89.99 |

P50 is priced above formula floor to preserve its wider-label positioning. X8T includes 10 tattoo transfer sheets and a carry pouch; its higher freight estimate reflects the approximately 860g unit and larger package.
