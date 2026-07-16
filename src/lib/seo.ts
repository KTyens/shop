import type { SeriesItem } from './catalog-data';

export const SITE_URL = 'https://shop.crtlu.me';
export const SITE_NAME = 'CRTLU Digital';
export const DEFAULT_OG_IMAGE = '/assets/hero-cinema.jpg';
export const SUPPORT_EMAIL = 'support@crtlu.me';

export const CORE_SEO_KEYWORDS = [
  'Android TV Box',
  'Google TV Box',
  'Google TV streamer',
  '4K TV Box',
  '4K streaming device',
  'streaming box',
  'TV set-top box',
  'Android set-top box',
  'home cinema projector',
  'compact projector',
  'portable projector',
  'wireless HDMI',
  'wireless HDMI transmitter',
  'mini wireless keyboard',
  'TV box remote keyboard',
  'TV Box Japan',
  'Android TV Box Japan',
  '电视盒子',
  '电视机顶盒',
  '网络机顶盒',
  '华人电视',
  '安卓电视盒子',
  '无线HDMI',
  '迷你无线键盘',
];

/** Brand / model discovery — store brands + common comparison searches (no IPTV claims). */
export const BRAND_DISCOVERY_KEYWORDS = [
  'H96Max',
  'H96 Max',
  'H96Max H618 Plus',
  'H96Max M1 Plus',
  'HK1 RBOX',
  'HK1',
  'MECOOL',
  'MECOOL KM2 Plus',
  'MECOOL KM7 Plus',
  'X96',
  'X96Q',
  'X96Q PRO',
  'X96 S400',
  'X96 X4',
  'X96 Max+ Ultra',
  'X98 PLUS',
  'X98Q',
  'X98K',
  'Tanix',
  'T95',
  'TX98',
  'Rocktek',
  'UBOX12',
  'UBOX',
  'UNBLOCK TECH',
  'UNBLOCK TV BOX',
  '安博盒子',
  '台湾安博',
  'EVPAD',
  '小云电视盒子',
  '易橎',
];

export const SAFE_COMMERCE_KEYWORDS = [
  'legal streaming device',
  'no preloaded paid content',
  'official app ready',
  'official apps only',
  'Stripe checkout',
  'Yanwen shipping',
  'global shipping TV box',
  'Android 14 TV Box',
  'Android 11 TV Box',
  'Wi-Fi 6 TV Box',
  'Bluetooth TV Box',
  '4K HDR TV Box',
  'AV1 TV Box',
  'RK3528 TV Box',
  'RK3588 TV Box',
  'Allwinner H618 TV Box',
  'Allwinner H313 TV Box',
  'Amlogic S905X4 TV Box',
  'Amlogic S905W2 TV Box',
  'buy Android TV box online',
  'Android TV box store',
];

export const CATEGORY_SEO_LABEL: Record<string, string> = {
  'tv-box': 'Android TV Box',
  projector: 'Home Cinema Projector',
  'wireless-hdmi': 'Wireless HDMI Kit',
  accessory: 'TV Box Accessory',
  premium: 'Premium Streaming Device',
};

export function categorySeoLabel(category?: string): string {
  if (!category) return 'Android TV Box';
  return CATEGORY_SEO_LABEL[category] || category.replace(/[-_]/g, ' ');
}

