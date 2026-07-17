# CRTL U Digital Shop — Agent Entry

Git repository root for `shop.crtlu.me`.

## Read first

1. `docs/agent-handoff.md` — full maintenance guide
2. `docs/记录文档.md` — **change history, git milestones, specs work, continue-task notes**
3. `docs/pricing-system.md`
4. `docs/product-media-guidelines.md`
5. `docs/published-products.md`
6. `docs/yanwen-api-integration.md` — **燕文 API 用法、能力边界（免填单号/实时轨迹尚未上线）**

## Hard rules

- Do not commit `api/config.local.php`, `.env`, or secrets.
- Product source of truth: `data/catalog.json` (keep `public/data/catalog.json` in sync).
- After catalog/UI changes: `npm run build`, then commit relevant `dist/` if this repo deploys from committed dist.
- Push: `git push origin main` → Cloudflare Pages frontend only.
- Backend PHP (`api/`, `admin/`) must be uploaded to Serv00 separately.

## Quick status (see 记录文档 for details)

- Latest specs work commit: `1a60638` (29 series detailed specs)
- Handoff doc commit: see `git log -- docs/记录文档.md`
