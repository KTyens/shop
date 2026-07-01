# CRTL U Digital Product Design Audit

Date: 2026-06-30

Auditor perspective: product manager + UX/design reviewer.

## Audit Scope

Reviewed surfaces:

1. Homepage desktop hero.
2. Homepage desktop featured products.
3. All-products desktop catalog.
4. HK1 RBOX K8S detail desktop.
5. HK1 RBOX K8S detail specs/images desktop.
6. A10 Projector detail desktop.
7. Cart drawer desktop.
8. Homepage mobile.
9. All-products mobile.
10. HK1 RBOX K8S detail mobile.

Screenshot evidence is saved in this folder:

```text
shop/docs/design-audit-20260630/
```

Evidence files:

```text
01-home-hero-desktop.png
02-home-products-desktop.png
03-all-products-desktop.png
04-k8s-detail-desktop.png
05-k8s-detail-images-desktop.png
06-a10-detail-desktop.png
07-cart-drawer-desktop.png
08-home-mobile.png
09-all-products-mobile.png
10-k8s-detail-mobile.png
```

## User Goal And Accessibility Target

Primary user goal:

Find a reliable TV box or projector, understand the key differences quickly, choose a configuration, and pay with confidence.

Business goal:

Turn Amazon-style product supply into a more premium independent-store experience that feels curated, trustworthy, and technically polished.

Accessibility target:

The site should be readable, navigable, and usable on desktop and mobile with clear focus order, sufficient contrast, meaningful buttons/links, and resilient responsive layout. This audit does not claim WCAG compliance because it is based mainly on screenshots and light DOM inspection.

## Strengths

1. The visual direction is already distinctive.
   - Evidence: `01-home-hero-desktop.png`.
   - The dark cinematic palette, neon green/cyan accents, and large hero type create a stronger brand impression than a generic dropshipping template.

2. The site has a clear product architecture foundation.
   - Evidence: `02-home-products-desktop.png`, `03-all-products-desktop.png`.
   - Homepage is curated, while the all-products page carries the full catalog. This is the right split for avoiding homepage clutter.

3. Real product images are now being used.
   - Evidence: `02-home-products-desktop.png`, `04-k8s-detail-desktop.png`, `06-a10-detail-desktop.png`.
   - This makes the site more credible than the earlier placeholder-card direction.

4. Detail pages already support variants and image galleries.
   - Evidence: `04-k8s-detail-desktop.png`, `10-k8s-detail-mobile.png`.
   - This is a good ecommerce base: users can see the product, compare memory/storage options, and add a selected SKU.

5. Cart drawer is simple and direct.
   - Evidence: `07-cart-drawer-desktop.png`.
   - The drawer keeps users in context and makes the next action obvious.

## UX Risks

### 1. Hero copy is stylish but not concrete enough for purchase intent

Evidence: `01-home-hero-desktop.png`, `08-home-mobile.png`.

The headline "Build a sharper living room signal" feels premium, but it does not immediately say "TV boxes and projectors" or help a cold visitor decide what to buy. The supporting copy explains the category, but users scanning quickly may not understand the offer in the first 2 seconds.

Recommended implementation:

- Change the hero headline to a literal category/offer, for example:
  - `Premium TV Boxes & Compact Projectors`
  - `Android TV Boxes And Projectors, Curated For Home Cinema`
- Add two first-screen category CTAs:
  - `Shop TV Boxes`
  - `Shop Projectors`
- Add one concrete trust line near the CTAs:
  - `Stripe checkout · Yanwen tracked shipping · No unofficial streaming bundles`

Files to change:

```text
shop/index.html
```

### 2. Homepage product cards look good, but image treatment is inconsistent

Evidence: `02-home-products-desktop.png`.

Some product images sit on white rectangular backgrounds while others blend into dark product cards. This makes the page feel assembled from supplier images rather than art-directed. The white boxes are not wrong, but they reduce the "premium tech" feeling.

Recommended implementation:

- Standardize product media blocks with one of these approaches:
  - Option A: keep white product canvases, but put every card image inside the same light product stage.
  - Option B: remove white backgrounds where possible and place products on the dark card stage.
