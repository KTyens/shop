import fs from 'node:fs';
import path from 'node:path';

/** Approved, verified-buyer reviews snapshot (data/reviews.json, written by `npm run reviews:sync`). */
export interface PublicReview {
  id: number;
  product_id: string;
  rating: number;
  title: string;
  body: string;
  display_name: string;
  verified: boolean;
  date: string;
}

export interface ProductReviewSummary {
  count: number;
  average: number | null;
  reviews: PublicReview[];
}

const EMPTY: ProductReviewSummary = { count: 0, average: null, reviews: [] };

function readSnapshot(): Record<string, any> {
  try {
    const raw = fs.readFileSync(path.join(process.cwd(), 'data', 'reviews.json'), 'utf8');
    const parsed = JSON.parse(raw);
    return parsed && typeof parsed.products === 'object' && !Array.isArray(parsed.products) ? parsed.products : {};
  } catch {
    return {};
  }
}

/** Returns only well-formed reviews; average/count are recomputed here (never trusted blindly). */
export function loadProductReviews(productId: string): ProductReviewSummary {
  const entry = readSnapshot()[productId];
  if (!entry || !Array.isArray(entry.reviews)) return EMPTY;
  const reviews: PublicReview[] = entry.reviews
    .filter((r: any) => r && Number.isInteger(r.rating) && r.rating >= 1 && r.rating <= 5 && typeof r.body === 'string' && r.body.trim())
    .map((r: any) => ({
      id: Number(r.id) || 0,
      product_id: productId,
      rating: r.rating,
      title: String(r.title || '').slice(0, 120),
      body: String(r.body).slice(0, 4000),
      display_name: String(r.display_name || 'Verified buyer').slice(0, 60),
      verified: true,
      date: String(r.date || '').slice(0, 10),
    }));
  if (!reviews.length) return EMPTY;
  const average = Math.round((reviews.reduce((s, r) => s + r.rating, 0) / reviews.length) * 100) / 100;
  return { count: reviews.length, average, reviews };
}
