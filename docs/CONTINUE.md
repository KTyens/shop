# 续做入口（下次打开项目先读这里）

**Last session:** 2026-07-17  
**Project path:** `/Users/apple/Desktop/Codex Projects/独立站/shop`  
**Live:** `https://shop.crtlu.me`（前端 CF Pages）· `https://api.crtlu.me`（PHP Serv00）  
**Git:** `origin/main` → `https://github.com/KTyens/shop.git`  
**Latest commits (this arc):** `9664050` (mail), `622b082`/`225fd98` (login fetch), `f5e7629` (Yanwen P3), `40896b6` (P2+P4)

---

## 0. 给 Agent 的第一句话

> 读 `docs/CONTINUE.md` + `docs/记录文档.md` 顶部 0.10–0.14，然后从 **§1 未完成阻塞** 接着做。  
> 不要重新设计整站；优先让登录邮件与燕文线上验收跑通。

---

## 1. 当前阻塞（用户关电脑前卡在这里）

### P0 阻塞：登录验证码邮件收不到

| 项 | 状态 |
|---|---|
| 接口 | 已能返回 “We sent a 6-digit…” |
| 邮件 | **Gmail 收不到**（垃圾箱也没有） |
| 根因 | Serv00 上 PHP `mail()` 不可靠 |
| 代码 | **已改**：Resend / SMTP / mail 三级发送（`9664050`） |
| 用户侧 | **尚未确认**已上传 PHP + 已配置 Resend 或 SMTP |

**下次立刻做：**

1. 确认 Serv00 已上传：  
   `api/notifications.php`、`api/member-auth.php`、`api/account-request-code.php`、`api/config.php`、`admin/emails.php`  
   包参考：`独立站/crtlu-serv00-backend-20260717-mail-fix.zip`（及 login-fix 包）  
   **禁止覆盖** `api/config.local.php`
2. 在 **Serv00** `config.local.php` 配置发信（二选一）：

```php
define('CRTLU_MAIL_FROM', 'support@crtlu.me'); // 或你域名邮箱
define('CRTLU_MAIL_FROM_NAME', 'CRTLU Digital');

// A) Resend（推荐）
// define('CRTLU_RESEND_API_KEY', 're_xxx');

// B) SMTP
// define('CRTLU_SMTP_HOST', 'smtp.xxx.com');
// define('CRTLU_SMTP_PORT', '587');
// define('CRTLU_SMTP_USER', '...');
// define('CRTLU_SMTP_PASS', '...');
// define('CRTLU_SMTP_SECURE', 'tls');
```

3. 应急（仅临时）：`define('CRTLU_LOGIN_CODE_DEBUG', '1');` → 页面显示验证码；**修好邮件后改回 0**  
4. 或后台「邮件队列」看 `member_login_code` 正文里的 6 位码  
5. 验收：`/account/` → Send code → 邮箱收到 → Sign in 成功

**相关代码：**  
`api/notifications.php`（`crtlu_send_mail`）、`api/member-auth.php`、`api/account-request-code.php`、`src/pages/account.astro`

---

## 2. 整站进度（相对整个项目）

| 大块 | 状态 |
|---|---|
| 前台商城 / 支付 Stripe / catalog | ✅ 可运营 |
| 会员 Phase4–5 / 后台 | ✅ 主体完成 |
| 登录（网络） | ✅ 已修 Failed to fetch（同域 `/api` + CORS + 自建表） |
| 登录（邮件） | ⏳ **代码好了，等部署+SMTP/Resend 配置** |
| 燕文 P0–P4 代码 | ✅ 全做完 |
| 燕文线上验收 | ⏳ 待 CHANNEL_ID + Serv00 包 + 真单 |

**整体阶段：** 可卖货运营的中后期；物流 API 与登录邮件处于「代码就绪 → 线上验收」收尾。

---

## 3. 燕文（快递）进度