- Give product images a consistent max height and centered alignment.
- Add a subtle product-image frame background, so white-background photos look intentional.

Files to change:

```text
shop/index.html
shop/products/index.html
shop/products/detail.html
shop/assets/products/
```

### 3. All-products page has too much intro before products, especially on mobile

Evidence: `03-all-products-desktop.png`, `09-all-products-mobile.png`.

The all-products page is conceptually correct, but the first mobile viewport shows mostly title and filters, with the actual product list barely starting. For buyers already clicking "All Products", the page should get to products faster.

Recommended implementation:

- Reduce all-products hero height and copy.
- Move product count and filter chips into a compact sticky catalog toolbar.
- Add search and sort:
  - Search by brand/model/SKU.
  - Sort by price low/high.
  - Filter by TV Box / Projector / brand / tier.
- On mobile, collapse brand chips into a horizontal scroll row or a select-style filter drawer.

Files to change:

```text
shop/products/index.html
shop/data/catalog.json
```

### 4. Detail pages need stronger purchase reassurance

Evidence: `04-k8s-detail-desktop.png`, `06-a10-detail-desktop.png`.

The detail hero is visually strong, but after price and variants there is not enough confidence-building information near the buy button. Users buying from an independent shop need shipping, payment, return, compatibility, and package-content reassurance before checkout.

Recommended implementation:

Add a compact trust strip near the Add to Cart area:

```text
Stripe secure checkout
Yanwen tracked shipping
7-18 business day estimate
Carefully checked configurations
```

Add detail sections:

- `What's in the box`
- `Best for`
- `Compatibility`
- `Shipping and after-sale notes`

Files to change:

```text
shop/products/detail.html
shop/products/h96max-plus.html
shop/data/catalog.json
```

### 5. Product comparison is present on homepage but not helpful enough at decision time

Evidence: `02-home-products-desktop.png`, `04-k8s-detail-desktop.png`.

Users choosing between many similar TV boxes need guidance. The site currently shows many specs, but it does not explain the decision in buyer language.

Recommended implementation:

- Add a "Choose by need" section:
  - Cheapest starter box
  - Best value Android 14 box
  - Better for 4K HDR
  - Projector for bedroom
- Add "Why choose this model" bullets on detail pages.
- Add a small comparison table on all product detail pages, not only the H96 custom page.

Files to change:

```text
shop/index.html
shop/products/detail.html
shop/data/catalog.json
```

### 6. Mobile navigation is too thin

Evidence: `08-home-mobile.png`, `09-all-products-mobile.png`.

On mobile, the top nav effectively becomes brand + cart. Users can still use hero buttons, but there is no persistent way to jump to products, categories, shipping, or search after scrolling.

Recommended implementation:

- Add a mobile menu button or a compact bottom/sticky bar:
  - `Products`
  - `TV Box`
  - `Projector`
  - `Cart`
- Keep the cart icon, but do not make cart the only persistent action.

Files to change:

```text
shop/index.html
shop/products/index.html
shop/products/detail.html
```

### 7. Detail mobile page uses a lot of vertical space before decision content

Evidence: `10-k8s-detail-mobile.png`.

The mobile detail page shows product image and thumbnails first, which is expected, but the thumbnail grid is dense. For mobile buyers, title, price, selected variant, and Add to Cart should appear quickly.

Recommended implementation:

- Use horizontally scrollable thumbnails on mobile instead of a 4-column grid.
- Keep title, price, and selected variant closer to the first viewport.
- Consider a sticky mobile buy bar after scrolling:
  - Product name
  - Selected price
  - Add to Cart

Files to change:

```text
shop/products/detail.html
shop/products/h96max-plus.html
```

### 8. Cart drawer works, but it lacks trust and product context

Evidence: `07-cart-drawer-desktop.png`.

The drawer is clean, but the line item is text-only. It does not show a product thumbnail, shipping estimate context, or checkout reassurance. This is a missed trust moment right before payment.

Recommended implementation:

- Add product thumbnail to each cart item.
- Add "Tracked shipping" and "Secure Stripe checkout" under the total.
- Show estimated delivery window near shipping cost.
- If checkout configuration is missing, show a more user-friendly unavailable state instead of a technical backend note.

Files to change:

```text
shop/index.html
shop/products/detail.html
shop/products/h96max-plus.html
```

## Accessibility Risks

1. Muted gray text on dark backgrounds may be low contrast in some areas.
   - Evidence: `03-all-products-desktop.png`, `05-k8s-detail-images-desktop.png`.
   - Check text such as secondary descriptions, SKU labels, and inactive filters.

2. Focus states are not visible from screenshots.
   - Need keyboard testing for nav links, filters, variant cards, thumbnails, drawer close, quantity controls, and checkout.

3. Mobile nav discoverability is limited.
   - Evidence: `08-home-mobile.png`.
   - The hidden desktop nav needs an accessible mobile equivalent, not only hero buttons.

4. Cart drawer should trap focus when open.
   - Evidence: `07-cart-drawer-desktop.png`.
   - The screenshot confirms the drawer overlay, but keyboard focus behavior still needs testing.

5. Product image thumbnails need clear active states and labels.
   - Evidence: `10-k8s-detail-mobile.png`.
   - The active image state is visually indicated, but screen reader labels and keyboard operation need verification.

## Opportunity Areas

### Quick Wins

1. Rewrite hero copy to be more literal and commerce-focused.
2. Standardize card image backgrounds.
3. Add search and sorting to all-products.
4. Add trust strip near Add to Cart.
5. Add mobile nav/menu.
6. Add product thumbnails to cart items.

### Medium Iterations

1. Add a guided product chooser.
2. Add comparison content on generic detail pages.
3. Add sticky mobile buy bar.
4. Add image lightbox for detail galleries.
5. Add stock and shipping estimates per product/category.

### Longer-Term Product Improvements

1. Build a reusable design token layer instead of repeated page-level CSS.
2. Create shared cart and product card components.
3. Add analytics events for:
   - hero CTA clicks
   - filter use
   - detail page variant changes
   - add-to-cart
   - checkout start
4. A/B test hero copy and category-first homepage layout.

## Priority Recommendations

### P0: Conversion Clarity

Implement first:

1. Hero headline and CTAs.
2. Detail trust strip.
3. Cart reassurance copy.
4. Mobile navigation.

Why:

These directly affect whether a first-time visitor understands the store and trusts checkout.

### P1: Catalog Usability

Implement next:

1. All-products search.
2. Sort by price.
3. Sticky/collapsed mobile filters.
4. "Choose by need" buying guide.

Why:

The catalog is already large enough that browsing all products manually can feel heavy.

### P2: Visual Polish

Implement after P0/P1:

1. Normalize image backgrounds.
2. Add lightbox or better gallery layout.
3. Unify section spacing and card image proportions.

Why:

This will lift the premium feeling, but the first priority should be decision clarity and trust.

## Suggested Implementation Plan

### Phase 1: One-Day UI/Conversion Pass

Files:

```text
shop/index.html
shop/products/detail.html
shop/products/h96max-plus.html
```

Changes:

- Rewrite hero headline/subcopy.
- Add two category CTAs.
- Add detail-page trust strip.
- Add mobile nav/menu.
- Add cart reassurance and product thumbnails.

### Phase 2: Catalog UX Pass

Files:

```text
shop/products/index.html
shop/data/catalog.json
```

Changes:

- Compact catalog header.
- Add search input.
- Add sort by price.
- Improve mobile filter layout.
- Add visible active-filter summary.

### Phase 3: Product Guidance Pass

Files:

```text
shop/index.html
shop/products/detail.html
shop/data/catalog.json
```

Changes:

- Add "Choose by need" section.
- Add detail-page "Best for" and "Why this model" content.
- Add comparison snippets across generic detail pages.

## Evidence Limits

This audit used current local screenshots and light DOM inspection. It did not complete:

- Full keyboard navigation testing.
- Real Stripe checkout submission.
- Screen reader output.
- Color contrast measurement with exact computed values.
- Performance testing on low-end mobile devices.

Those should be checked before claiming accessibility or production quality.

