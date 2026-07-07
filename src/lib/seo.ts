import type { SeriesItem } from './catalog-data';

export const SITE_URL = 'https://shop.crtlu.me';
export const SITE_NAME = 'CRTLU Digital';
export const DEFAULT_OG_IMAGE = '/assets/hero-cinema.png';

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
  'TV Box Japan',
  '电视盒子',
  '电视机顶盒',
  '网络机顶盒',
  '华人电视',
];

export const BRAND_DISCOVERY_KEYWORDS = [
  'H96Max',
  'HK1 RBOX',
  'Mecool',
  'Rocktek',
  'Tanix',
  'T95',
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
  'Stripe checkout',
  'Yanwen shipping',
  'Android 14 TV Box',
  'Wi-Fi 6 TV Box',
  'Bluetooth TV Box',
  '4K HDR TV Box',
  'RK3528 TV Box',
  'Allwinner TV Box',
  'Amlogic TV Box',
];

export function absoluteUrl(path = '/') {
  if (/^https?:\/\//i.test(path)) return path;
  const normalized = path.startsWith('/') ? path : `/${path}`;
  return new URL(normalized, SITE_URL).toString();
}

export function dedupeKeywords(values: Array<string | undefined | null | false>) {
  return [...new Set(values.filter(Boolean).map((value) => String(value).trim()).filter(Boolean))];
}

export function productKeywords(product: SeriesItem) {
  return dedupeKeywords([
    product.name,
    product.brand,
    product.category === 'projector' ? 'projector' : 'TV Box',
    product.category === 'projector' ? 'home cinema projector' : 'Android TV Box',
    product.tier,
    ...(product.specs ? Object.values(product.specs) : []),
    ...product.variants.flatMap((variant) => [variant.label, variant.sku]),
    ...CORE_SEO_KEYWORDS,
    ...BRAND_DISCOVERY_KEYWORDS,
    ...SAFE_COMMERCE_KEYWORDS,
  ]);
}

export function productJsonLd(product: SeriesItem) {
  const productUrl = absoluteUrl(`/products/${product.id}/`);
  const images = [...new Set([product.image, ...(product.gallery || [])].filter(Boolean).map(absoluteUrl))];
  const firstVariant = product.variants[0];

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
    category: product.category === 'projector' ? 'Home Cinema Projector' : 'Android TV Box',
    url: productUrl,
    offers: product.variants.map((variant) => ({
      '@type': 'Offer',
      sku: variant.sku,
      name: `${product.name} ${variant.label}`,
      url: productUrl,
      priceCurrency: 'USD',
      price: (variant.price_cents / 100).toFixed(2),
      availability: 'https://schema.org/InStock',
      itemCondition: 'https://schema.org/NewCondition',
      seller: {
        '@type': 'Organization',
        name: SITE_NAME,
      },
    })),
  };
}

export function websiteJsonLd() {
  return {
    '@context': 'https://schema.org',
    '@type': 'WebSite',
    name: SITE_NAME,
    url: SITE_URL,
    potentialAction: {
      '@type': 'SearchAction',
      target: `${SITE_URL}/products/?q={search_term_string}`,
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
    email: 'support@crtlu.me',
    alternateName: ['CRTLU Digital Shop', 'CRTL U Digital'],
  };
}
