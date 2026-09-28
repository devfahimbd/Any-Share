<?php
/**
 * Any Share - Utility & Helper Functions
 * Robust file system management, security validation, and S3-style directory navigation
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Validates and sanitizes a Secret / Unique ID
 *
 * @param string $id
 * @return string|false
 */
function validate_unique_id($id) {
    if (!is_string($id)) {
        return false;
    }
    $id = trim($id);
    $min = (int) get_config('security', 'min_id_length', 3);
    $max = (int) get_config('security', 'max_id_length', 64);
    $pattern = get_config('security', 'allowed_id_pattern', '^[a-zA-Z0-9_\-\.]+$');

    $len = strlen($id);
    if ($len < $min || $len > $max) {
        return false;
    }

    if (!preg_match('/' . $pattern . '/', $id)) {
        return false;
    }

    // Disallow reserved names or dot traversals
    if ($id === '.' || $id === '..' || str_contains($id, '..') || str_contains($id, '/') || str_contains($id, '\\')) {
        return false;
    }

    return $id;
}

/**
 * Returns root uploads directory path
 *
 * @return string
 */
function get_storage_root() {
    $dir = get_config('storage', 'upload_dir', 'uploads');
    return rtrim(dirname(__DIR__) . DIRECTORY_SEPARATOR . $dir, DIRECTORY_SEPARATOR);
}

/**
 * Recursively deletes a directory and all its files/subfolders
 *
 * @param string $dir
 * @return bool
 */
function delete_directory_recursive($dir) {
    if (!is_dir($dir)) return false;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            delete_directory_recursive($path);
        } else {
            @unlink($path);
        }
    }
    return @rmdir($dir);
}

/**
 * Checks if a bucket has expired (older than 30 minutes)
 *
 * @param string $id
 * @return bool
 */
function is_bucket_expired($id) {
    $cleanId = validate_unique_id($id);
    if (!$cleanId) return true;

    $expiryMinutes = (int) get_config('storage', 'auto_delete_minutes', 30);
    $expirySeconds = $expiryMinutes * 60;
    $now = time();

    // Check database record first
    $dbBucket = db_find_bucket($cleanId);
    if ($dbBucket) {
        if (!empty($dbBucket['expires_at'])) {
            return strtotime($dbBucket['expires_at']) <= $now;
        }
        if (!empty($dbBucket['created_at'])) {
            return (strtotime($dbBucket['created_at']) + $expirySeconds) <= $now;
        }
    }

    // Check disk meta.json or folder timestamp
    $baseDir = get_storage_root() . DIRECTORY_SEPARATOR . $cleanId;
    if (is_dir($baseDir)) {
        $metaFile = $baseDir . DIRECTORY_SEPARATOR . 'meta.json';
        if (file_exists($metaFile)) {
            $meta = json_decode(@file_get_contents($metaFile), true);
            $createdAt = (int) ($meta['created_at'] ?? 0);
            if ($createdAt > 0 && ($now - $createdAt) >= $expirySeconds) {
                return true;
            }
        } else {
            if (($now - filemtime($baseDir)) >= $expirySeconds) {
                return true;
            }
        }
        return false;
    }

    return true;
}

/**
 * Automatically purges buckets and files older than 30 minutes
 */
function purge_expired_storage() {
    $expiryMinutes = (int) get_config('storage', 'auto_delete_minutes', 30);
    $storageRoot = get_storage_root();
    $now = time();

    // 1. Purge from MySQL database if connected
    $purgedDbIds = db_purge_expired($expiryMinutes);
    foreach ($purgedDbIds as $pId) {
        $pDir = $storageRoot . DIRECTORY_SEPARATOR . $pId;
        if (is_dir($pDir)) {
            delete_directory_recursive($pDir);
        }
    }

    // 2. Sweep disk for any unrecorded or orphaned expired folders
    if (is_dir($storageRoot)) {
        $entries = @scandir($storageRoot);
        if ($entries) {
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..' || $entry === '.gitkeep') continue;
                $entryPath = $storageRoot . DIRECTORY_SEPARATOR . $entry;
                if (is_dir($entryPath)) {
                    $metaFile = $entryPath . DIRECTORY_SEPARATOR . 'meta.json';
                    $createdTime = 0;
                    if (file_exists($metaFile)) {
                        $meta = json_decode(@file_get_contents($metaFile), true);
                        $createdTime = (int) ($meta['created_at'] ?? 0);
                    }
                    if ($createdTime === 0) {
                        $createdTime = filemtime($entryPath);
                    }
                    if (($now - $createdTime) >= ($expiryMinutes * 60)) {
                        delete_directory_recursive($entryPath);
                        $db = get_db();
                        if ($db) {
                            $del = $db->prepare("DELETE FROM `buckets` WHERE `secret_id` = ?");
                            $del->execute([$entry]);
                        }
                    }
                }
            }
        }
    }
}

