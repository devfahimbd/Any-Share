<?php
/**
 * Any Share - S3-Style Storage Explorer Page
 * Enables interactive exploration of folders, files, breadcrumb navigation, previewing, and downloading
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

$rawId = $_GET['id'] ?? '';
$uniqueId = validate_unique_id($rawId);
$currentSubPath = trim(str_replace(['\\', '//'], '/', $_GET['path'] ?? ''), '/');

$pageTitle = $uniqueId ? "Explorer: {$uniqueId}" : "Storage Explorer";
$activeNav = 'browse';
$extraScripts = ['assets/js/browser.js'];

require_once __DIR__ . '/includes/header.php';

// If invalid or missing ID
if (!$uniqueId || !get_id_directory($uniqueId, false)) {
?>
<div class="container" style="max-width: 650px; text-align: center; padding: 4rem 1.5rem;">
    <div class="glass-card" style="padding: 3rem 2rem;">
        <div style="font-size: 3.5rem; margin-bottom: 1rem;">🔍</div>
        <h2 style="font-size: 1.6rem; margin-bottom: 0.5rem;">Storage Not Found</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem;">
            <?= $rawId ? 'No storage bucket found for Secret Key: <code>' . htmlspecialchars($rawId) . '</code>' : 'Please provide a valid Secret Key to explore files.' ?>
        </p>

        <!-- Inline Search Form -->
        <form id="secretSearchForm" class="search-box" style="margin-bottom: 1.5rem;">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" id="secretSearchInput" class="search-input" placeholder="Enter Secret ID..." required>
            <button type="submit" class="search-btn">Search</button>
        </form>

        <a href="<?= $baseUrl ?>/" class="btn btn-secondary">Back to Home & Upload</a>
    </div>
</div>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch browser items for this path
$browserData = get_browser_items($uniqueId, $currentSubPath);
$totalStats = get_storage_stats($uniqueId);

$folders = $browserData['folders'] ?? [];
$files = $browserData['files'] ?? [];
$breadcrumbs = $browserData['breadcrumbs'] ?? [];
$folderCount = count($folders);
$fileCount = count($files);
$hasItems = ($folderCount + $fileCount) > 0;

$zipDownloadUrl = "{$baseUrl}/api/download.php?id=" . urlencode($uniqueId) . ($currentSubPath ? "&path=" . urlencode($currentSubPath) : '') . "&zip=1";
?>

<div class="container">
    <!-- Unified Modern Explorer Header & Action Bar -->
    <div class="s3-explorer-header">
        <!-- Upper Row: Bucket Info, Storage Stats & Countdown -->
        <div class="explorer-meta-row">
            <div class="explorer-id-group">
                <div class="bucket-pill">
                    <div class="bucket-pill-icon">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>
                        </svg>
                    </div>
                    <span class="bucket-pill-label">Bucket:</span>
                    <strong class="bucket-pill-id"><?= htmlspecialchars($uniqueId) ?></strong>
                    <button type="button" class="btn-copy-id" id="copyIdBtn" data-id="<?= htmlspecialchars($uniqueId) ?>" title="Copy Secret ID">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    </button>
                </div>

                <div class="explorer-stats-wrap">
                    <span class="stat-chip">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                        <span><strong><?= $totalStats['files'] ?></strong> files (<?= $totalStats['size_formatted'] ?>)</span>
                    </span>
                    <?php if (isset($browserData['views_count'])): ?>
                    <span class="stat-chip">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <span><strong><?= (int)$browserData['views_count'] ?></strong> views</span>
                    </span>
                    <span class="stat-chip">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span><strong><?= (int)$browserData['downloads_count'] ?></strong> downloads</span>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Live Expiration Timer -->
            <div class="countdown-badge-card" title="Files auto-delete 30 minutes after upload">
                <div class="countdown-pulse-icon">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <span class="countdown-label">Expires in:</span>
                <span id="bucketCountdown" class="countdown-timer-text" data-seconds="<?= (int)($browserData['expires_in_seconds'] ?? 1800) ?>">--:--</span>
            </div>
        </div>

        <div class="explorer-divider"></div>

        <!-- Lower Row: Breadcrumbs and Action Toolbar -->
        <div class="explorer-toolbar-row">
            <!-- Directory Breadcrumb Trail -->
            <nav class="explorer-breadcrumb-trail" aria-label="Directory Breadcrumbs">
                <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?>" class="crumb-link <?= empty($currentSubPath) ? 'crumb-active' : '' ?>">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    <span>Root</span>
                </a>
                <?php if (!empty($currentSubPath)): ?>
                    <?php foreach ($breadcrumbs as $index => $crumb): ?>
                        <span class="crumb-sep">/</span>
                        <?php if ($index === count($breadcrumbs) - 1): ?>
                            <span class="crumb-current"><?= htmlspecialchars($crumb['name']) ?></span>
                        <?php else: ?>
                            <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?><?= $crumb['path'] ? '&path=' . urlencode($crumb['path']) : '' ?>" class="crumb-link">
                                <?= htmlspecialchars($crumb['name']) ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>

            <!-- Actions Toolbar -->
            <div class="explorer-btn-toolbar">
                <!-- View Toggle (List/Grid) -->
                <div class="view-mode-pill">
                    <button type="button" id="viewTableBtn" class="view-mode-btn active" title="List View">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                        <span class="btn-text-desktop">List</span>
                    </button>
                    <button type="button" id="viewGridBtn" class="view-mode-btn" title="Grid View">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                        <span class="btn-text-desktop">Grid</span>
                    </button>
                </div>

                <!-- Download ZIP -->
                <a href="<?= $zipDownloadUrl ?>" class="btn-action-emerald" title="Download entire folder or bucket as ZIP">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Download ZIP</span>
                </a>

                <!-- Share Link -->
                <button type="button" id="copyShareUrlBtn" class="btn-action-secondary" title="Copy shareable link">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    <span>Share Link</span>
                </button>

                <!-- Upload More -->
                <a href="<?= $baseUrl ?>/?id=<?= urlencode($uniqueId) ?>#uploadSection" class="btn-action-primary" title="Upload more files">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span>Upload More</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Explorer Content Container -->
    <div class="explorer-table-card">
        <?php if (!$hasItems): ?>
            <div class="s3-empty-state">
                <div class="s3-empty-icon">📂</div>
                <h3>This folder is empty</h3>
                <p style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--text-dim);">No files or subdirectories found inside this path.</p>
                <div style="margin-top: 1.5rem;">
                    <a href="<?= $baseUrl ?>/?id=<?= urlencode($uniqueId) ?>#uploadSection" class="btn btn-primary">Upload Files Here</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Table View -->
            <div id="s3TableView" class="s3-table-wrap">
                <table class="modern-s3-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Name</th>
                            <th class="col-hide-mobile" style="width: 14%;">Size</th>
                            <th class="col-hide-mobile" style="width: 13%;">Type</th>
                            <th class="col-hide-mobile" style="width: 18%;">Last Modified</th>
                            <th style="width: 10%; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Parent Directory Link -->
                        <?php if (!empty($currentSubPath)): 
                            $parentPath = dirname($currentSubPath);
                            if ($parentPath === '.' || $parentPath === '/') $parentPath = '';
                        ?>
                        <tr class="table-row-parent">
                            <td colspan="5">
                                <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?><?= $parentPath ? '&path=' . urlencode($parentPath) : '' ?>" class="file-name-cell" style="text-decoration: none;">
                                    <div class="file-icon-box icon-parent">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
                                    </div>
                                    <div class="file-name-info">
                                        <span class="file-main-link" style="color: var(--text-muted); font-weight: 700;">.. (Up to Parent Directory)</span>
                                    </div>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <!-- Subfolders -->
                        <?php foreach ($folders as $folder): ?>
                        <tr>
                            <td>
                                <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?>&path=<?= urlencode($folder['path']) ?>" class="file-name-cell" style="text-decoration: none;">
                                    <div class="file-icon-box icon-folder">
                                        📁
                                    </div>
                                    <div class="file-name-info">
                                        <span class="file-main-link"><?= htmlspecialchars($folder['name']) ?></span>
                                        <div class="mobile-file-meta"><?= $folder['items_count'] ?> item(s) • FOLDER</div>
                                    </div>
                                </a>
                            </td>
                            <td class="col-hide-mobile"><span class="file-size-text"><?= $folder['items_count'] ?> item(s)</span></td>
                            <td class="col-hide-mobile"><span class="file-type-pill">FOLDER</span></td>
                            <td class="col-hide-mobile"><span class="file-date-text"><?= $folder['modified_formatted'] ?></span></td>
                            <td>
                                <div class="table-actions-group">
                                    <a href="<?= $baseUrl ?>/api/download.php?id=<?= urlencode($uniqueId) ?>&path=<?= urlencode($folder['path']) ?>&zip=1" class="action-btn-circle action-btn-download" title="Download Folder as ZIP">
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <!-- Files -->
                        <?php foreach ($files as $file): 
                            $viewUrl = "{$baseUrl}/api/view.php?id=" . urlencode($uniqueId) . "&path=" . urlencode($file['path']);
                            $downloadUrl = "{$baseUrl}/api/download.php?id=" . urlencode($uniqueId) . "&path=" . urlencode($file['path']);
                            $fileJson = htmlspecialchars(json_encode($file), ENT_QUOTES, 'UTF-8');
                            
                            $iconClass = 'icon-generic';
                            $iconSymbol = '📄';
                            if ($file['category'] === 'image') { $iconClass = 'icon-image'; $iconSymbol = '🖼️'; }
                            elseif ($file['category'] === 'video') { $iconClass = 'icon-video'; $iconSymbol = '🎬'; }
                            elseif ($file['category'] === 'audio') { $iconClass = 'icon-audio'; $iconSymbol = '🎵'; }
                            elseif ($file['category'] === 'code') { $iconClass = 'icon-code'; $iconSymbol = '💻'; }
                            elseif ($file['extension'] === 'pdf') { $iconClass = 'icon-pdf'; $iconSymbol = '📑'; }
                            elseif ($file['category'] === 'archive') { $iconClass = 'icon-archive'; $iconSymbol = '📦'; }
                        ?>
                        <tr data-preview-item='<?= $fileJson ?>' data-unique-id="<?= htmlspecialchars($uniqueId) ?>" style="cursor: pointer;">
                            <td>
                                <div class="file-name-cell">
                                    <div class="file-icon-box <?= $iconClass ?>">
                                        <?= $iconSymbol ?>
                                    </div>
                                    <div class="file-name-info">
                                        <span class="file-main-link" title="<?= htmlspecialchars($file['name']) ?>">
                                            <?= htmlspecialchars($file['name']) ?>
                                            <?php if (!empty($file['download_count'])): ?>
                                                <span class="download-count-pill" title="<?= (int)$file['download_count'] ?> downloads">⬇️ <?= (int)$file['download_count'] ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <div class="mobile-file-meta"><?= $file['size_formatted'] ?> • <?= strtoupper($file['extension'] ?: 'FILE') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="col-hide-mobile"><span class="file-size-text"><?= $file['size_formatted'] ?></span></td>
                            <td class="col-hide-mobile"><span class="file-type-pill"><?= htmlspecialchars(strtoupper($file['extension'] ?: 'FILE')) ?></span></td>
                            <td class="col-hide-mobile"><span class="file-date-text"><?= $file['modified_formatted'] ?></span></td>
                            <td>
                                <div class="table-actions-group">
                                    <?php if ($file['is_previewable']): ?>
                                    <button type="button" class="action-btn-circle" title="Preview / Open">
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?= $downloadUrl ?>" class="action-btn-circle action-btn-download action-download" download="<?= htmlspecialchars($file['name']) ?>" title="Download File">
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    </a>

                                    <button type="button" class="action-btn-circle action-copy" data-url="<?= htmlspecialchars($viewUrl) ?>" title="Copy Direct Link">
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Grid View -->
            <div id="s3GridView" class="s3-grid-container" style="display: none;">
                <!-- Parent Dir in Grid -->
                <?php if (!empty($currentSubPath)): ?>
                <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?><?= $parentPath ? '&path=' . urlencode($parentPath) : '' ?>" class="grid-card">
                    <div class="grid-icon">⤴️</div>
                    <div class="grid-title">.. (Parent)</div>
                    <div class="grid-meta">Directory</div>
                </a>
                <?php endif; ?>

                <!-- Folders in Grid -->
                <?php foreach ($folders as $folder): ?>
                <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?>&path=<?= urlencode($folder['path']) ?>" class="grid-card">
                    <div class="grid-icon">📁</div>
                    <div class="grid-title" title="<?= htmlspecialchars($folder['name']) ?>"><?= htmlspecialchars($folder['name']) ?></div>
                    <div class="grid-meta"><?= $folder['items_count'] ?> item(s) • FOLDER</div>
                </a>
                <?php endforeach; ?>

                <!-- Files in Grid -->
                <?php foreach ($files as $file): 
                    $fileJson = htmlspecialchars(json_encode($file), ENT_QUOTES, 'UTF-8');
                    $icon = '📄';
                    if ($file['category'] === 'image') $icon = '🖼️';
                    elseif ($file['category'] === 'video') $icon = '🎬';
                    elseif ($file['category'] === 'audio') $icon = '🎵';
                    elseif ($file['category'] === 'code') $icon = '💻';
                    elseif ($file['extension'] === 'pdf') $icon = '📑';
                    elseif ($file['category'] === 'archive') $icon = '📦';
                ?>
                <div class="grid-card" data-preview-item='<?= $fileJson ?>' data-unique-id="<?= htmlspecialchars($uniqueId) ?>" style="cursor: pointer;">
                    <div class="grid-icon"><?= $icon ?></div>
                    <div class="grid-title" title="<?= htmlspecialchars($file['name']) ?>"><?= htmlspecialchars($file['name']) ?></div>
                    <div class="grid-meta">
                        <?= $file['size_formatted'] ?> • <?= strtoupper($file['extension'] ?: 'FILE') ?>
                        <?= !empty($file['download_count']) ? ' • ⬇️ ' . (int)$file['download_count'] : '' ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