export function absoluteUrl(path = '/') {
  if (/^https?:\/\//i.test(path)) return path;
  const normalized = path.startsWith('/') ? path : `/${path}`;
  return new URL(normalized, SITE_URL).toString();
}

export function dedupeKeywords(values: Array<string | undefined | null | false>) {
  return [...new Set(values.filter(Boolean).map((value) => String(value).trim()).filter(Boolean))];
}

export function productKeywords(product: SeriesItem) {
  const cat = product.category || 'tv-box';
  const catLabel = categorySeoLabel(cat);
  const chipset = product.specs?.Chipset || product.specs?.chipset;
  const memoryHints = (product.variants || []).map((v) => v.label).filter(Boolean);

  return dedupeKeywords([
    product.name,
    product.brand,
    catLabel,
    cat === 'projector' ? 'home cinema projector' : null,
    cat === 'tv-box' || cat === 'premium' ? 'Android TV Box' : null,
    cat === 'wireless-hdmi' ? 'wireless HDMI transmitter receiver' : null,
    cat === 'accessory' ? 'mini keyboard touchpad TV box' : null,
    product.tier,
    chipset,
    ...memoryHints,
    ...(product.specs
      ? Object.entries(product.specs)
          .filter(([k]) => !['shipping_baseline', 'starting_price', 'configurations'].includes(k))
          .map(([, v]) => v)
      : []),
    ...product.variants.flatMap((variant) => [variant.label, variant.sku]),
    `${product.brand} TV Box`,
    `buy ${product.name}`,
    ...CORE_SEO_KEYWORDS,
    ...BRAND_DISCOVERY_KEYWORDS,
    ...SAFE_COMMERCE_KEYWORDS,
  ]);
}

export function productPageTitle(product: SeriesItem): string {
  const cat = categorySeoLabel(product.category);
  const first = product.variants?.[0];
  const configHint = first?.label ? ` ${first.label}` : '';
  // Keep under ~60–70 chars when possible
  return `${product.name}${configHint} ${cat} | ${SITE_NAME}`.replace(/\s+/g, ' ').trim();
}

export function productPageDescription(product: SeriesItem): string {
  const prices = (product.variants || []).map((v) => v.price_cents).filter((n) => n > 0);
  const from = prices.length ? `From $${(Math.min(...prices) / 100).toFixed(2)}. ` : '';
  const cat = categorySeoLabel(product.category);
  return `${product.description} ${from}Shop ${product.name} (${cat}) with secure Stripe checkout, Yanwen tracked shipping, UK/EU/US plug options, and official apps only — no preloaded paid content.`.replace(
    /\s+/g,
    ' '
  ).trim();
}

export function productJsonLd(product: SeriesItem) {
  const productUrl = absoluteUrl(`/products/${product.id}/`);
  const images = [...new Set([product.image, ...(product.gallery || [])].filter(Boolean).map((p) => absoluteUrl(p!)))];
  const firstVariant = product.variants[0];
  const prices = product.variants.map((v) => v.price_cents).filter((n) => n > 0);
  const low = prices.length ? Math.min(...prices) / 100 : undefined;
  const high = prices.length ? Math.max(...prices) / 100 : undefined;
  const catLabel = categorySeoLabel(product.category);

  const offers = product.variants.map((variant) => ({
    '@type': 'Offer',
    sku: variant.sku,
    name: `${product.name} ${variant.label}`,
    url: productUrl,
    priceCurrency: 'USD',
    price: (variant.price_cents / 100).toFixed(2),
    availability: 'https://schema.org/InStock',
    itemCondition: 'https://schema.org/NewCondition',
    priceValidUntil: new Date(Date.now() + 1000 * 60 * 60 * 24 * 90).toISOString().slice(0, 10),
    shippingDetails: {
      '@type': 'OfferShippingDetails',
      shippingRate: {
        '@type': 'MonetaryAmount',
        value: '12.00',
        currency: 'USD',
      },
      shippingDestination: {
        '@type': 'DefinedRegion',
        addressCountry: ['US', 'CA', 'GB', 'DE', 'FR', 'ES', 'IT', 'NL', 'AU', 'JP'],
      },
      deliveryTime: {
        '@type': 'ShippingDeliveryTime',
        handlingTime: {
          '@type': 'QuantitativeValue',
          minValue: 1,
          maxValue: 3,
          unitCode: 'd',
        },
        transitTime: {
          '@type': 'QuantitativeValue',
          minValue: 7,
          maxValue: 18,
          unitCode: 'd',
        },
      },
    },
    seller: {
      '@type': 'Organization',
      name: SITE_NAME,
      url: SITE_URL,
    },
  }));

  return {
    '@context': 'https://schema.org',
    '@type': 'Product',
    name: product.name,
    description: product.description,
    image: images,
    brand: {
      '@type': 'Brand',
      name: product.brand,
    },
    sku: firstVariant?.sku,
    mpn: firstVariant?.sku,
    category: catLabel,
    url: productUrl,
    ...(low != null && high != null
      ? {
          offers: {
            '@type': 'AggregateOffer',
            url: productUrl,
            priceCurrency: 'USD',
            lowPrice: low.toFixed(2),
            highPrice: high.toFixed(2),
            offerCount: String(product.variants.length),
            availability: 'https://schema.org/InStock',
            offers,
          },
        }
      : { offers }),
  };
}

export function breadcrumbJsonLd(items: Array<{ name: string; path: string }>) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      name: item.name,
      item: absoluteUrl(item.path),
    })),
  };
}

