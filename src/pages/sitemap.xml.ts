import { loadCatalog } from '../lib/catalog-data';
import { absoluteUrl } from '../lib/seo';

const STATIC_ROUTES = [
  { path: '/', priority: '1.0', changefreq: 'weekly' },
  { path: '/products/', priority: '0.9', changefreq: 'weekly' },
  { path: '/contact/', priority: '0.4', changefreq: 'monthly' },
  { path: '/shipping/', priority: '0.4', changefreq: 'monthly' },
  { path: '/returns/', priority: '0.4', changefreq: 'monthly' },
  { path: '/warranty/', priority: '0.4', changefreq: 'monthly' },
  { path: '/privacy/', priority: '0.3', changefreq: 'yearly' },
  { path: '/terms/', priority: '0.3', changefreq: 'yearly' },
];

function xmlEscape(value: string) {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

export function GET() {
  const today = new Date().toISOString().slice(0, 10);
  const productRoutes = loadCatalog().map((item) => {
    const isFeatured =
      item.tier === 'main' ||
      item.tier === 'best-value' ||
      item.tier === 'performance' ||
      ['mecool-km2-plus', 'mecool-km7-plus', 'h96max-m1-plus', 'h96max-h618-plus', 'whdmi-wx50', 'whdmi-wx100'].includes(
        item.id
      );
    return {
      path: `/products/${item.id}/`,
      priority: isFeatured ? '0.85' : item.category === 'tv-box' ? '0.75' : '0.7',
      changefreq: 'weekly' as const,
    };
  });

  const urls = [...STATIC_ROUTES, ...productRoutes].map((route) => [
    '  <url>',
    `    <loc>${xmlEscape(absoluteUrl(route.path))}</loc>`,
    `    <lastmod>${today}</lastmod>`,
    `    <changefreq>${route.changefreq}</changefreq>`,
    `    <priority>${route.priority}</priority>`,
    '  </url>',
  ].join('\n')).join('\n');

  return new Response([
    '<?xml version="1.0" encoding="UTF-8"?>',
    '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    urls,
    '</urlset>',
    '',
  ].join('\n'), {
    headers: {
      'Content-Type': 'application/xml; charset=utf-8',
    },
  });
}
