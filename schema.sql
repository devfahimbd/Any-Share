-- ====================================================================
-- Any Share - Database Schema (MySQL / MariaDB)
-- Compatible with XAMPP (Localhost) & cPanel MySQL
-- ====================================================================
-- --------------------------------------------------------------------
-- Table: buckets
-- Stores each secret share/bucket record with 30-minute auto-expiry
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `buckets` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `secret_id` VARCHAR(64) NOT NULL,
    `title` VARCHAR(255) DEFAULT NULL,
    `note` TEXT DEFAULT NULL,
    `total_files` INT UNSIGNED DEFAULT 0,
    `total_size` BIGINT UNSIGNED DEFAULT 0,
    `views_count` INT UNSIGNED DEFAULT 0,
    `downloads_count` INT UNSIGNED DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `expires_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `uniq_secret_id` (`secret_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_expires_at` (`expires_at`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
-- --------------------------------------------------------------------
-- Table: files
-- Stores individual file records belonging to a bucket
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `files` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `bucket_id` INT UNSIGNED NOT NULL,
    `secret_id` VARCHAR(64) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `relative_path` VARCHAR(500) NOT NULL,
    `file_size` BIGINT UNSIGNED NOT NULL,
    `file_type` VARCHAR(150) DEFAULT NULL,
    `extension` VARCHAR(50) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `download_count` INT UNSIGNED DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_bucket_id` (`bucket_id`),
    KEY `idx_secret_id` (`secret_id`),
    KEY `idx_extension` (`extension`),
    KEY `idx_category` (`category`),
    CONSTRAINT `fk_files_bucket` FOREIGN KEY (`bucket_id`) REFERENCES `buckets` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;