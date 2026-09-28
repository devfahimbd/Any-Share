<?php
/**
 * Any Share - Automatic Cron & Cleanup Worker
 * Automatically deletes buckets and files older than 30 minutes
 * Can be run via CLI, cron job, or HTTP request
 */

require_once __DIR__ . '/includes/functions.php';

// Purge all expired storage
purge_expired_storage();

if (php_sapi_name() === 'cli') {
    echo "[" . date('Y-m-d H:i:s') . "] Any Share 30-minute cleanup completed successfully." . PHP_EOL;
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => '30-minute auto-cleanup executed successfully.',
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
