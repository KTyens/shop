# 中文部署操作手册：Cloudflare Pages 前端 + Serv00 API

这份文档按“照着点”的方式写，适合不是开发人员时使用。

当前推荐架构：

- 前端网站：Cloudflare Pages，显示首页、产品页、详情页、购物车界面。
- 后端 API：Serv00，处理 Stripe 支付、订单、会员、优惠码、后台管理。
- 域名建议：
  - 店铺前端：`https://shop.crtlu.me`
  - 后端 API：`https://api.crtlu.me`

不要把 Stripe 私钥、数据库密码放到 Cloudflare Pages。真实密钥只放在 Serv00 的 `api/config.local.php`。

## 一、Cloudflare Pages 设置

进入 Cloudflare 后按这个路径：

```text
Workers 和 Pages -> shop-crtlu -> 设置 -> 环境变量
```

添加这个变量：

| 名称 | 值 |
|---|---|
| `PUBLIC_CRTLU_API_BASE_URL` | `https://api.crtlu.me/api` |

注意：

- 环境选择 `Production`。
- 如果页面里同时有 `Preview` 环境，也可以同样加一遍。
- 保存后需要重新部署一次 Pages，环境变量才会进入新版本。

重新部署路径一般是：

```text
Workers 和 Pages -> shop-crtlu -> 部署 -> 最新部署 -> 重试部署
```

或者直接推送新代码到 GitHub 的 `main` 分支，Cloudflare 会自动部署。

## 二、Cloudflare Pages 构建设置

进入：

```text
Workers 和 Pages -> shop-crtlu -> 设置 -> 构建与部署
```

确认这些值：

| 项目 | 填写 |
|---|---|
| Framework preset | `Astro` |
| Build command | `npm run build` |
| Build output directory | `dist` |
| Root directory | 如果仓库根目录就是 `shop`，留空；如果仓库上层还有目录，填 `shop` |
| Node version | Node 20 或更新版本 |

本项目目前 GitHub 仓库看起来就是 `shop`，所以多数情况下 `Root directory` 留空。

## 三、Serv00 后端文件放哪里

Serv00 上建议使用独立的 API 子域名：

```text
api.crtlu.me
```

把这些目录上传或保留在 API 网站目录中：

```text
api/
admin/
data/
database/
.htaccess
```

Serv00 的真实配置文件是：

```text
api/config.local.php
```

如果还没有这个文件，就把：

```text
api/config.local.example.php
```

复制成：

```text
api/config.local.php
```

然后只改 `config.local.php`，不要改示例文件来放真实密钥。

## 四、Serv00 的 config.local.php 怎么填

最关键的是这两个：

```php
define('CRTLU_BASE_URL', 'https://shop.crtlu.me');
define('CRTLU_ALLOWED_ORIGINS', 'https://shop.crtlu.me,http://localhost:4321,http://127.0.0.1:4321');
```

解释：

- `CRTLU_BASE_URL`：客户付款成功后回到哪个前端网站。
- `CRTLU_ALLOWED_ORIGINS`：允许哪些前端域名调用 Serv00 API。

如果你现在还在用 Cloudflare 默认域名测试，也就是：

```text
https://shop-crtlu.pages.dev
```

那么临时建议写成：

```php
define('CRTLU_BASE_URL', 'https://shop-crtlu.pages.dev');
define('CRTLU_ALLOWED_ORIGINS', 'https://shop-crtlu.pages.dev,https://shop.crtlu.me,http://localhost:4321,http://127.0.0.1:4321');
```

等 `shop.crtlu.me` 正式绑定到 Cloudflare Pages 后，再改回：

```php
define('CRTLU_BASE_URL', 'https://shop.crtlu.me');
define('CRTLU_ALLOWED_ORIGINS', 'https://shop.crtlu.me,http://localhost:4321,http://127.0.0.1:4321');
```

完整模板如下：

