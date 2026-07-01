import fs from 'node:fs';
import path from 'node:path';

export interface Variant {
  id: string;
  label: string;
  sku: string;
  price_cents: number;
  compare_at_cents?: number;
  seriesId?: string;
  seriesName?: string;
  brand?: string;
  tier?: string;
  category?: string;
  description?: string;
}

export interface SeriesItem {
  id: string;
  name: string;
  brand: string;
  tier: string;
  category: string;
  description: string;
  image: string;
  variants: Variant[];
  specs?: Record<string, string>;
  detail_url?: string;
  gallery?: string[];
}

export function loadCatalog(): SeriesItem[] {
  const projectRoot = process.cwd();
  const catalogPath = path.join(projectRoot, 'data', 'catalog.json');
  const catalogData = JSON.parse(fs.readFileSync(catalogPath, 'utf-8'));
  return (catalogData.series || []).filter((item) => item.status === 'published');
}
