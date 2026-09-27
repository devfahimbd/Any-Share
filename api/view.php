<?php
/**
 * Any Share - File Viewer & Streamer API
 * Safely streams media, PDF, text, and code files for inline browser preview
 */

require_once __DIR__ . '/../includes/functions.php';

$rawId = $_GET['id'] ?? '';
$uniqueId = validate_unique_id($rawId);

if (!$uniqueId) {
    http_response_code(400);
    die('Invalid Secret ID.');
}

$relPath = $_GET['path'] ?? '';
$targetPath = safe_resolve_path($uniqueId, $relPath);

if (!$targetPath || !is_file($targetPath)) {
    http_response_code(404);
    die('File not found.');
}

$fileName = basename($targetPath);
$ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
$mimeType = get_safe_mime_type($targetPath);
$fileSize = filesize($targetPath);

// If client requests raw text/code as JSON for syntax-highlighted modal
if (isset($_GET['raw']) && $_GET['raw'] === '1') {
    // Only allow text/code under 5MB to prevent memory crashes
    if ($fileSize > 5 * 1024 * 1024) {
        json_response(['success' => false, 'error' => 'File too large for inline text viewer.'], 400);
    }

    $content = @file_get_contents($targetPath);
    json_response([
        'success' => true,
        'filename' => $fileName,
        'extension' => $ext,
        'size' => $fileSize,
        'size_formatted' => format_bytes($fileSize),
        'content' => $content
    ]);
}

// Clear output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Security: Prevent script execution inside upload folder
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . addslashes($fileName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: public, max-age=86400');

// Stream file
$fp = fopen($targetPath, 'rb');
if ($fp) {
    while (!feof($fp)) {
        echo fread($fp, 1024 * 64);
        flush();
    }
    fclose($fp);
}
exit;
