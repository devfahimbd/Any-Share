<?php
/**
 * Any Share - Configuration Loader
 * Safely parses config.ini and exposes global configuration settings
 */

if (!defined('ANY_SHARE')) {
    define('ANY_SHARE', true);
}

// Path to config.ini
$configFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.ini';

// Default configuration fallbacks
$defaultConfig = [
    'app' => [
        'app_name' => 'Any Share',
        'app_version' => '1.0.0',
        'app_description' => 'Private, Instant & Anonymous S3-Style Cloud File Sharing',
        'base_url' => ''
    ],
    'storage' => [
        'upload_dir' => 'uploads',
        'max_file_size_mb' => 1024,
        'allow_zip_extraction' => true,
        'preserve_directory_structure' => true
    ],
    'security' => [
        'min_id_length' => 3,
        'max_id_length' => 64,
        'allowed_id_pattern' => '^[a-zA-Z0-9_\-\.]+$',
        'allow_public_indexing' => false
    ],
    'ui' => [
        'theme' => 'dark',
        'items_per_page' => 50,
        'enable_code_syntax_highlight' => true
    ]
];

// Load INI file if exists
if (file_exists($configFile)) {
    $parsedConfig = @parse_ini_file($configFile, true, INI_SCANNER_TYPED);
    if ($parsedConfig !== false) {
        $config = array_replace_recursive($defaultConfig, $parsedConfig);
    } else {
        $config = $defaultConfig;
    }
} else {
    $config = $defaultConfig;
}

// Set application timezone (defaults to Asia/Dhaka)
$appTimezone = $config['app']['timezone'] ?? 'Asia/Dhaka';
if (!empty($appTimezone)) {
    @date_default_timezone_set($appTimezone);
}

// Auto-detect Base URL if not specified
if (empty($config['app']['base_url'])) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Auto-detect project subfolder (e.g., /Any%20Share or /)
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    
    // Normalize script directory to project root if executed from subfolder (like /api)
    $scriptDir = preg_replace('#/(api|includes|assets.*)$#', '', $scriptDir);
    $scriptDir = rtrim($scriptDir, '/');
    
    $config['app']['base_url'] = $protocol . $host . $scriptDir;
}

// Ensure Upload Directory exists
$uploadDirPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $config['storage']['upload_dir'];
if (!is_dir($uploadDirPath)) {
    @mkdir($uploadDirPath, 0755, true);
}

// Global helper access
function get_config($section, $key = null, $default = null) {
    global $config;
    if ($key === null) {
        return $config[$section] ?? $default;
    }
    return $config[$section][$key] ?? $default;
}
