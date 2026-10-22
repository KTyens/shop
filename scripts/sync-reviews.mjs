#!/usr/bin/env node
// Pull APPROVED reviews from the Serv00 API into data/reviews.json so `npm run build`
// can render them (and aggregateRating JSON-LD) into static product HTML.
// Usage: npm run reviews:sync   (override source with REVIEWS_SOURCE_URL=...)
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const out = path.join(root, 'data', 'reviews.json');
const url = process.env.REVIEWS_SOURCE_URL || 'https://api.crtlu.me/api/reviews.php';

const res = await fetch(url, { headers: { accept: 'application/json' }, signal: AbortSignal.timeout(20000) });
if (!res.ok) {
  console.error(`reviews:sync failed: HTTP ${res.status} from ${url} — keeping existing ${out}`);
  process.exit(1);
}
const data = await res.json();
if (!data || typeof data !== 'object' || typeof data.products !== 'object' || Array.isArray(data.products) && data.products.length) {
  console.error('reviews:sync failed: unexpected payload — keeping existing file');
  process.exit(1);
}
const products = Array.isArray(data.products) ? {} : data.products;
fs.writeFileSync(out, JSON.stringify({ generated_at: data.generated_at || new Date().toISOString(), products }, null, 2) + '\n');
const total = Object.values(products).reduce((n, p) => n + (p?.count || 0), 0);
console.log(`reviews:sync wrote ${total} approved review(s) for ${Object.keys(products).length} product(s) → data/reviews.json`);
