ALTER TABLE `orders`
  ADD COLUMN `member_id` BIGINT UNSIGNED NULL AFTER `id`;

ALTER TABLE `orders`
  ADD COLUMN `coupon_code` VARCHAR(64) NOT NULL DEFAULT '' AFTER `currency`;

ALTER TABLE `orders`
  ADD COLUMN `discount_total` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `coupon_code`;

ALTER TABLE `orders`
  ADD COLUMN `display_currency` VARCHAR(8) NOT NULL DEFAULT '' AFTER `discount_total`;

ALTER TABLE `orders`
  ADD COLUMN `locale` VARCHAR(16) NOT NULL DEFAULT '' AFTER `display_currency`;

ALTER TABLE `orders`
  ADD KEY `idx_orders_member_id` (`member_id`);

ALTER TABLE `orders`
  ADD KEY `idx_orders_coupon_code` (`coupon_code`);

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
