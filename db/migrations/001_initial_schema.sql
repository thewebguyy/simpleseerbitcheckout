-- SeerBit Checkout — Full Database Schema
-- Migration: 001_initial_schema.sql
-- Run once on a fresh database.
-- Character set: utf8mb4 (full Unicode including emoji)

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ─────────────────────────────────────────────────────────────────────────────
-- users
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `users` (
  `id`                   BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `email`                VARCHAR(254)     NOT NULL,
  `password_hash`        VARCHAR(255)     NOT NULL,
  `full_name`            VARCHAR(100)     NOT NULL,
  `phone`                VARCHAR(20)      DEFAULT NULL,
  `profile_photo_path`   VARCHAR(500)     DEFAULT NULL,
  `locale`               VARCHAR(10)      NOT NULL DEFAULT 'en-NG',
  `email_verified`       TINYINT(1)       NOT NULL DEFAULT 0,
  `verification_token`   VARCHAR(64)      DEFAULT NULL,
  `token_expires_at`     DATETIME         DEFAULT NULL,
  `created_at`           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_verification_token` (`verification_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- products (reference table for merchant store)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `products` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(255)    NOT NULL,
  `description` TEXT            DEFAULT NULL,
  `price`       BIGINT UNSIGNED NOT NULL COMMENT 'Amount in minor units (e.g. kobo for NGN)',
  `currency`    CHAR(3)         NOT NULL DEFAULT 'NGN',
  `category`    VARCHAR(100)    DEFAULT NULL,
  `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
  `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_products_category_active` (`category`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- orders
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `orders` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`          BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL = guest checkout',
  `order_reference`  VARCHAR(64)     NOT NULL,
  `status`           ENUM(
                       'DRAFT',
                       'PENDING_PAYMENT',
                       'PROCESSING',
                       'PAID',
                       'FAILED',
                       'CANCELLED',
                       'EXPIRED',
                       'REFUNDED'
                     )               NOT NULL DEFAULT 'DRAFT',
  `currency`         CHAR(3)         NOT NULL,
  `subtotal`         BIGINT UNSIGNED NOT NULL COMMENT 'Minor units',
  `total_amount`     BIGINT UNSIGNED NOT NULL COMMENT 'Minor units',
  `billing_name`     VARCHAR(100)    NOT NULL,
  `billing_email`    VARCHAR(254)    NOT NULL,
  `billing_address`  TEXT            DEFAULT NULL,
  `shipping_address` TEXT            DEFAULT NULL,
  `ip_address`       VARCHAR(45)     NOT NULL,
  `user_agent`       VARCHAR(500)    DEFAULT NULL,
  `expires_at`       DATETIME        NOT NULL,
  `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_reference` (`order_reference`),
  KEY `idx_orders_user_id`       (`user_id`),
  KEY `idx_orders_status`        (`status`),
  KEY `idx_orders_status_expiry` (`status`, `expires_at`),
  KEY `idx_orders_user_created`  (`user_id`, `created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- order_items
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `order_items` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     BIGINT UNSIGNED NOT NULL,
  `product_id`   BIGINT UNSIGNED DEFAULT NULL COMMENT 'NULL if product deleted',
  `product_name` VARCHAR(255)    NOT NULL    COMMENT 'Snapshot at purchase time',
  `unit_price`   BIGINT UNSIGNED NOT NULL    COMMENT 'Snapshot in minor units',
  `quantity`     INT UNSIGNED    NOT NULL DEFAULT 1,
  `line_total`   BIGINT UNSIGNED NOT NULL    COMMENT 'unit_price * quantity',
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  CONSTRAINT `fk_order_items_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`   (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- payments
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `payments` (
  `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`                BIGINT UNSIGNED NOT NULL,
  `provider`                VARCHAR(50)     NOT NULL DEFAULT 'seerbit',
  `provider_transaction_ref` VARCHAR(255)   DEFAULT NULL,
  `tranref`                 VARCHAR(64)     NOT NULL,
  `idempotency_key`         VARCHAR(128)    NOT NULL,
  `amount`                  BIGINT UNSIGNED NOT NULL COMMENT 'Minor units',
  `currency`                CHAR(3)         NOT NULL,
  `status`                  ENUM(
                              'PENDING',
                              'PROCESSING',
                              'SUCCESSFUL',
                              'FAILED',
                              'ABANDONED',
                              'REFUNDED'
                            )               NOT NULL DEFAULT 'PENDING',
  `provider_response`       JSON            DEFAULT NULL,
  `initiated_at`            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at`            DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payments_tranref`          (`tranref`),
  UNIQUE KEY `uq_payments_idempotency_key`  (`idempotency_key`),
  UNIQUE KEY `uq_payments_provider_ref`     (`provider_transaction_ref`),
  KEY `idx_payments_order_id`  (`order_id`),
  KEY `idx_payments_status_initiated` (`status`, `initiated_at`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`)
    REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- payment_events (write-once audit log — never update, never delete)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `payment_events` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_id`       BIGINT UNSIGNED DEFAULT NULL,
  `event_type`       VARCHAR(100)    NOT NULL,
  `tranref`          VARCHAR(64)     NOT NULL,
  `payload`          JSON            NOT NULL,
  `signature`        VARCHAR(255)    NOT NULL,
  `signature_valid`  TINYINT(1)      NOT NULL,
  `processed`        TINYINT(1)      NOT NULL DEFAULT 0,
  `received_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_events_tranref`    (`tranref`),
  KEY `idx_events_type`       (`event_type`),
  KEY `idx_events_processed`  (`processed`, `received_at`),
  CONSTRAINT `fk_events_payment` FOREIGN KEY (`payment_id`)
    REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- jobs (database-backed async queue)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `jobs` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue`        VARCHAR(50)     NOT NULL DEFAULT 'default',
  `job_class`    VARCHAR(255)    NOT NULL,
  `payload`      JSON            NOT NULL,
  `attempts`     TINYINT         NOT NULL DEFAULT 0,
  `max_attempts` TINYINT         NOT NULL DEFAULT 3,
  `status`       ENUM('pending','processing','failed','done') NOT NULL DEFAULT 'pending',
  `available_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `started_at`   DATETIME        DEFAULT NULL,
  `completed_at` DATETIME        DEFAULT NULL,
  `failed_at`    DATETIME        DEFAULT NULL,
  `error`        TEXT            DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jobs_pickup` (`status`, `available_at`, `queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────────────
-- Seed: sample products (matches legacy merchantstore.php data)
-- ─────────────────────────────────────────────────────────────────────────────

INSERT INTO `products` (`name`, `description`, `price`, `currency`, `category`) VALUES
('Product 1', 'This is product 1 description.', 5000,  'NGN', 'Category 1'),
('Product 2', 'This is product 2 description.', 7500,  'NGN', 'Category 2'),
('Product 3', 'This is product 3 description.', 10000, 'NGN', 'Category 1');

SET foreign_key_checks = 1;
