<?php

// Copy this file to config.local.php on Serv00 and fill real values there.
// Never commit live Stripe keys or database passwords.

define('CRTLU_BASE_URL', 'https://shop.crtlu.me');
define('CRTLU_ALLOWED_ORIGINS', 'https://shop.crtlu.me,https://shop-crtlu.pages.dev,http://localhost:4321,http://127.0.0.1:4321');
define('STRIPE_SECRET_KEY', 'sk_live_replace_me');
define('STRIPE_WEBHOOK_SECRET', 'whsec_replace_me');

define('CRTLU_DB_DSN', 'mysql:host=mysql.example.serv00.com;dbname=your_db;charset=utf8mb4');
define('CRTLU_DB_USER', 'your_db_user');
define('CRTLU_DB_PASS', 'your_db_password');

define('CRTLU_ADMIN_USER', 'admin');
define('CRTLU_ADMIN_PASS', 'Ydkj.9298');

// Optional phase-4 customer communication defaults.
define('CRTLU_MAIL_FROM', 'support@crtlu.me');       // must be a domain you control
define('CRTLU_MAIL_FROM_NAME', 'CRTLU Digital');
define('CRTLU_ORDER_NOTIFY_EMAIL', 'owner@gmail.com');
// --- Email delivery (login codes / order mail) ---
// PHP mail() on shared hosts often never reaches Gmail. Prefer ONE of:
// A) Resend (https://resend.com) — easiest deliverability
// define('CRTLU_RESEND_API_KEY', 're_xxxxxxxx');
// B) SMTP (Gmail app password / domain mailbox / SendGrid SMTP)
// define('CRTLU_SMTP_HOST', 'smtp.example.com');
// define('CRTLU_SMTP_PORT', '587');
// define('CRTLU_SMTP_USER', 'support@crtlu.me');
// define('CRTLU_SMTP_PASS', 'your_smtp_password');
// define('CRTLU_SMTP_SECURE', 'tls'); // tls | ssl | none
// Emergency only: return code in API JSON (never leave on in production)
define('CRTLU_LOGIN_CODE_DEBUG', '0');
define('CRTLU_TELEGRAM_BOT_TOKEN', '');
define('CRTLU_TELEGRAM_CHAT_ID', '');
define('CRTLU_DEFAULT_LOCALE', 'en');
define('CRTLU_DEFAULT_CURRENCY', 'USD');

// 燕文开放平台（详见 docs/yanwen-api-integration.md）
// define('YANWEN_USER_ID', '客户号');
// define('YANWEN_API_TOKEN', 'apitoken');
// define('YANWEN_API_BASE', 'https://open.yw56.com.cn/api/order'); // 测试: open-fat.yw56.com.cn
// define('YANWEN_API_VERSION', 'V1.0');
// define('YANWEN_CHANNEL_ID', '');           // 必填：已开通产品 id（后台「拉取已开通产品」）
// define('YANWEN_WAREHOUSE_CODE', '');       // 可选：交货仓 companyCode
// define('YANWEN_DEFAULT_HSCODE', '851762');
// define('YANWEN_DEFAULT_WEIGHT_G', '500');  // 单件申报重量（克）
// define('YANWEN_HAS_BATTERY', '0');         // 1=带电 0=不带电
// define('YANWEN_ORDER_SOURCE', 'CRTLU');
// define('YANWEN_TRACK_BASE', 'https://api.track.yw56.com.cn/api/tracking');
// 可选发件人（senderInfo）
// define('YANWEN_SENDER_NAME', '');
// define('YANWEN_SENDER_PHONE', '');
// define('YANWEN_SENDER_EMAIL', '');
// define('YANWEN_SENDER_COMPANY', 'CRTLU Digital');
// define('YANWEN_SENDER_COUNTRY', 'CN');
// define('YANWEN_SENDER_STATE', '');
// define('YANWEN_SENDER_CITY', '');
// define('YANWEN_SENDER_ZIP', '');
// define('YANWEN_SENDER_ADDRESS', '');
