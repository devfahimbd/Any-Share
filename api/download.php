<?php
/**
 * Any Share - Download API Handler
 * Securely streams files or generates dynamic ZIP archives for full folder downloads
 */

require_once __DIR__ . '/../includes/functions.php';

$rawId = $_GET['id'] ?? '';
$uniqueId = validate_unique_id($rawId);

if (!$uniqueId) {
    http_response_code(400);
    die('Invalid Secret ID.');
}

$idDir = get_id_directory($uniqueId, false);
if (!$idDir) {
    http_response_code(404);
    die('Storage directory not found for this ID.');
}

$relPath = $_GET['path'] ?? '';
$isZipDownload = filter_var($_GET['zip'] ?? false, FILTER_VALIDATE_BOOLEAN);

// 1. If ZIP download requested (or if target is a directory)
$targetPath = safe_resolve_path($uniqueId, $relPath);

if ($isZipDownload || ($targetPath && is_dir($targetPath))) {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        die('PHP ZipArchive extension is not enabled on this server.');
    }

    $sourceDir = ($targetPath && is_dir($targetPath)) ? $targetPath : $idDir;
    $zipBaseName = empty($relPath) ? $uniqueId : basename($relPath);
    $zipFileName = $zipBaseName . '_' . date('Ymd_His') . '.zip';

    // Temporary ZIP file
    $tempZip = tempnam(sys_get_temp_dir(), 'any_share_');
    if (!create_zip_archive($sourceDir, $tempZip, $zipBaseName)) {
        http_response_code(500);
        die('Failed to generate ZIP archive.');
    }

    // Record download count in database
    db_increment_downloads($uniqueId);

    // Clean output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . addslashes($zipFileName) . '"');
    header('Content-Length: ' . filesize($tempZip));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    readfile($tempZip);
    @unlink($tempZip);
    exit;
}

// 2. Individual file download
if (!$targetPath || !is_file($targetPath)) {
    http_response_code(404);
    die('Requested file does not exist.');
}

$fileName = basename($targetPath);
$fileSize = filesize($targetPath);
$mimeType = get_safe_mime_type($targetPath);

// Record download in database
db_increment_downloads($uniqueId, $relPath);

// Clear output buffers
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

// Stream file in chunks
$fp = fopen($targetPath, 'rb');
if ($fp) {
    while (!feof($fp)) {
        echo fread($fp, 1024 * 64); // 64KB chunks
        flush();
    }
    fclose($fp);
}
exit;
