# Cloudflare Pages 部署指南

## 前提条件

- 已注册 [Cloudflare 账号](https://dash.cloudflare.com/sign-up)
- 已注册 [Stripe 账号](https://dashboard.stripe.com)（用于收款）
- 本地项目已构建成功：`npx astro build`

## 方式一：通过 Git 自动部署（推荐）

### 1. 推送代码到 GitHub

```bash
cd "/Users/apple/Desktop/Codex Projects/独立站/shop"
git init
git add -A
git commit -m "Initial Astro build"
git branch -M main
git remote add origin git@github.com:YOUR_USERNAME/shop.git
git push -u origin main
```

### 2. 在 Cloudflare 创建 Pages 项目

1. 登录 [Cloudflare Dashboard](https://dash.cloudflare.com)
2. 左侧菜单 → **Workers & Pages** → **Create application** → **Pages** → **Connect to Git**
3. 选择你的 GitHub 账号和 `shop` 仓库
4. 配置构建设置：

| 字段 | 值 |
|------|------|
| **Project name** | `shop-crtlu` |
| **Production branch** | `main` |
| **Framework preset** | `Astro` |
| **Build command** | `npx astro build` |
| **Build output directory** | `dist` |
| **Root directory** | 留空 |

5. 点击 **Save and Deploy**

### 3. 自定义域名

1. 部署完成后，进入项目设置 → **Custom Domains**
2. 添加 `shop.crtlu.me`（或你的域名）
3. 按照提示在 DNS 管理中添加 CNAME 记录

## 方式二：手动上传构建产物

### 1. 本地构建

```bash
cd "/Users/apple/Desktop/Codex Projects/独立站/shop"
npx astro build
```

### 2. 上传到 Cloudflare

1. 进入 [Cloudflare Dashboard](https://dash.cloudflare.com) → Workers & Pages
2. 创建 Pages 项目
3. 选择 **Upload assets manually**
4. 上传 `dist/` 目录下的所有内容

## PHP API 配置（Serv00）

由于前端部署在 Cloudflare Pages，后端 API 仍留在 Serv00，需要配置 CORS：

### 在 Serv00 的 PHP API 文件中添加 CORS 头

编辑 `api/create-checkout-session.php` 等 API 文件，在文件开头添加：

```php
<?php
header("Access-Control-Allow-Origin: https://shop.crtlu.me");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
?>
```

### 如果使用多个域名

```php
header("Access-Control-Allow-Origin: *");  // 开发环境
// 生产环境建议指定具体域名
```

## Stripe 配置

### 1. 获取 API 密钥

1. 登录 [Stripe Dashboard](https://dashboard.stripe.com)
2. 开发模式下使用测试密钥（pk_test_...）
3. 上线前切换到生产密钥（pk_live_...）

### 2. 配置环境变量

在 Cloudflare Pages 项目中：

1. 进入项目设置 → **Environment variables**
2. 添加以下变量：

| 变量名 | 值 |
|--------|-----|
| `STRIPE_SECRET_KEY` | `sk_test_...` |
| `STRIPE_WEBHOOK_SECRET` | webhook 密钥 |
| `API_BASE_URL` | `https://your-serv00-domain.com/api` |

## 部署后验证

### 1. 检查页面

访问 `https://shop-crtlu.pages.dev`（或你的自定义域名），验证：

- [ ] 首页正常加载
- [ ] 产品列表页可搜索、筛选、排序
- [ ] 产品详情页图片画廊正常
- [ ] 购物车抽屉可打开/关闭
- [ ] 账户页面正常

### 2. 检查资源

- [ ] 产品图片加载正常
- [ ] CSS 样式正确
- [ ] JavaScript 无控制台错误

### 3. 测试结账流程

- [ ] 添加商品到购物车
- [ ] 点击 Checkout 跳转到 Stripe
- [ ] 支付成功后返回 success 页面
- [ ] order 确认信息正常显示

## 常见问题

### Q: 构建失败

检查 Node 版本：Cloudflare Pages 默认使用最新 LTS。如需指定版本，在项目根目录创建 `.node-version` 文件：

```
20.11.0
```

### Q: API 请求被 CORS 拦截

确保 Serv00 上的 PHP API 文件已添加 CORS 头，并且允许的域名与 Cloudflare Pages 域名匹配。

### Q: 图片不显示

确保 `public/assets/products/` 下的图片已正确复制到 `public/` 目录，或者在构建后将它们复制到 `dist/assets/products/`。

### Q: 环境变量未生效

Cloudflare Pages 的环境变量需要重新部署才能生效。修改后触发一次新的部署即可。

## 性能优化建议

1. **启用 CDN**：Cloudflare Pages 自动全局 CDN 加速
2. **图片优化**：考虑使用 Cloudflare Images 或 Imgix
3. **缓存策略**：在 `wrangler.json` 中配置缓存头：

```json
{
  "name": "shop-crtlu",
  "compatibility_date": "2024-01-01",
  "assets": {
    "directory": "dist"
  }
}
```

4. **监控**：在 Cloudflare Dashboard 查看访问量和性能指标
