<?php
/**
 * Any Share - Search / Lookup API Handler
 * Looks up existence and metadata for a given Unique Secret ID
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

$rawId = $_GET['id'] ?? ($_POST['id'] ?? '');
$uniqueId = validate_unique_id($rawId);

if (!$uniqueId) {
    json_response([
        'success' => false,
        'error' => 'Please enter a valid Secret ID.'
    ], 400);
}

$idDir = get_id_directory($uniqueId, false);
if (!$idDir) {
    json_response([
        'success' => false,
        'error' => 'No files or folders found with Secret ID: ' . htmlspecialchars($uniqueId)
    ], 404);
}

// Record search / view metric in database
db_increment_views($uniqueId);

// Fetch stats and top-level listing
$stats = get_storage_stats($uniqueId);
$items = get_browser_items($uniqueId, '');

$baseUrl = rtrim(get_config('app', 'base_url', ''), '/');
$browseUrl = $baseUrl . '/browse.php?id=' . urlencode($uniqueId);

json_response([
    'success' => true,
    'id' => $uniqueId,
    'browse_url' => $browseUrl,
    'stats' => $stats,
    'views_count' => $items['views_count'] ?? 0,
    'downloads_count' => $items['downloads_count'] ?? 0,
    'expires_at' => $items['expires_at'] ?? null,
    'expires_in_seconds' => $items['expires_in_seconds'] ?? 0,
    'folder_count' => $items['folder_count'] ?? 0,
    'file_count' => $items['file_count'] ?? 0,
    'total_size_formatted' => $stats['size_formatted'] ?? '0 B',
    'meta' => $items['meta'] ?? []
]);
