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