/**
 * Resolves and verifies base directory for a unique ID
 *
 * @param string $id
 * @param bool $autoCreate
 * @return string|false
 */
function get_id_directory($id, $autoCreate = false) {
    $cleanId = validate_unique_id($id);
    if (!$cleanId) {
        return false;
    }

    // Auto-run purge (lightweight)
    static $purgeRun = false;
    if (!$purgeRun) {
        purge_expired_storage();
        $purgeRun = true;
    }

    $base = get_storage_root();
    $target = $base . DIRECTORY_SEPARATOR . $cleanId;

    if (!$autoCreate) {
        if (!is_dir($target) || is_bucket_expired($cleanId)) {
            if (is_dir($target)) {
                delete_directory_recursive($target);
            }
            $db = get_db();
            if ($db) {
                $del = $db->prepare("DELETE FROM `buckets` WHERE `secret_id` = ?");
                $del->execute([$cleanId]);
            }
            return false;
        }
    } else {
        if (!is_dir($target)) {
            if (!@mkdir($target, 0755, true)) {
                return false;
            }
        }
    }

    // Verify it is strictly inside storage root
    $realBase = realpath($base);
    $realTarget = realpath($target);

    if ($realTarget === false || !str_starts_with($realTarget, $realBase)) {
        return false;
    }

    return $realTarget;
}

/**
 * Safely resolves a subpath within a unique ID directory, preventing directory traversal
 *
 * @param string $id
 * @param string $relativePath
 * @return string|false
 */
function safe_resolve_path($id, $relativePath = '') {
    $idDir = get_id_directory($id, false);
    if (!$idDir) {
        return false;
    }

    // Normalize slashes
    $cleanRel = trim(str_replace(['\\', '//'], '/', $relativePath), '/');

    // Reject any attempt of directory traversal
    $segments = explode('/', $cleanRel);
    foreach ($segments as $segment) {
        if ($segment === '..' || $segment === '.') {
            return false;
        }
    }

    if (empty($cleanRel)) {
        return $idDir;
    }

    $fullPath = $idDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRel);

    if (!file_exists($fullPath)) {
        return false;
    }

    $realFull = realpath($fullPath);
    if ($realFull === false || !str_starts_with($realFull, $idDir)) {
        return false;
    }

    return $realFull;
}

/**
 * Human-readable byte formatting
 *
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function format_bytes($bytes, $precision = 1) {
    if ($bytes <= 0) {
        return '0 B';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Determine file category by extension
 *
 * @param string $ext
 * @return string
 */
function get_file_category($ext) {
    $ext = strtolower(trim($ext, '.'));
    $categories = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'tiff'],
        'video' => ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'flv'],
        'audio' => ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'],
        'document' => ['pdf', 'doc', 'docx', 'odt', 'rtf', 'txt', 'pages'],
        'sheet' => ['xls', 'xlsx', 'csv', 'ods', 'tsv'],
        'presentation' => ['ppt', 'pptx', 'odp'],
        'archive' => ['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'],
        'code' => ['html', 'css', 'js', 'json', 'php', 'py', 'java', 'c', 'cpp', 'h', 'cs', 'go', 'rs', 'sh', 'sql', 'xml', 'yaml', 'yml', 'md']
    ];

    foreach ($categories as $category => $extensions) {
        if (in_array($ext, $extensions)) {
            return $category;
        }
    }
    return 'file';
}

/**
 * Check if a file extension is previewable in browser
 *
 * @param string $ext
 * @return bool
 */
function is_previewable($ext) {
    $category = get_file_category($ext);
    return in_array($category, ['image', 'video', 'audio', 'code']) || in_array(strtolower($ext), ['pdf', 'txt', 'md', 'json', 'csv', 'log', 'xml']);
}

/**
 * Get accurate MIME type for a file
 *
 * @param string $filePath
 * @return string
 */
function get_safe_mime_type($filePath) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $map = [
        'pdf' => 'application/pdf',
        'json' => 'application/json',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'svg' => 'image/svg+xml',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'txt' => 'text/plain; charset=utf-8',
        'md' => 'text/markdown; charset=utf-8',
        'html' => 'text/html; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'js' => 'application/javascript; charset=utf-8',
        'csv' => 'text/csv; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
        'zip' => 'application/zip'
    ];

    if (isset($map[$ext])) {
        return $map[$ext];
    }

    if (function_exists('mime_content_type') && file_exists($filePath)) {
        $detected = @mime_content_type($filePath);
        if ($detected) {
            return $detected;
        }
    }

    return 'application/octet-stream';
}

