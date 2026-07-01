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
