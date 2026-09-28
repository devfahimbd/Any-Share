<?php
/**
 * Any Share - Database Handler (PDO)
 * Provides robust MySQL connectivity, automatic schema provisioning, and query helpers
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $pdo = null;
    private static bool $initialized = false;

    /**
     * Get or create PDO connection
     *
     * @return PDO|null
     */
    public static function getConnection(): ?PDO {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = get_config('database', 'db_host', 'localhost');
        $port = get_config('database', 'db_port', '3306');
        $dbName = get_config('database', 'db_name', 'any_share');
        $user = get_config('database', 'db_user', 'root');
        $pass = get_config('database', 'db_pass', '');
        $charset = get_config('database', 'db_charset', 'utf8mb4');
        $autoCreate = (bool) get_config('database', 'auto_create_db', true);

        try {
            if ($autoCreate) {
                // Connect to MySQL server without dbname first to verify/create database (Localhost/Dev)
                try {
                    $dsnServer = "mysql:host={$host};port={$port};charset={$charset}";
                    $tempPdo = new PDO($dsnServer, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE {$charset}_unicode_ci");
                    unset($tempPdo);
                } catch (Throwable $e) {
                    // In cPanel, regular MySQL users cannot CREATE DATABASE. Silently proceed to dbname connection.
                }
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Sync MySQL session timezone with PHP's current timezone offset
            try {
                $tzOffset = date('P');
                self::$pdo->exec("SET time_zone = '{$tzOffset}'");
            } catch (Throwable $tzEx) {}

            if ($autoCreate && !self::$initialized) {
                self::ensureSchema();
                self::$initialized = true;
            }

            return self::$pdo;
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Ensures necessary tables exist
     */
    private static function ensureSchema(): void {
        if (!self::$pdo) return;

        $bucketsTable = "CREATE TABLE IF NOT EXISTS `buckets` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $filesTable = "CREATE TABLE IF NOT EXISTS `files` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        self::$pdo->exec($bucketsTable);
        self::$pdo->exec($filesTable);

        // Ensure expires_at column exists in existing tables
        try {
            $colCheck = self::$pdo->query("SHOW COLUMNS FROM `buckets` LIKE 'expires_at'")->fetch();
            if (!$colCheck) {
                self::$pdo->exec("ALTER TABLE `buckets` ADD COLUMN `expires_at` DATETIME NULL AFTER `updated_at`, ADD INDEX `idx_expires_at` (`expires_at`)");
            }
        } catch (Exception $e) {}
    }
}

/**
 * Global helper to get PDO instance
 *
 * @return PDO|null
 */
function get_db(): ?PDO {
    return Database::getConnection();
}

/**
 * Check if Database connection is active
 *
 * @return bool
 */
function db_is_connected(): bool {
    return get_db() !== null;
}

/**
 * Finds bucket by secret ID
 *
 * @param string $secretId
 * @return array|null
 */
function db_find_bucket(string $secretId): ?array {
    $db = get_db();
    if (!$db) return null;

    $stmt = $db->prepare("SELECT * FROM `buckets` WHERE `secret_id` = ? LIMIT 1");
    $stmt->execute([$secretId]);
    $bucket = $stmt->fetch();
    return $bucket ?: null;
}

/**
 * Creates or updates bucket record in MySQL with 30-minute auto-expiry
 *
 * @param string $secretId
 * @param int $totalFiles
 * @param int $totalSize
 * @param string $note
 * @param int $expiryMinutes
 * @return int|null Bucket ID
 */
function db_create_or_update_bucket(string $secretId, int $totalFiles, int $totalSize, string $note = '', ?int $expiryMinutes = null): ?int {
    $db = get_db();
    if (!$db) return null;

    if ($expiryMinutes === null) {
        $expiryMinutes = (int) get_config('storage', 'auto_delete_minutes', 30);
    }

    $now = time();
    $expiresAtStr = date('Y-m-d H:i:s', $now + ($expiryMinutes * 60));

    $existing = db_find_bucket($secretId);
    if ($existing) {
        $stmt = $db->prepare("UPDATE `buckets` SET `total_files` = ?, `total_size` = ?, `note` = COALESCE(NULLIF(?, ''), `note`), `expires_at` = ? WHERE `id` = ?");
        $stmt->execute([$totalFiles, $totalSize, $note, $expiresAtStr, $existing['id']]);
        return (int) $existing['id'];
    } else {
        $stmt = $db->prepare("INSERT INTO `buckets` (`secret_id`, `total_files`, `total_size`, `note`, `expires_at`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$secretId, $totalFiles, $totalSize, $note, $expiresAtStr]);
        return (int) $db->lastInsertId();
    }
}

/**
 * Purge expired buckets from database
 *
 * @param int $expiryMinutes
 * @return array Array of deleted secret IDs
 */
function db_purge_expired(int $expiryMinutes = 30): array {
    $db = get_db();
    if (!$db) return [];

    try {
        $nowStr = date('Y-m-d H:i:s');
        $cutoffStr = date('Y-m-d H:i:s', time() - ($expiryMinutes * 60));
        $stmt = $db->prepare("SELECT `secret_id` FROM `buckets` WHERE (`expires_at` IS NOT NULL AND `expires_at` <= ?) OR (`created_at` <= ?)");
        $stmt->execute([$nowStr, $cutoffStr]);
        $expiredIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($expiredIds)) {
            $inClause = implode(',', array_fill(0, count($expiredIds), '?'));
            $delStmt = $db->prepare("DELETE FROM `buckets` WHERE `secret_id` IN ($inClause)");
            $delStmt->execute($expiredIds);
        }

        return $expiredIds;
    } catch (Exception $e) {
        error_log("Database purge error: " . $e->getMessage());
        return [];
    }
}


/**
 * Saves file record into MySQL files table
 *
 * @param int $bucketId
 * @param string $secretId
 * @param array $fileData
 * @return bool
 */
function db_save_file(int $bucketId, string $secretId, array $fileData): bool {
    $db = get_db();
    if (!$db) return false;

    // Check if file with same path already registered
    $checkStmt = $db->prepare("SELECT `id` FROM `files` WHERE `bucket_id` = ? AND `relative_path` = ? LIMIT 1");
    $checkStmt->execute([$bucketId, $fileData['path']]);
    $existingFileId = $checkStmt->fetchColumn();

    if ($existingFileId) {
        $stmt = $db->prepare("UPDATE `files` SET `file_size` = ?, `file_type` = ?, `extension` = ?, `category` = ? WHERE `id` = ?");
        return $stmt->execute([
            $fileData['size'],
            $fileData['file_type'] ?? null,
            $fileData['extension'] ?? null,
            $fileData['category'] ?? null,
            $existingFileId
        ]);
    } else {
        $stmt = $db->prepare("INSERT INTO `files` (`bucket_id`, `secret_id`, `file_name`, `relative_path`, `file_size`, `file_type`, `extension`, `category`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $bucketId,
            $secretId,
            $fileData['name'],
            $fileData['path'],
            $fileData['size'],
            $fileData['file_type'] ?? null,
            $fileData['extension'] ?? null,
            $fileData['category'] ?? null
        ]);
    }
}

/**
 * Increment bucket view counter
 *
 * @param string $secretId
 */
function db_increment_views(string $secretId): void {
    $db = get_db();
    if (!$db) return;

    $stmt = $db->prepare("UPDATE `buckets` SET `views_count` = `views_count` + 1 WHERE `secret_id` = ?");
    $stmt->execute([$secretId]);
}

/**
 * Increment download counter for bucket and optional file
 *
 * @param string $secretId
 * @param string|null $relativePath
 */
function db_increment_downloads(string $secretId, ?string $relativePath = null): void {
    $db = get_db();
    if (!$db) return;

    $stmt = $db->prepare("UPDATE `buckets` SET `downloads_count` = `downloads_count` + 1 WHERE `secret_id` = ?");
    $stmt->execute([$secretId]);

    if (!empty($relativePath)) {
        $fileStmt = $db->prepare("UPDATE `files` SET `download_count` = `download_count` + 1 WHERE `secret_id` = ? AND `relative_path` = ?");
        $fileStmt->execute([$secretId, $relativePath]);
    }
}