/**
 * Scans a folder for S3 Browser view
 *
 * @param string $id
 * @param string $subPath
 * @return array
 */
function get_browser_items($id, $subPath = '') {
    $baseDir = get_id_directory($id, false);
    if (!$baseDir) {
        return ['error' => 'Storage ID not found'];
    }

    $currentDir = safe_resolve_path($id, $subPath);
    if (!$currentDir || !is_dir($currentDir)) {
        return ['error' => 'Invalid directory path'];
    }

    $items = [];
    $folderCount = 0;
    $fileCount = 0;
    $totalBytes = 0;

    $entries = scandir($currentDir);
    if ($entries === false) {
        return ['error' => 'Unable to read directory'];
    }

    // Sort folders first, then files
    $dirs = [];
    $files = [];

    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..' || $entry === 'meta.json') {
            continue;
        }

        $fullPath = $currentDir . DIRECTORY_SEPARATOR . $entry;
        $relPath = ltrim(str_replace('\\', '/', substr($fullPath, strlen($baseDir))), '/');

        if (is_dir($fullPath)) {
            // Count items inside subfolder
            $subEntries = @scandir($fullPath);
            $itemCount = ($subEntries !== false) ? max(0, count($subEntries) - 2) : 0;
            $modTime = filemtime($fullPath);

            $dirs[] = [
                'name' => $entry,
                'path' => $relPath,
                'is_dir' => true,
                'category' => 'folder',
                'items_count' => $itemCount,
                'modified' => $modTime,
                'modified_formatted' => date('M d, Y h:i A', $modTime)
            ];
            $folderCount++;
        } else {
            $size = filesize($fullPath);
            $modTime = filemtime($fullPath);
            $ext = pathinfo($entry, PATHINFO_EXTENSION);
            $category = get_file_category($ext);

            $files[] = [
                'name' => $entry,
                'path' => $relPath,
                'is_dir' => false,
                'extension' => strtolower($ext),
                'category' => $category,
                'size' => $size,
                'size_formatted' => format_bytes($size),
                'is_previewable' => is_previewable($ext),
                'modified' => $modTime,
                'modified_formatted' => date('M d, Y h:i A', $modTime)
            ];
            $fileCount++;
            $totalBytes += $size;
        }
    }

    // Sort alphabetically
    usort($dirs, fn($a, $b) => strcasecmp($a['name'], $b['name']));
    usort($files, fn($a, $b) => strcasecmp($a['name'], $b['name']));

    // Calculate breadcrumb navigation
    $breadcrumbs = [['name' => $id, 'path' => '']];
    if (!empty($subPath)) {
        $parts = explode('/', trim($subPath, '/'));
        $accumulated = '';
        foreach ($parts as $part) {
            $accumulated = empty($accumulated) ? $part : $accumulated . '/' . $part;
            $breadcrumbs[] = [
                'name' => $part,
                'path' => $accumulated
            ];
        }
    }

    // Read meta.json if present
    $metaFile = $baseDir . DIRECTORY_SEPARATOR . 'meta.json';
    $meta = [];
    if (file_exists($metaFile)) {
        $metaContent = @file_get_contents($metaFile);
        $meta = json_decode($metaContent, true) ?? [];
    }

    // Merge database metrics if MySQL is connected
    $dbBucket = db_find_bucket($id);
    if ($dbBucket) {
        $meta['views_count'] = (int) ($dbBucket['views_count'] ?? 0);
        $meta['downloads_count'] = (int) ($dbBucket['downloads_count'] ?? 0);
        $meta['title'] = $dbBucket['title'] ?? ($meta['title'] ?? '');
        $meta['note'] = $dbBucket['note'] ?? ($meta['note'] ?? '');

        // Fetch download counts for current directory files
        $db = get_db();
        if ($db) {
            $stmt = $db->prepare("SELECT `relative_path`, `download_count` FROM `files` WHERE `secret_id` = ?");
            $stmt->execute([$id]);
            $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($files as &$file) {
                $file['download_count'] = (int) ($counts[$file['path']] ?? 0);
            }
            unset($file);
        }
    }

    // Calculate expiration countdown
    $expiryMinutes = (int) get_config('storage', 'auto_delete_minutes', 30);
    $expirySeconds = $expiryMinutes * 60;
    $now = time();

    $expiresAtTimestamp = 0;
    if (!empty($dbBucket['expires_at'])) {
        $expiresAtTimestamp = strtotime($dbBucket['expires_at']);
    } elseif (!empty($dbBucket['created_at'])) {
        $expiresAtTimestamp = strtotime($dbBucket['created_at']) + $expirySeconds;
    } elseif (!empty($meta['created_at'])) {
        $expiresAtTimestamp = (int) $meta['created_at'] + $expirySeconds;
    } else {
        $expiresAtTimestamp = filemtime($baseDir) + $expirySeconds;
    }

    $expiresInSeconds = max(0, $expiresAtTimestamp - $now);

    return [
        'success' => true,
        'id' => $id,
        'current_path' => trim($subPath, '/'),
        'breadcrumbs' => $breadcrumbs,
        'folders' => $dirs,
        'files' => $files,
        'folder_count' => $folderCount,
        'file_count' => $fileCount,
        'total_size' => $totalBytes,
        'total_size_formatted' => format_bytes($totalBytes),
        'views_count' => $meta['views_count'] ?? 0,
        'downloads_count' => $meta['downloads_count'] ?? 0,
        'expires_at' => date('Y-m-d H:i:s', $expiresAtTimestamp),
        'expires_in_seconds' => $expiresInSeconds,
        'expiry_minutes' => $expiryMinutes,
        'meta' => $meta
    ];
}

