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

            <div class="header-actions">
                <a href="https://github.com/devfahimbd/Any-Share" target="_blank" rel="noopener" class="header-github-btn" title="GitHub Repository" aria-label="GitHub Repository">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                </a>
            </div>
        </div>
    </header>

    <main class="main-content">
