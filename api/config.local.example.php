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
define('CRTLU_ADMIN_PASS', 'change_this_password');

// Optional phase-4 customer communication defaults.
define('CRTLU_MAIL_FROM', 'support@crtlu.me');
define('CRTLU_DEFAULT_LOCALE', 'en');
define('CRTLU_DEFAULT_CURRENCY', 'USD');
define('CRTLU_LOGIN_CODE_DEBUG', '0');
