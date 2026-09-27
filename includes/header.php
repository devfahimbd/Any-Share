<?php
/**
 * Any Share - Common HTML Header
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? get_config('app', 'app_name', 'Any Share');
$pageDesc = $pageDesc ?? get_config('app', 'app_description', 'Private & Instant S3-Style File Sharing');
$baseUrl = rtrim(get_config('app', 'base_url', ''), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(get_config('app', 'app_name', 'Any Share')) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
    <script>
        window.ANY_SHARE = {
            baseUrl: <?= json_encode($baseUrl) ?>,
            maxUploadSizeMB: <?= (int) get_config('storage', 'max_file_size_mb', 1024) ?>
        };
    </script>
</head>
<body>
    <div class="background-mesh">
        <div class="mesh-orb orb-1"></div>
        <div class="mesh-orb orb-2"></div>
        <div class="mesh-orb orb-3"></div>
    </div>

    <header class="app-header">
        <div class="header-inner container">
            <a href="<?= $baseUrl ?>/" class="brand-logo">
                <div class="logo-icon-wrap">
                    <svg class="logo-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>
                        <path d="m14 12-3-3-3 3"/>
                        <path d="M11 9v8"/>
                    </svg>
                </div>
                <div class="brand-text">
                    <span class="brand-title">Any<span class="accent">Share</span></span>
                    <div style="display: flex; align-items: center; gap: 0.4rem;">
                        <span class="brand-badge">S3 Explorer</span>
                        <?php if (db_is_connected()): ?>
                            <span class="db-status-badge" title="MySQL Connected (XAMPP / cPanel)">🟢 MySQL</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>

            <nav class="nav-links">
                <a href="<?= $baseUrl ?>/" class="nav-item <?= empty($activeNav) || $activeNav === 'home' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Home & Upload</span>
                </a>
                <a href="#searchSection" class="nav-item" onclick="if(document.getElementById('secretSearchInput')) { document.getElementById('secretSearchInput').focus(); }">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <span>Search Key</span>
                </a>
                <a href="https://github.com/devfahimbd/Any-Share" target="_blank" rel="noopener" class="nav-item nav-github">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0-1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22"/></svg>
                    <span>GitHub</span>
                </a>
            </nav>
        </div>
    </header>

    <main class="main-content">
