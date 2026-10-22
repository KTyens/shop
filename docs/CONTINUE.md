# 续做入口（下次打开项目先读这里）

**Last session:** 2026-07-21  
**Project path:** `/Users/apple/Desktop/Codex Projects/独立站/shop`  
**Live:** `https://shop.crtlu.me`（前端 CF Pages）· `https://api.crtlu.me`（PHP Serv00）  
**Git:** `origin/main` → `https://github.com/KTyens/shop.git`  
**Tip (this arc):** `b5bfe70` category deep-link · `72ebece` PDP hydrate · `4f94d96` store-catalog + delete · `61d972f` dual-host preview  

**当日完整账本：** `docs/记录文档.md` → **§0.15 2026-07-21**

---

## 0. 给 Agent 的第一句话

> 读 `docs/CONTINUE.md` + `docs/记录文档.md`（**§0.15** 与 0.10–0.14）。  
> 整站可卖货；**后台换图已可前台实时见**（双宿主 + store-catalog）；**勿整站回档 Serv00 7/19 备份**。  
> **下一优先：燕文线上真单验收**（`CHANNEL_ID=481` + Serv00 后端包）。  
> 登录/账户/改图链路本周已收口，勿无故重做整站。

---

## 0.1 真实买家评价系统（分支 `feat/verified-reviews`，2026-10-22，未推送 / 未部署）

**目标：** 只有真实下单用户能评价；默认待审核；产品页静态 HTML 中仅在有已通过评价时输出 `aggregateRating` / `review` JSON-LD（绝不伪造，不导入亚马逊评价，无种子数据）。

### 新增 / 修改文件
- 后端：`api/reviews-lib.php`（HMAC 令牌、订单商品→产品映射、清洗、限流）、`api/review-submit.php`（GET 校验链接 / POST 提交）、`api/reviews.php`（公开已通过评价 + 平均分 + 数量）、`admin/reviews.php`（审核：通过/拒绝/删除 + 为订单生成评价链接）
- `api/stripe-webhook.php`：`order_items` 写入 `variant_id` / `product_id`（列存在时才写，未迁移也不报错）
- `api/notifications.php`：新增 `crtlu_send_shipped_email()`（发货邮件含物流号 + 评价链接）
- `admin/orders.php`：订单状态**首次**改为 `shipped` 时自动发送上面的发货邮件（每单只发一次）
- 前台：`src/lib/reviews.ts`、`src/components/ProductReviews.astro`、`src/pages/review.astro`（`/review/?t=…`）、`src/lib/seo.ts`（`reviewJsonLdFields`）、`src/pages/products/[slug].astro`、`src/layouts/Layout.astro`（JSON-LD 转义 `<`，防 `</script>` 注入）
- 数据：`data/reviews.json`（已通过评价快照，初始为空）、`scripts/sync-reviews.mjs`、`package.json`（`reviews:sync`、`build:with-reviews`）
- SQL：`database/phase6-reviews-migration.sql`

### Serv00 需要做的（按顺序）
1. MySQL 执行 `database/phase6-reviews-migration.sql`（给 `order_items` 加 `variant_id`、`product_id`；建 `product_reviews`、`review_rate_limits`）。若提示 Duplicate column 可跳过该句。
2. `api/config.local.php`（**只在服务器上改，不进 git**）新增：
   ```php
   define('CRTLU_REVIEW_SECRET', '<64位hex>'); // php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
   ```
   ≥32 字符；更换后已发出的评价链接全部失效。未配置时评价接口返回 503，网站其余功能不受影响。
3. 上传 `api/`、`admin/`、`database/`（`scripts/pack-serv00-backend.sh` 打包，自动排除 `config.local.php`）。保留服务器上已有的 `config.local.php`。
4. 验证：`https://api.crtlu.me/api/reviews.php` 应返回 `{"products":{}}`；后台顶部出现「评价」。

### 流程
1. 下单 → webhook 写订单与 `order_items.product_id`（老订单按商品名匹配 catalog 回退）。
2. 后台订单改为「已发货」→ 自动发「has shipped」邮件，含 `https://shop.crtlu.me/review/?t=<token>`。也可在后台「评价」页输入订单号手动生成链接。
3. 令牌 = base64url(订单号、order_item ids、产品 ids、过期时间 180 天) + HMAC-SHA256(`CRTLU_REVIEW_SECRET`)。
4. `/review/` 页面 GET 校验：签名有效 + 未过期 + 订单存在且状态 ∈ paid/processing/shipped/delivered；逐商品显示表单（已评价显示感谢）。
5. POST：每个订单商品唯一（`uniq_review_order_item`），评分 1–5，标题≤120，正文 10–4000，署名≤60（空=Verified buyer）；去 HTML/控制字符、禁止链接、蜜罐字段；限流：每 IP 每小时 POST 10 次 / GET 60 次，每订单每天 20 次。入库 `status='pending'`。
6. 后台「评价」页审核通过 → `reviews.php` 公开返回。
7. 产品页：构建时读 `data/reviews.json` 输出星级/平均分/列表和 JSON-LD；页面加载后客户端再拉 `/api/reviews.php?product=` 实时刷新列表（审核后到下次构建之间也能看到）。零评价显示「No reviews yet」，不输出 aggregateRating。

