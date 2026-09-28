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
    <link rel="icon" type="image/png" href="<?= $baseUrl ?>/assets/img/favicon.png">
    <link rel="apple-touch-icon" href="<?= $baseUrl ?>/assets/img/icon-192.png">
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
                    <img src="<?= $baseUrl ?>/assets/img/logo.png" alt="Any Share Logo" class="brand-logo-img" width="36" height="36">
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
