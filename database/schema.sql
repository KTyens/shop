CREATE TABLE IF NOT EXISTS `orders` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id BIGINT UNSIGNED NULL,
  stripe_session_id VARCHAR(255) NOT NULL,
  customer_email VARCHAR(255) NOT NULL DEFAULT '',
  customer_name VARCHAR(255) NOT NULL DEFAULT '',
  phone VARCHAR(80) NOT NULL DEFAULT '',
  amount_total INT UNSIGNED NOT NULL DEFAULT 0,
  currency VARCHAR(8) NOT NULL DEFAULT 'usd',
  coupon_code VARCHAR(64) NOT NULL DEFAULT '',
  discount_total INT UNSIGNED NOT NULL DEFAULT 0,
  display_currency VARCHAR(8) NOT NULL DEFAULT '',
  locale VARCHAR(16) NOT NULL DEFAULT '',
  payment_status VARCHAR(40) NOT NULL DEFAULT '',
  shipping_name VARCHAR(255) NOT NULL DEFAULT '',
  shipping_address_json LONGTEXT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'paid',
  yanwen_tracking VARCHAR(255) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_stripe_session_id (stripe_session_id),
  KEY idx_orders_member_id (member_id),
  KEY idx_orders_coupon_code (coupon_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  quantity INT UNSIGNED NOT NULL DEFAULT 1,
  unit_amount INT UNSIGNED NOT NULL DEFAULT 0,
  currency VARCHAR(8) NOT NULL DEFAULT 'usd',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `members` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(255) NOT NULL DEFAULT '',
  locale VARCHAR(16) NOT NULL DEFAULT 'en',
  currency VARCHAR(8) NOT NULL DEFAULT 'USD',
  status VARCHAR(40) NOT NULL DEFAULT 'active',
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_member_email (email),
  KEY idx_member_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_notifications` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NULL,
  to_email VARCHAR(255) NOT NULL,
  template VARCHAR(80) NOT NULL DEFAULT '',
  subject VARCHAR(255) NOT NULL DEFAULT '',
  body LONGTEXT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'queued',
  last_error TEXT NULL,
  sent_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_email_order_id (order_id),
  KEY idx_email_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `coupon_redemptions` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  coupon_code VARCHAR(64) NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  member_id BIGINT UNSIGNED NULL,
  customer_email VARCHAR(255) NOT NULL DEFAULT '',
  discount_total INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_coupon_code (coupon_code),
  KEY idx_coupon_order_id (order_id),
  KEY idx_coupon_member_id (member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `member_login_codes` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(255) NOT NULL,
  code_hash VARCHAR(255) NOT NULL,
  expires_at TIMESTAMP NOT NULL,
  used_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_login_email_created (email, created_at),
  KEY idx_login_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `member_addresses` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  member_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(80) NOT NULL DEFAULT 'Default',
  recipient_name VARCHAR(255) NOT NULL DEFAULT '',
  phone VARCHAR(80) NOT NULL DEFAULT '',
  country VARCHAR(80) NOT NULL DEFAULT '',
  postal_code VARCHAR(40) NOT NULL DEFAULT '',
  state VARCHAR(120) NOT NULL DEFAULT '',
  city VARCHAR(120) NOT NULL DEFAULT '',
  line1 VARCHAR(255) NOT NULL DEFAULT '',
  line2 VARCHAR(255) NOT NULL DEFAULT '',
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_member_addresses_member_id (member_id),
  KEY idx_member_addresses_default (member_id, is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