| 阶段 | 能力 | 代码 | 线上 |
|---|---|---|---|
| P0 | 签名/连通 | ✅ | ✅ 密钥已通 |
| P1 | 国家/产品/仓列表 | ✅ | 可用 |
| P2 | 一键创建运单 | ✅ | ⏳ 需部署 + CHANNEL_ID |
| P3 | 打印标签 PDF | ✅ | ⏳ 需部署 |
| P4 | 账户 View tracking | ✅ | ⏳ 需部署 + 登录可用 |

**产品 ID（用户确认）：** 燕文专线追踪-普货 → **`YANWEN_CHANNEL_ID = '481'`**  
**仓库：** `YANWEN_WAREHOUSE_CODE` **可不填**（默认仓）

```php
define('YANWEN_USER_ID', '…');      // 已有
define('YANWEN_API_TOKEN', '…');    // 已有
define('YANWEN_CHANNEL_ID', '481'); // 用户确认填写
// define('YANWEN_WAREHOUSE_CODE', ''); // 可选，可不写
```

**文档：** `docs/yanwen-api-integration.md`

---

## 4. 部署架构（别搞混）

| 层 | 宿主 | 内容 | 怎么更新 |
|---|---|---|---|
| 前端 | Cloudflare Pages | `shop/dist` from `main` | `git push origin main` |
| 后端 | Serv00 `api.crtlu.me` | `api/` `admin/` | **手动上传 zip/文件** |
| 密钥 | 仅 Serv00 | `api/config.local.php` | 永不提交 Git |

前台 API 默认：**同域 `/api`** → CF Functions 反代到 `https://api.crtlu.me/api`。  
若 CF 环境变量 `PUBLIC_CRTLU_API_BASE_URL=https://api.crtlu.me/api`，建议清空以免再踩 CORS。

---

## 5. 建议下次工作顺序

1. **登录邮件** — 部署 PHP + 配 Resend/SMTP + 真实验收  
2. **燕文** — 上传含 P2/P3/P4 的后端；`CHANNEL_ID=481`；真单：创建→打标签→账户轨迹  
3. **打磨（非阻塞）** — 见下表  

| 优先级 | 项 |
|---|---|
| 中 | 规格书与 SKU 档位核对 |
| 中 | 登录/订单邮件送达与 From 域名 SPF/DKIM |
| 低 | `published-products.md` 与现价对齐 |
| 低 | 规格中英文、账户页 i18n |
| 按需 | 新品上架流程 |

---

## 6. 本机/会话关键路径

```text
/Users/apple/Desktop/Codex Projects/独立站/          # 工作区父目录
  AGENTS.md                                         # 根入口 → 指向 shop 文档
  shop/                                             # ★ 主仓库
    AGENTS.md
    docs/CONTINUE.md                                # ★ 本文件
    docs/记录文档.md                                 # 变更账本 0.10–0.14
    docs/yanwen-api-integration.md
    docs/agent-handoff.md
  crtlu-serv00-backend-20260717*.zip                # 近期 Serv00 包
```

**常用命令：**

```bash
cd "/Users/apple/Desktop/Codex Projects/独立站/shop"
git log -5 --oneline
git status
# 前端
npm run build && git push origin main
# 后端包
bash scripts/pack-serv00-backend.sh
```

---

## 7. 硬性规则（续做时勿忘）

- 不提交 `api/config.local.php` / secrets  
- catalog 真相源：`data/catalog.json`（同步 `public/data`）  
- 改 PHP 必须提醒上传 Serv00  
- 燕文 token / Stripe 密钥不进文档正文  

---

## 8. 用户已知上下文

- 常用邮箱测试：`ysmy0418@gmail.com`（登录验证）  
- 燕文产品：`481` 普货专线追踪  
- 仓库代码：可不填  
- 整站 vs 快递阶段已向用户解释过；勿混淆 Phase4/5 与 燕文 P0–P4 编号  