export function itemListJsonLd(name: string, products: SeriesItem[]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name,
    numberOfItems: products.length,
    itemListElement: products.map((item, index) => ({
      '@type': 'ListItem',
      position: index + 1,
      url: absoluteUrl(`/products/${item.id}/`),
      name: item.name,
    })),
  };
}

export function websiteJsonLd() {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: SITE_NAME,
    url: SITE_URL,
    description:
      'Android TV boxes, projectors, wireless HDMI kits, and mini keyboards with Stripe checkout and Yanwen shipping.',
    inLanguage: ['en', 'zh-CN', 'ja', 'zh-TW', 'ar'],
    potentialAction: {
      '@type': 'SearchAction',
      target: {
        '@type': 'EntryPoint',
        urlTemplate: `${SITE_URL}/products/?q={search_term_string}`,
      },
      'query-input': 'required name=search_term_string',
    },
  };
}

export function organizationJsonLd() {
  return {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: SITE_NAME,
    url: SITE_URL,
    email: SUPPORT_EMAIL,
    logo: absoluteUrl('/favicon.svg'),
    alternateName: ['CRTLU Digital Shop', 'CRTL U Digital', 'CRTLU'],
    description:
      'Independent store for Android TV boxes, compact projectors, wireless HDMI, and accessories. Hardware only — official apps and your own subscriptions.',
    contactPoint: [
      {
        '@type': 'ContactPoint',
        contactType: 'customer support',
        email: SUPPORT_EMAIL,
        availableLanguage: ['English', 'Chinese', 'Japanese'],
      },
    ],
  };
}

/** Storefront FAQ — helps Google + AI overviews cite clear policies. */
export function storeFaqJsonLd() {
  const faqs = [
    {
      q: 'Does CRTLU Digital include IPTV or paid channels with TV boxes?',
      a: 'No. We sell hardware only. Use official apps and your own valid subscriptions. No preloaded paid content or unofficial streaming accounts are included.',
    },
    {
      q: 'Which countries do you ship to?',
      a: 'Yanwen tracked shipping is available to major destinations including the US, Canada, UK, EU countries, Australia, and Japan. Shipping is a flat USD 12 per order with an estimated 7–18 business days in transit after processing.',
    },
    {
      q: 'What products does CRTLU Digital sell?',
      a: 'Android TV boxes (H96, HK1, MECOOL, X96/X98, Tanix, and more), compact projectors, wireless HDMI kits, and mini wireless keyboards for TV box control.',
    },
    {
      q: 'How do I choose a power plug?',
      a: 'Select UK (BS 1363), EU (Europlug), or US (NEMA 1-15) at checkout based on the delivery country. The plug type is stored with the order for fulfillment.',
    },
  ];
  return {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqs.map((item) => ({
      '@type': 'Question',
      name: item.q,
      acceptedAnswer: { '@type': 'Answer', text: item.a },
    })),
  };
}
