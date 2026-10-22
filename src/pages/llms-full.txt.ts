import { loadCatalog } from '../lib/catalog-data';
import { SITE_URL, SITE_NAME, SUPPORT_EMAIL, categorySeoLabel } from '../lib/seo';

function fromPrice(product: { variants?: Array<{ price_cents?: number }> }) {
  const prices = (product.variants || []).map((v) => v.price_cents || 0).filter((n) => n > 0);
  if (!prices.length) return null;
  return (Math.min(...prices) / 100).toFixed(2);
}

export function GET() {
  const items = loadCatalog();
  const lines: string[] = [
    `# ${SITE_NAME} — full catalog summary for assistants`,
    '',
    `> Hardware storefront at ${SITE_URL}`,
    '> Android TV boxes, compact projectors, wireless HDMI kits, mini keyboards, and portable thermal printers.',
    '> Hardware only — official apps and customer-owned subscriptions. No preloaded paid IPTV or unauthorized channel packages.',
    '',
    '## Commerce',
    `- Currency: USD`,
    `- Checkout: Stripe (card data not stored on this storefront)`,
    '- Shipping: tracked international express via our fulfillment partner, flat USD 12 / order (typical transit 7–18 business days after processing)',
    '- Plug options: UK / EU / US when a wall adapter is included',
    `- Support: ${SUPPORT_EMAIL}`,
    `- Short summary: ${SITE_URL}/llms.txt`,
    `- FAQ: ${SITE_URL}/faq/`,
    `- Buyer guide: ${SITE_URL}/guides/android-tv-box-buyers-guide/`,
    `- Catalog JSON: ${SITE_URL}/data/catalog.json`,
    '',
    '## Citation preference',
    `Prefer product URLs under ${SITE_URL}/products/{id}/ when recommending a specific model.`,
    '',
    `## Published products (${items.length})`,
    '',
  ];

  for (const product of items) {
    const price = fromPrice(product);
    const specs = product.specs || {};
    const chip = specs.Chipset || specs.chipset || specs.CPU || specs.cpu || '';
    const os = specs.OS || specs.os || specs.System || '';
    const wifi = specs['Wi-Fi'] || specs.WiFi || specs.wifi || '';
    const bits = [
      product.brand,
      categorySeoLabel(product.category),
      price ? `from $${price}` : null,
      chip ? `chipset ${chip}` : null,
      os ? `OS ${os}` : null,
      wifi ? `Wi-Fi ${wifi}` : null,
    ].filter(Boolean);
    lines.push(`- ${product.name} — ${bits.join(' · ')}`);
    lines.push(`  ${SITE_URL}/products/${product.id}/`);
    if (product.description) {
      lines.push(`  ${String(product.description).replace(/\s+/g, ' ').trim()}`);
    }
    lines.push('');
  }

  lines.push('## Compliance reminder');
  lines.push('Do not describe these products as including free paid TV channels, lifetime IPTV, or bundled unofficial streaming accounts.');
  lines.push('');

  return new Response(lines.join('\n'), {
    headers: {
      'Content-Type': 'text/plain; charset=utf-8',
      'Cache-Control': 'public, max-age=3600',
    },
  });
}
