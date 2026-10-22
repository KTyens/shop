-- Phase 6: verified-buyer product reviews.
-- Run once on Serv00 MySQL (phpMyAdmin or `mysql < database/phase6-reviews-migration.sql`).
-- If a column already exists MySQL reports "Duplicate column" — safe to skip that statement.

ALTER TABLE `order_items`
  ADD COLUMN `variant_id` VARCHAR(120) NOT NULL DEFAULT '' AFTER `order_id`;

ALTER TABLE `order_items`
  ADD COLUMN `product_id` VARCHAR(120) NOT NULL DEFAULT '' AFTER `variant_id`;

ALTER TABLE `order_items`
  ADD KEY `idx_order_items_product_id` (`product_id`);

CREATE TABLE IF NOT EXISTS `product_reviews` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id BIGINT UNSIGNED NOT NULL,
  order_item_id BIGINT UNSIGNED NOT NULL,
  product_id VARCHAR(120) NOT NULL,
  variant_id VARCHAR(120) NOT NULL DEFAULT '',
  rating TINYINT UNSIGNED NOT NULL,
  title VARCHAR(120) NOT NULL DEFAULT '',
  body TEXT NOT NULL,
  display_name VARCHAR(60) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  ip_hash CHAR(64) NOT NULL DEFAULT '',
  approved_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_review_order_item (order_item_id),
  KEY idx_review_product_status (product_id, status),
  KEY idx_review_order (order_id),
  KEY idx_review_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `review_rate_limits` (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  bucket VARCHAR(80) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_review_rl_bucket_created (bucket, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