```php
<?php

define('CRTLU_BASE_URL', 'https://shop.crtlu.me');
define('CRTLU_ALLOWED_ORIGINS', 'https://shop.crtlu.me,http://localhost:4321,http://127.0.0.1:4321');

define('STRIPE_SECRET_KEY', 'sk_live_你的真实Stripe私钥');
define('STRIPE_WEBHOOK_SECRET', 'whsec_你的真实Webhook密钥');

define('CRTLU_DB_DSN', 'mysql:host=mysql5.serv00.com;dbname=你的数据库名;charset=utf8mb4');
define('CRTLU_DB_USER', '你的数据库用户名');
define('CRTLU_DB_PASS', '你的数据库密码');

define('CRTLU_ADMIN_USER', '后台用户名');
define('CRTLU_ADMIN_PASS', '后台密码');

define('CRTLU_MAIL_FROM', 'support@crtlu.me');
define('CRTLU_DEFAULT_LOCALE', 'en');
define('CRTLU_DEFAULT_CURRENCY', 'USD');
define('CRTLU_LOGIN_CODE_DEBUG', '0');
```

重要：

- `STRIPE_SECRET_KEY` 是 Stripe 私钥，通常以 `sk_live_` 开头。
- `STRIPE_WEBHOOK_SECRET` 是 webhook signing secret，通常以 `whsec_` 开头。
- `CRTLU_LOGIN_CODE_DEBUG` 生产环境必须是 `0`。

## 五、Stripe Webhook 设置

Stripe 后台进入：

```text
Developers -> Webhooks -> Add endpoint
```

Endpoint URL 填：

```text
https://api.crtlu.me/api/stripe-webhook.php
```

事件选择：

```text
checkout.session.completed
```

创建完成后，复制 Stripe 给你的 `Signing secret`，填到 Serv00：

```php
define('STRIPE_WEBHOOK_SECRET', 'whsec_这里换成真实值');
```

## 六、部署后检查

先检查后端：

```text
https://api.crtlu.me/api/health.php
https://api.crtlu.me/api/products.php
```

正常情况下：

- `health.php` 应该返回 JSON，不应该是 404 或空白。
- `products.php` 应该返回产品数据。

再检查前端：

```text
https://shop-crtlu.pages.dev/
https://shop-crtlu.pages.dev/products/
```

如果正式域名已绑定：

```text
https://shop.crtlu.me/
https://shop.crtlu.me/products/
```

至少测试这些动作：

1. 首页能看到精选产品。
2. All Products 页面能看到所有产品。
3. 点 Details 能进入详情页。
4. Add to Cart 能加入购物车。
5. Checkout with Stripe 能跳转到 Stripe 支付页。
6. 支付完成后能回到成功页。

## 七、常见问题

### 产品页一直显示 Loading catalog

通常是前端构建产物没有更新，或者 Cloudflare 还在旧部署。

处理：

1. 确认 GitHub 已推送最新代码。
2. 在 Cloudflare Pages 里重新部署最新版本。
3. 浏览器强制刷新，或者换无痕窗口测试。

### 加购物车可以，但结账失败

优先检查：

1. Cloudflare Pages 是否设置了 `PUBLIC_CRTLU_API_BASE_URL=https://api.crtlu.me/api`。
2. Serv00 的 `CRTLU_ALLOWED_ORIGINS` 是否包含当前访问的前端域名。
3. Serv00 的 `STRIPE_SECRET_KEY` 是否是真实 live 或 test 私钥。
4. `https://api.crtlu.me/api/health.php` 是否正常。

### 会员登录或优惠码接口失败

多数是 CORS 来源没加对。

如果你当前访问的是：

```text
https://shop-crtlu.pages.dev
```

那么 Serv00 必须允许它：

```php
define('CRTLU_ALLOWED_ORIGINS', 'https://shop-crtlu.pages.dev,https://shop.crtlu.me,http://localhost:4321,http://127.0.0.1:4321');
```

### 支付成功后跳回了错误网址

检查 Serv00：

```php
define('CRTLU_BASE_URL', 'https://shop.crtlu.me');
```

如果你还在测试 `pages.dev`，可以临时填：

```php
define('CRTLU_BASE_URL', 'https://shop-crtlu.pages.dev');
```

正式上线后再改回 `https://shop.crtlu.me`。

## 八、日常更新代码流程

本地改完并验证后：

```bash
git add -A
git commit -m "你的提交说明"
git push origin main
```

推送后 Cloudflare Pages 会自动部署。

如果部署完成但浏览器看起来还是旧页面：

1. 等 1 到 3 分钟。
2. 强制刷新浏览器。
3. 到 Cloudflare Pages 里确认最新 commit 是否部署成功。
