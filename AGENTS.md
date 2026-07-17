# CRTL U Digital Shop — Agent Entry

Git repository root for `shop.crtlu.me`.

## 下次打开项目：先读这个

1. **`docs/CONTINUE.md`** — **当前进度、阻塞项、从哪接着做（权威续做入口）**
2. `docs/记录文档.md` — 变更历史（尤其 §0.10–0.14）
3. `docs/agent-handoff.md` — 全量维护指南
4. `docs/yanwen-api-integration.md` — 燕文 P0–P4
5. `docs/pricing-system.md` / `product-media-guidelines.md` / `published-products.md` — 按需

## Hard rules

- Do not commit `api/config.local.php`, `.env`, or secrets.
- Product source of truth: `data/catalog.json` (keep `public/data/catalog.json` in sync).
- After catalog/UI changes: `npm run build`, then commit relevant `dist/` if this repo deploys from committed dist.
- Push: `git push origin main` → Cloudflare Pages frontend only.
- Backend PHP (`api/`, `admin/`) must be uploaded to Serv00 separately.

## Quick status (2026-07-17)

| 项 | 状态 |
|---|---|
| 整站 | 可运营；物流 API + 登录邮件在验收收尾 |
| 燕文 | 代码 P0–P4 完成；`CHANNEL_ID=481`；WAREHOUSE 可不填 |
| **下一刀** | **登录邮件：Serv00 部署 + Resend/SMTP 配置** → 再真单验燕文 |
| Git tip | `git log -3 --oneline`（mail fix ~`9664050`） |
