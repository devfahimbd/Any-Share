<?php
/**
 * Any Share - Clean White & Emerald Header
 * Minimal, fast, no clutter
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? get_config('app', 'app_name', 'Any Share');
$baseUrl = rtrim(get_config('app', 'base_url', ''), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(get_config('app', 'app_name', 'Any Share')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <script>
        window.ANY_SHARE = {
            baseUrl: <?= json_encode($baseUrl) ?>,
            maxUploadSizeMB: <?= (int) get_config('storage', 'max_file_size_mb', 512) ?>,
            autoDeleteMinutes: <?= (int) get_config('storage', 'auto_delete_minutes', 30) ?>
        };
    </script>
</head>
<body>
    <div class="background-mesh"></div>

    <header class="app-header">
        <div class="header-inner container">
            <a href="<?= $baseUrl ?>/" class="brand-logo">
                <div class="logo-icon-wrap">
                    <svg class="logo-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                        <path d="m14 12-3-3-3 3"/>
                        <path d="M11 9v8"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <span class="brand-title">Any<span class="accent">Share</span></span>
                </div>
            </a>

            <div style="display: flex; align-items: center; gap: 0.6rem;">
                <span class="expiry-pill-banner" style="margin-bottom: 0;" title="Files automatically delete 30 minutes after upload">
                    ⏱️ Auto-deletes in 30 mins
                </span>
                <?php if (db_is_connected()): ?>
                    <span class="db-status-badge" title="Connected to MySQL Database">🟢 MySQL</span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
