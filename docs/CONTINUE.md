# 续做入口（下次打开项目先读这里）

**Last session:** 2026-07-19  
**Project path:** `/Users/apple/Desktop/Codex Projects/独立站/shop`  
**Live:** `https://shop.crtlu.me`（前端 CF Pages）· `https://api.crtlu.me`（PHP Serv00）  
**Git:** `origin/main` → `https://github.com/KTyens/shop.git`  
**Tip (this arc):** `7c9ab6e` account flash · `77d6bc7` brand PNG · `248a5fe` dashboard · `d67791a` session cookie · Resend 邮件已线上可用

---

## 0. 给 Agent 的第一句话

> 读 `docs/CONTINUE.md` + `docs/记录文档.md`（0.10–0.14 与后续）。  
> 整站可卖货；**优先燕文线上真单验收**（CHANNEL_ID=481 + Serv00 后端包）。  
> 登录/账户体验本周已收口，勿无故重做整站。

---

## 1. 当前状态（相对阻塞）

| 项 | 状态 |
|---|---|
| 登录验证码邮件（Resend） | ✅ 用户已配置，Gmail 可收码 |
| 登录 Session Cookie（CF 反代） | ✅ 已修，Sign in 可进账户 |
| 账户 Profile/Address（orders.member_id） | ✅ 自动补列 + 前端语言下拉 |
| 账户 UI（主流个人中心） | ✅ 侧栏 + 右上头像菜单 |
| 品牌图标（超现实玻璃立方体） | ✅ 暂用 `brand-mark.png` |
| 账户闪登录页 | ✅ 先 Loading 再分流 |
| 燕文 P2–P4 线上真单 | ⏳ **下一优先** |

---

## 2. 整站进度

| 大块 | 状态 |
|---|---|
| 前台商城 / Stripe / catalog | ✅ 可运营 |
| 分层调价 / shop1 合并 | ✅ 2026-07-16 |
| 会员 Phase4–5 / 后台 | ✅ 主体完成 |
| 登录网络（CORS / 同域 /api） | ✅ |
| 登录邮件 Resend | ✅ 线上可用 |
| 登录会话 / 账户页体验 | ✅ 本周收口 |
| 燕文 P0–P4 代码 | ✅ 全做完 |
| 燕文线上验收（真单） | ⏳ 待 CHANNEL_ID + 包部署 |

**整体阶段：** **可卖货运营中后期**；物流 API 真单验收是主要未完成业务项。

---

## 3. 燕文（快递）

| 阶段 | 能力 | 代码 | 线上 |
|---|---|---|---|
| P0 | 签名/连通 | ✅ | ✅ |
| P1 | 国家/产品/仓列表 | ✅ | 可用 |
| P2 | 一键创建运单 | ✅ | ⏳ 部署 + CHANNEL_ID |
| P3 | 打印标签 PDF | ✅ | ⏳ 部署 |
| P4 | 账户 View tracking | ✅ | ⏳ 部署（登录已可用） |

```php
define('YANWEN_CHANNEL_ID', '481'); // 燕文专线追踪-普货
// YANWEN_WAREHOUSE_CODE 可不填
```

---

## 4. 部署架构

| 层 | 宿主 | 更新方式 |
|---|---|---|
| 前端 | CF Pages `shop.crtlu.me` | `git push origin main` |
| 后端 | Serv00 `api.crtlu.me` | 手动上传 zip/PHP |
| 密钥 | `api/config.local.php` | 永不提交、永不覆盖丢密钥 |

近期 Serv00 包目录：`独立站/crtlu-serv00-backend-202607*.zip`  
（mail-fix / session-fix / account-schema-fix 等）

---

## 5. 建议下次顺序

1. **燕文真单** — 确认 `YANWEN_CHANNEL_ID=481`；上传含 P2/P3/P4 的 PHP；后台创建运单 → 标签 → 前台轨迹  
2. **邮件域名** — SPF/DKIM（Resend 域名验证，提升送达）  
3. **打磨（非阻塞）** — 规格/SKU 核对、价格文档对齐、账户 i18n  

---

## 6. 硬性规则

- 不提交 `api/config.local.php` / secrets  
- catalog 真相源：`data/catalog.json`（同步 `public/data`）  
- 改 PHP 必须提醒上传 Serv00  
- 燕文 / Stripe 密钥不进文档正文  
