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
    <!-- Top Action & Details Bar -->
    <div class="s3-topbar">
        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <div class="s3-bucket-badge">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary-light);">
                    <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/>
                </svg>
                <span>Bucket:</span>
                <span class="s3-bucket-name"><?= htmlspecialchars($uniqueId) ?></span>
                <button type="button" class="btn btn-sm btn-icon" id="copyIdBtn" data-id="<?= htmlspecialchars($uniqueId) ?>" title="Copy Secret ID">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                </button>
            </div>
            <div style="font-size: 0.825rem; color: var(--text-dim);">
                <span>Total: <strong><?= $totalStats['files'] ?></strong> files (<?= $totalStats['size_formatted'] ?>)</span>
            </div>
        </div>

        <div class="s3-actions">
            <!-- View Mode Toggle -->
            <div style="display: flex; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 2px;">
                <button type="button" id="viewTableBtn" class="btn btn-sm btn-icon active" title="Table View" style="border:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </button>
                <button type="button" id="viewGridBtn" class="btn btn-sm btn-icon" title="Grid View" style="border:none;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>
                </button>
            </div>

            <!-- Download Whole Folder as ZIP -->
            <a href="<?= $zipDownloadUrl ?>" class="btn btn-sm btn-emerald" title="Download entire folder or bucket as ZIP">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span>Download as ZIP</span>
            </a>

            <!-- Copy Share Link -->
            <button type="button" id="copyShareUrlBtn" class="btn btn-sm btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                <span>Share Link</span>
            </button>

            <!-- Upload More to this bucket -->
            <a href="<?= $baseUrl ?>/?id=<?= urlencode($uniqueId) ?>#uploadSection" class="btn btn-sm btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload More</span>
            </a>
        </div>
    </div>

    <!-- S3 Breadcrumb Path Navigation -->
    <nav class="s3-breadcrumb-bar" aria-label="Directory Breadcrumbs">
        <span class="breadcrumb-root-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
        </span>

        <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php if ($index > 0): ?>
                <span class="breadcrumb-sep">/</span>
            <?php endif; ?>

            <?php if ($index === count($breadcrumbs) - 1): ?>
                <span class="breadcrumb-current"><?= htmlspecialchars($crumb['name']) ?></span>
            <?php else: ?>
                <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?><?= $crumb['path'] ? '&path=' . urlencode($crumb['path']) : '' ?>" class="breadcrumb-link">
                    <?= htmlspecialchars($crumb['name']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Explorer Glass Container -->
    <div class="glass-card">
        <?php if (!$hasItems): ?>
            <div class="s3-empty-state">
                <div class="s3-empty-icon">📂</div>
                <h3>This folder is empty</h3>
                <p style="margin-top: 0.5rem; font-size: 0.85rem;">No files or subdirectories found inside this path.</p>
                <div style="margin-top: 1.5rem;">
                    <a href="<?= $baseUrl ?>/?id=<?= urlencode($uniqueId) ?>#uploadSection" class="btn btn-primary">Upload Files Here</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Table View -->
            <div id="s3TableView" class="s3-table-wrap">
                <table class="s3-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Name</th>
                            <th style="width: 15%;">Size</th>
                            <th style="width: 15%;">Type</th>
                            <th style="width: 15%;">Last Modified</th>
                            <th style="width: 10%; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Up one directory row if in subpath -->
                        <?php if (!empty($currentSubPath)): 
                            $parentPath = dirname($currentSubPath);
                            if ($parentPath === '.' || $parentPath === '/') $parentPath = '';
                        ?>
                        <tr>
                            <td colspan="5">
                                <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?><?= $parentPath ? '&path=' . urlencode($parentPath) : '' ?>" class="s3-row-name" style="text-decoration: none; color: var(--text-muted); font-weight: 600;">
                                    <span class="s3-item-icon">⤴️</span>
                                    <span>.. (Parent Directory)</span>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>

                        <!-- Subfolders -->
                        <?php foreach ($folders as $folder): ?>
                        <tr>
                            <td>
                                <a href="<?= $baseUrl ?>/browse.php?id=<?= urlencode($uniqueId) ?>&path=<?= urlencode($folder['path']) ?>" class="s3-row-name">
                                    <span class="s3-item-icon">📁</span>
                                    <span class="s3-link-name"><?= htmlspecialchars($folder['name']) ?></span>
                                </a>
                            </td>
                            <td style="color: var(--text-dim);"><?= $folder['items_count'] ?> item(s)</td>
                            <td style="color: var(--text-dim); text-transform: uppercase;">Folder</td>
                            <td style="color: var(--text-dim);"><?= $folder['modified_formatted'] ?></td>
                            <td>
                                <div class="s3-row-actions">
                                    <a href="<?= $baseUrl ?>/api/download.php?id=<?= urlencode($uniqueId) ?>&path=<?= urlencode($folder['path']) ?>&zip=1" class="btn btn-sm btn-icon" title="Download Folder as ZIP">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
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
                            
                            $icon = '📄';
                            if ($file['category'] === 'image') $icon = '🖼️';
                            elseif ($file['category'] === 'video') $icon = '🎬';
                            elseif ($file['category'] === 'audio') $icon = '🎵';
                            elseif ($file['category'] === 'code') $icon = '💻';
                            elseif ($file['extension'] === 'pdf') $icon = '📑';
                            elseif ($file['category'] === 'archive') $icon = '📦';
                        ?>
                        <tr data-preview-item='<?= $fileJson ?>' data-unique-id="<?= htmlspecialchars($uniqueId) ?>" style="cursor: pointer;">
                            <td>
                                <div class="s3-row-name">
                                    <span class="s3-item-icon"><?= $icon ?></span>
                                    <span class="s3-link-name"><?= htmlspecialchars($file['name']) ?></span>
                                </div>
                            </td>
                            <td style="font-family: var(--font-mono); font-size: 0.825rem;"><?= $file['size_formatted'] ?></td>
                            <td style="color: var(--text-dim); text-transform: uppercase; font-size: 0.75rem;"><?= htmlspecialchars($file['extension'] ?: 'File') ?></td>
                            <td style="color: var(--text-dim); font-size: 0.8rem;"><?= $file['modified_formatted'] ?></td>
                            <td>
                                <div class="s3-row-actions">
                                    <?php if ($file['is_previewable']): ?>
                                    <button type="button" class="btn btn-sm btn-icon" title="Preview / Open">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?= $downloadUrl ?>" class="btn btn-sm btn-icon action-download" download="<?= htmlspecialchars($file['name']) ?>" title="Download File">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    </a>

                                    <button type="button" class="btn btn-sm btn-icon action-copy" data-url="<?= htmlspecialchars($viewUrl) ?>" title="Copy Link">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
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
                    <div class="grid-meta"><?= $folder['items_count'] ?> item(s)</div>
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
                    <div class="grid-meta"><?= $file['size_formatted'] ?> • <?= strtoupper($file['extension'] ?: 'FILE') ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
