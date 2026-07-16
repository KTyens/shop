import fs from 'node:fs';
import path from 'node:path';

// Vite watches this import so HMR invalidates when catalog.json changes.
// Runtime loadCatalog() still prefers the live file on disk (admin edits).
import catalogImport from '../../data/catalog.json';

export interface Variant {
  id: string;
  label: string;
  sku: string;
  price_cents: number;
  compare_at_cents?: number;
  rmb_price?: number;
  seriesId?: string;
  seriesName?: string;
  brand?: string;
  tier?: string;
  category?: string;
  description?: string;
  image?: string;
}

export interface PlugType {
  id: string;
  label: string;
  code: string;
}

export interface SeriesItem {
  id: string;
  name: string;
  brand: string;
  tier: string;
  category: string;
  description: string;
  image: string;
  status?: string;
  variants: Variant[];
  specs?: Record<string, string>;
  detail_url?: string;
  gallery?: string[];
}

export interface CatalogData {
  updated?: string;
  currency?: string;
  plug_types?: PlugType[];
  series?: SeriesItem[];
  pending?: unknown[];
}

export function publicAssetPath(value: string | undefined): string {
  if (!value) return '';
  if (/^(https?:)?\/\//i.test(value) || value.startsWith('/')) return value;
  return `/${value.replace(/^\/+/, '')}`;
}

/** Append cache-busting query so replaced images show without hard-refresh. */
export function withAssetVersion(url: string | undefined, version?: string): string {
  const base = publicAssetPath(url);
  if (!base || !version) return base;
  if (/^(https?:)?\/\//i.test(base)) return base;
  const sep = base.includes('?') ? '&' : '?';
  return `${base}${sep}v=${encodeURIComponent(version)}`;
}

function normalizeSeriesItem(item: SeriesItem, version?: string): SeriesItem {
  const image = withAssetVersion(item.image, version);
  const gallery = Array.isArray(item.gallery)
    ? item.gallery.map((g) => withAssetVersion(g, version)).filter(Boolean)
    : item.gallery;
  return {
    ...item,
    image,
    gallery,
  };
}

function catalogPath(): string {
  return path.join(process.cwd(), 'data', 'catalog.json');
}

/** Read catalog: live disk first (picks up admin edits), import as fallback. */
export function readCatalogData(): CatalogData {
  try {
    const live = JSON.parse(fs.readFileSync(catalogPath(), 'utf-8')) as CatalogData;
    if (live && Array.isArray(live.series)) return live;
  } catch {
    // fall through
  }
  return catalogImport as CatalogData;
}

/** Version string for cache-busting product images. */
export function catalogMediaVersion(data?: CatalogData): string {
  const catalog = data || readCatalogData();
  if (catalog.updated) return String(catalog.updated);
  try {
    const st = fs.statSync(catalogPath());
    return String(Math.floor(st.mtimeMs));
  } catch {
    return '';
  }
}

export function loadCatalog(): SeriesItem[] {
  const catalogData = readCatalogData();
  const version = catalogMediaVersion(catalogData);
  return (catalogData.series || [])
    .filter((item) => (item.status || 'published') === 'published')
    .map((item) => normalizeSeriesItem(item, version));
}

export function loadSeriesById(id: string): SeriesItem | undefined {
  return loadCatalog().find((item) => item.id === id);
}

export function loadPlugTypes(): PlugType[] {
  const catalogData = readCatalogData();
  const plugTypes = Array.isArray(catalogData.plug_types) ? catalogData.plug_types : [];
  return plugTypes
    .map((plug) => ({
      id: String(plug.id || '').trim(),
      label: String(plug.label || '').trim(),
      code: String(plug.code || '').trim(),
    }))
    .filter((plug) => plug.id && plug.label);
}
