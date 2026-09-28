<?php
/**
 * Any Share - Upload API Handler
 * Handles both File Uploads (files, folders, zips) and Direct Text/Note Uploads
 * Synchronizes with MySQL database and assigns 30-minute auto-expiry
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed. Use POST.'], 405);
}

// Check for Unique ID
$rawId = $_POST['unique_id'] ?? '';
$autoGenerate = filter_var($_POST['auto_generate'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (empty($rawId) && $autoGenerate) {
    // Generate a clean alphanumeric secret key: 8 random characters
    $rawId = 'as_' . substr(bin2hex(random_bytes(6)), 0, 8);
}

$uniqueId = validate_unique_id($rawId);
if (!$uniqueId) {
    json_response([
        'success' => false,
        'error' => 'Invalid Secret ID format. Must be 3-64 characters and contain only letters, numbers, hyphens (-), underscores (_), and dots (.).'
    ], 400);
}

// Check if uploading Text Content or Files
$textContent = $_POST['text_content'] ?? null;
$hasFiles = !empty($_FILES['files']) && is_array($_FILES['files']['name']) && !empty($_FILES['files']['name'][0]);

if ($textContent === null && !$hasFiles) {
    json_response(['success' => false, 'error' => 'No files or text content provided for upload.'], 400);
}

// Target directory for this unique ID
$targetDir = get_id_directory($uniqueId, true);
if (!$targetDir) {
    json_response(['success' => false, 'error' => 'Failed to create or access storage directory for this Secret ID.'], 500);
}

$uploadedFiles = [];
$failedFiles = [];
$totalUploadedBytes = 0;
$maxSizeMB = (int) get_config('storage', 'max_file_size_mb', 512);
$maxSizeBytes = $maxSizeMB * 1024 * 1024;
$autoDeleteMinutes = (int) get_config('storage', 'auto_delete_minutes', 30);

// 1. Process Direct Text / Note Upload
if ($textContent !== null && trim($textContent) !== '') {
    $textFilename = trim($_POST['text_filename'] ?? 'note.txt');
    // Sanitize filename
    $textFilename = preg_replace('/[^\w\.\-\s]/u', '_', $textFilename);
    if (empty($textFilename) || str_contains($textFilename, '..')) {
        $textFilename = 'note.txt';
    }
    if (!pathinfo($textFilename, PATHINFO_EXTENSION)) {
        $textFilename .= '.txt';
    }

    $destPath = $targetDir . DIRECTORY_SEPARATOR . $textFilename;
    if (@file_put_contents($destPath, $textContent) !== false) {
        $size = filesize($destPath);
        $uploadedFiles[] = [
            'name' => $textFilename,
            'path' => $textFilename,
            'size' => $size,
            'size_formatted' => format_bytes($size)
        ];
        $totalUploadedBytes += $size;
    } else {
        $failedFiles[] = [
            'name' => $textFilename,
            'reason' => 'Failed to write text file to storage.'
        ];
    }
}

// 2. Process File Uploads (Single, Multiple, Folders, or ZIPs)
if ($hasFiles) {
    $relativePaths = $_POST['relative_paths'] ?? [];
    $autoExtractZip = filter_var($_POST['extract_zip'] ?? true, FILTER_VALIDATE_BOOLEAN);

    $fileNames = $_FILES['files']['name'];
    $fileTmpNames = $_FILES['files']['tmp_name'];
    $fileSizes = $_FILES['files']['size'];
    $fileErrors = $_FILES['files']['error'];
    $fileCount = count($fileNames);

    for ($i = 0; $i < $fileCount; $i++) {
        $originalName = $fileNames[$i];
        $tmpName = $fileTmpNames[$i];
        $size = $fileSizes[$i];
        $error = $fileErrors[$i];

        if ($error !== UPLOAD_ERR_OK) {
            $failedFiles[] = [
                'name' => $originalName,
                'reason' => 'Upload error code: ' . $error
            ];
            continue;
        }

        if ($size > $maxSizeBytes) {
            $failedFiles[] = [
                'name' => $originalName,
                'reason' => 'File exceeds maximum allowed size of ' . $maxSizeMB . ' MB'
            ];
            continue;
        }

        // Determine target relative path (retains folder directory structure)
        $relPath = '';
        if (!empty($relativePaths[$i])) {
            $relPath = trim(str_replace(['\\', '//'], '/', $relativePaths[$i]), '/');
        }

        if (empty($relPath) || str_contains($relPath, '..')) {
            $relPath = basename($originalName);
        }

        // Sanitize path segments
        $pathParts = explode('/', $relPath);
        $safeParts = array_map(function($part) {
            return preg_replace('/[^\w\.\-\s\(\)\[\]]/u', '_', $part);
        }, $pathParts);

        $safeRelPath = implode(DIRECTORY_SEPARATOR, $safeParts);
        $destFilePath = $targetDir . DIRECTORY_SEPARATOR . $safeRelPath;
        $destFileDir = dirname($destFilePath);

        if (!is_dir($destFileDir)) {
            @mkdir($destFileDir, 0755, true);
        }

        if (move_uploaded_file($tmpName, $destFilePath)) {
            $ext = strtolower(pathinfo($destFilePath, PATHINFO_EXTENSION));

            // Auto-extract ZIP if requested
            if ($ext === 'zip' && $autoExtractZip && get_config('storage', 'allow_zip_extraction', true)) {
                $zipSubdir = $destFileDir . DIRECTORY_SEPARATOR . pathinfo($destFilePath, PATHINFO_FILENAME);
                extract_zip_safely($destFilePath, $zipSubdir);
            }

            $uploadedFiles[] = [
                'name' => $originalName,
                'path' => str_replace('\\', '/', $safeRelPath),
                'size' => $size,
                'size_formatted' => format_bytes($size)
            ];
            $totalUploadedBytes += $size;
        } else {
            $failedFiles[] = [
                'name' => $originalName,
                'reason' => 'Failed to move uploaded file.'
            ];
        }
    }
}

// 3. Metadata & Expiry Storage
$metaFile = $targetDir . DIRECTORY_SEPARATOR . 'meta.json';
$existingMeta = file_exists($metaFile) ? json_decode(@file_get_contents($metaFile), true) : [];
if (!is_array($existingMeta)) {
    $existingMeta = [];
}

$now = time();
$expiresAtTimestamp = $now + ($autoDeleteMinutes * 60);

$metaData = [
    'unique_id' => $uniqueId,
    'created_at' => $existingMeta['created_at'] ?? $now,
    'created_formatted' => $existingMeta['created_formatted'] ?? date('Y-m-d H:i:s', $now),
    'updated_at' => $now,
    'updated_formatted' => date('Y-m-d H:i:s', $now),
    'expires_at' => date('Y-m-d H:i:s', $expiresAtTimestamp),
    'expiry_minutes' => $autoDeleteMinutes,
    'note' => trim($_POST['note'] ?? ($existingMeta['note'] ?? '')),
];

@file_put_contents($metaFile, json_encode($metaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

$stats = get_storage_stats($uniqueId);

// 4. Persist to MySQL database with 30-minute expiry
$bucketId = db_create_or_update_bucket($uniqueId, $stats['files'], $stats['size'], $metaData['note'], $autoDeleteMinutes);
if ($bucketId) {
    foreach ($uploadedFiles as $uFile) {
        $ext = strtolower(pathinfo($uFile['name'], PATHINFO_EXTENSION));
        $fullUploadedPath = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $uFile['path']);
        db_save_file($bucketId, $uniqueId, [
            'name' => $uFile['name'],
            'path' => $uFile['path'],
            'size' => $uFile['size'],
            'file_type' => get_safe_mime_type($fullUploadedPath),
            'extension' => $ext,
            'category' => get_file_category($ext)
        ]);
    }
}

$baseUrl = rtrim(get_config('app', 'base_url', ''), '/');
$browseUrl = $baseUrl . '/browse.php?id=' . urlencode($uniqueId);

json_response([
    'success' => count($uploadedFiles) > 0,
    'message' => count($uploadedFiles) . ' item(s) uploaded successfully! (Expires in ' . $autoDeleteMinutes . ' minutes)',
    'unique_id' => $uniqueId,
    'uploaded_files' => $uploadedFiles,
    'failed_files' => $failedFiles,
    'total_stats' => $stats,
    'expires_at' => date('Y-m-d H:i:s', $expiresAtTimestamp),
    'expires_in_minutes' => $autoDeleteMinutes,
    'browse_url' => $browseUrl
]);