/**
 * Recursively creates a ZIP archive of a directory
 *
 * @param string $sourceDir
 * @param string $zipFilePath
 * @param string $zipInternalRoot
 * @return bool
 */
function create_zip_archive($sourceDir, $zipFilePath, $zipInternalRoot = '') {
    if (!class_exists('ZipArchive')) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $sourceDir = realpath($sourceDir);
    if (!$sourceDir || !is_dir($sourceDir)) {
        $zip->close();
        return false;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        // Skip meta.json or temp files
        if ($file->getFilename() === 'meta.json') {
            continue;
        }

        $relPath = substr($filePath, strlen($sourceDir) + 1);
        $relPath = str_replace('\\', '/', $relPath);

        if (!empty($zipInternalRoot)) {
            $relPath = trim($zipInternalRoot, '/') . '/' . $relPath;
        }

        if ($file->isDir()) {
            $zip->addEmptyDir($relPath);
        } elseif ($file->isFile()) {
            $zip->addFile($filePath, $relPath);
        }
    }

    return $zip->close();
}

/**
 * Extracts a ZIP file into target destination safely
 *
 * @param string $zipFilePath
 * @param string $destinationDir
 * @return bool
 */
function extract_zip_safely($zipFilePath, $destinationDir) {
    if (!class_exists('ZipArchive')) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFilePath) !== true) {
        return false;
    }

    $destReal = realpath($destinationDir);
    if (!$destReal) {
        @mkdir($destinationDir, 0755, true);
        $destReal = realpath($destinationDir);
    }

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        if (!$stat) continue;

        $entryName = str_replace('\\', '/', $stat['name']);
        
        // Prevent zip slip vulnerability
        if (str_contains($entryName, '../') || str_contains($entryName, '..\\')) {
            continue;
        }

        $targetPath = $destReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entryName);

        if (str_ends_with($entryName, '/')) {
            @mkdir($targetPath, 0755, true);
        } else {
            $parentDir = dirname($targetPath);
            if (!is_dir($parentDir)) {
                @mkdir($parentDir, 0755, true);
            }
            copy("zip://" . $zipFilePath . "#" . $stat['name'], $targetPath);
        }
    }

    $zip->close();
    return true;
}

/**
 * Calculates overall stats (total file count and total bytes) for a storage ID
 *
 * @param string $id
 * @return array
 */
function get_storage_stats($id) {
    $dir = get_id_directory($id, false);
    if (!$dir) {
        return ['files' => 0, 'folders' => 0, 'size' => 0, 'size_formatted' => '0 B'];
    }

    $fileCount = 0;
    $folderCount = 0;
    $totalBytes = 0;

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($it as $item) {
        if ($item->getFilename() === 'meta.json') continue;
        if ($item->isDir()) {
            $folderCount++;
        } elseif ($item->isFile()) {
            $fileCount++;
            $totalBytes += $item->getSize();
        }
    }

    return [
        'files' => $fileCount,
        'folders' => $folderCount,
        'size' => $totalBytes,
        'size_formatted' => format_bytes($totalBytes)
    ];
}

/**
 * Helper to output standard JSON response
 *
 * @param mixed $data
 * @param int $statusCode
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