### 前台发布（CF Pages）
```bash
npm run reviews:sync     # 从 https://api.crtlu.me/api/reviews.php 拉已通过评价 → data/reviews.json
npm run build            # 需 Node ≥ 22.12
git add data/reviews.json dist && git commit -m "chore: sync approved reviews" && git push origin main
```
审核通过新评价后重复以上步骤，JSON-LD 才会更新。`reviews:sync` 失败时保留旧文件并退出非 0。

### 已知 / 待办
- 未在真实 MySQL 上端到端测过提交流程（本地仅单元测试令牌、清洗、映射、JSON-LD 构建）；上线后用一笔测试单走一遍，测完在后台删除该评价。
- 订单确认邮件（付款时）未加评价链接——刻意放在发货邮件，用户收到货后再评价。

---

## 1. 当前状态（相对阻塞）

| 项 | 状态 |
|---|---|
| 登录验证码邮件（Resend） | ✅ 线上可用 |
| 登录 Session / 账户页 | ✅ |
| 后台产品列表缩略图 | ✅ 双宿主：本地或 shop CDN |
| 后台换图 → 前台更新 | ✅ live `store-catalog.php` + 详情页 hydrate（`72ebece`） |
| 后台删除产品 | ✅ 编辑页危险区（输 ID 确认） |
| 首页分类跳转 | ✅ `?category=`（`b5bfe70`，部署后强刷） |
| 燕文 P2–P4 线上真单 | ⏳ **下一优先** |

---

## 2. 整站进度

| 大块 | 状态 |
|---|---|
| 前台商城 / Stripe / catalog | ✅ 可运营 |
| 打印机 P15 / P50 / X8T | ✅ 前台 + catalog；双宿主媒体模型已定 |
| 后台改图 / 删产品 / 实时 catalog | ✅ 2026-07-21 |
| 燕文 P0–P4 代码 | ✅ |
| 燕文线上验收（真单） | ⏳ |

**整体阶段：** 可卖货运营中后期；物流 API 真单验收是主要未完成业务项。

---

## 3. 双宿主媒体（硬规则）

| 层 | 宿主 | 职责 |
|---|---|---|
| 全量产品图 + 静态站 | CF Pages `shop.crtlu.me` | 主图库 |
| API / 后台 / 运营 catalog | Serv00 `api.crtlu.me` | 轻逻辑；**仅**后台刚上传的覆盖图 |

- 后台预览：`crtlu_local_asset_url` → 本地有则本地，否则 `https://shop.crtlu.me/...`  
- 前台实时：`/api/store-catalog.php`（CF 反代）→ Serv00 上有文件的图改写为 `https://api.crtlu.me/assets/...?v=mtime`  
- **禁止**为修预览把整库图上传 Serv00  
- **禁止**用 `backups/local/20260719` 整目录覆盖 `public_html`

---

## 4. 燕文（快递）

| 阶段 | 能力 | 代码 | 线上 |
|---|---|---|---|
| P0–P1 | 签名 / 列表 | ✅ | 可用 |
| P2–P4 | 运单 / 标签 / 轨迹 | ✅ | ⏳ 真单验收 |

```php
define('YANWEN_CHANNEL_ID', '481'); // 燕文专线追踪-普货
// YANWEN_WAREHOUSE_CODE 可不填
```

---

## 5. 部署架构

| 层 | 宿主 | 更新方式 |
|---|---|---|
| 前端 | CF Pages | `git push origin main`（含 `dist/`） |
| 后端 | Serv00 | 手动上传 zip/PHP；**永不覆盖丢** `api/config.local.php` |

近期 Serv00 包：

- `独立站/crtlu-serv00-live-catalog-delete-20260721.zip`（store-catalog + admin 删产品/双宿主）  
- `独立站/crtlu-serv00-admin-image-preview-fix-20260721.zip`（仅预览早期包）  
- 更早：mail-fix / session-fix / account-schema-fix / printer-update 等  

---

## 6. 建议下次顺序

1. **燕文真单** — 确认 `YANWEN_CHANNEL_ID=481`；上传含 P2/P3/P4 的 PHP；后台创建运单 → 标签 → 前台轨迹  
2. 可选：把 Serv00 仅有的覆盖图/catalog 变更回写 Git，清理 CF 陈旧静态  
3. 可选：删除测试品、对齐 api/shop 两份 catalog 分叉  

---

## 7. 硬性规则

- 不提交 `api/config.local.php` / secrets  
- catalog 真相源：`data/catalog.json`（同步 `public/data`；**运营侧 Serv00 的 catalog 可领先 CF**，前台靠 store-catalog）  
- 改 PHP 必须提醒上传 Serv00  
- 燕文 / Stripe 密钥不进文档正文  

## 8. 2026-07-20 打印机分类（背景）

- 分类 `printer`：P15、P50、X8T；`requires_plug: false`  
- 前端随 `main` 推送；结账需 Serv00 认识 SKU（`create-checkout-session` / catalog）  

## 9. 2026-07-21 一句话

> 双宿主已定；换图走 live catalog；详情页 hydrate 崩溃已修；删产品已加；首页分类深链已修。下一刀：燕文真单。

---

## 2026-09-23 — GEO content pass (local, not pushed yet)

Added crawlable `/faq/`, `/guides/android-tv-box-buyers-guide/`, expanded `public/llms.txt`, dynamic `/llms-full.txt`, sitemap + footer/nav links, homepage visible FAQ block (`STORE_FAQS` in `src/lib/seo.ts`).

**Next:** review locally / `npm run build`, then `git push origin main` so CF Pages picks up `dist/`. GEO helps citations; first sales still need traffic + checkout trust.
