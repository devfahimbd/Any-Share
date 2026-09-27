<?php
/**
 * Any Share - Home & Upload Hub
 */
$pageTitle = 'Home & Instant Upload';
$activeNav = 'home';
$extraScripts = ['assets/js/uploader.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-pill">
            <span class="hero-pill-dot"></span>
            <span>No Login Required • Completely Anonymous • Unindexed Storage</span>
        </div>
        <h1 class="hero-title">Upload & Share Anything with <br><span class="accent">Your Own Secret ID</span></h1>
        <p class="hero-subtitle">
            Upload files, full folders, or ZIP archives in seconds. Assign a unique Secret Key to keep your files private. Only people with your key can search, explore, and download.
        </p>
    </section>

    <!-- Search Section (Primary Access Point) -->
    <section class="search-card-wrapper" id="searchSection">
        <form id="secretSearchForm" class="search-box">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="text" id="secretSearchInput" class="search-input" placeholder="Enter Secret ID to access files (e.g. my-project-files)..." autocomplete="off" spellcheck="false" required>
            <button type="submit" class="search-btn">
                <span>Browse Files</span>
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </form>

        <div class="search-hints">
            <div class="recent-ids">
                <span>Recent:</span>
                <div id="recentKeysList" class="recent-ids"></div>
            </div>
            <span class="text-dim">Files are unindexed & private</span>
        </div>
    </section>

    <!-- Upload Hub -->
    <section class="upload-hub-card glass-card" id="uploadSection">
        <div class="card-header-bar">
            <div class="card-title-group">
                <h2>Cloud Upload Center</h2>
                <p>Preserve folder structures, bulk upload files, or unpack ZIP archives.</p>
            </div>
            <div class="upload-badge">
                <span class="text-dim" style="font-size: 0.8rem;">Max Size: <?= htmlspecialchars(get_config('storage', 'max_file_size_mb', 1024)) ?> MB</span>
            </div>
        </div>

        <form id="uploadForm">
            <!-- Secret ID Definition Bar -->
            <div class="secret-id-bar">
                <label for="secretIdInput" class="secret-id-label">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-6 6 2-2m-8 8 2-2m-4 4 2-2"/><circle cx="7.5" cy="15.5" r="5.5"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
                    <span>Secret Key (ID):</span>
                </label>
                <div class="secret-id-field-wrap">
                    <input type="text" id="secretIdInput" name="unique_id" class="input-styled" placeholder="Enter custom secret key or generate..." required pattern="^[a-zA-Z0-9_\-\.]{3,64}$" title="3-64 characters: letters, numbers, dash, underscore, dot">
                    <button type="button" id="generateIdBtn" class="btn-sm-random" title="Generate a random secure key">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="1.5"/><circle cx="15" cy="15" r="1.5"/></svg>
                        <span>🎲 Random Key</span>
                    </button>
                </div>
            </div>

            <!-- Upload Type Tabs -->
            <div class="upload-tabs">
                <button type="button" class="upload-tab active" data-mode="files">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Individual Files</span>
                </button>
                <button type="button" class="upload-tab" data-mode="folder">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                    <span>Entire Folder Tree</span>
                </button>
                <button type="button" class="upload-tab" data-mode="zip">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v20"/><path d="M14 2v20"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/></svg>
                    <span>ZIP Archive</span>
                </button>
            </div>

            <!-- Hidden Inputs -->
            <input type="file" id="fileInput" multiple style="display: none;">
            <input type="file" id="folderInput" webkitdirectory directory multiple style="display: none;">
            <input type="file" id="zipInput" accept=".zip" style="display: none;">

            <!-- Drop Zone -->
            <div class="dropzone-container">
                <div id="dropzone" class="dropzone">
                    <div class="dropzone-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/>
                            <path d="M12 12v9"/>
                            <path d="m16 16-4-4-4 4"/>
                        </svg>
                    </div>
                    <h3 id="dropzoneTitle" class="dropzone-title">Drag & drop files here, or browse</h3>
                    <p id="dropzoneHint" class="dropzone-hint">Upload any document, image, video, audio, or code. Full directory trees are preserved.</p>
                    <div class="dropzone-cta-btns">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('fileInput').click()">
                            <span>Choose Files</span>
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('folderInput').click()">
                            <span>Choose Folder</span>
                        </button>
                    </div>
                </div>

                <!-- Staged Files Preview -->
                <div id="stagedSection" class="staged-section" style="display: none;">
                    <div class="staged-header">
                        <span id="stagedCount" class="staged-title">0 item(s) selected</span>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <label style="font-size: 0.8rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                                <input type="checkbox" id="extractZipCheckbox" checked>
                                <span>Auto-extract ZIP archives</span>
                            </label>
                            <button type="button" id="clearStagedBtn" class="btn btn-sm btn-secondary" style="color: var(--rose);">Clear All</button>
                        </div>
                    </div>
                    <div id="stagedList" class="stagedList"></div>

                    <div style="margin-top: 1.25rem; display: flex; justify-content: flex-end;">
                        <button type="submit" id="uploadSubmitBtn" class="btn btn-primary" style="padding: 0.85rem 2rem;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Upload Now to Storage</span>
                        </button>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div id="progressCard" class="progress-card" style="display: none;">
                    <div class="progress-info">
                        <span id="progressStatus">Uploading...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress-track">
                        <div id="progressFill" class="progress-fill"></div>
                    </div>
                </div>

                <!-- Success Box -->
                <div id="uploadSuccessPanel" class="upload-success-panel" style="display: none;">
                    <div class="success-icon-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <h3 style="font-size: 1.3rem; margin-bottom: 0.25rem;">Upload Successful!</h3>
                    <p class="text-dim" style="font-size: 0.85rem;">Save your Secret ID. You will need it to search and access your files anytime.</p>
                    
                    <div class="success-key-box">
                        <span id="successKeyText" class="success-key-text">---</span>
                        <button type="button" id="copySuccessKeyBtn" class="btn btn-sm btn-secondary">Copy Key</button>
                    </div>

                    <div style="display: flex; justify-content: center; gap: 0.75rem; margin-top: 1.25rem; flex-wrap: wrap;">
                        <a id="openExplorerBtn" href="#" class="btn btn-emerald">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                            <span>Open in S3 Explorer</span>
                        </a>
                        <button type="button" id="copySuccessLinkBtn" class="btn btn-secondary">
                            <span>Copy Explorer Link</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <!-- Platform Highlights Section -->
    <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-top: 3.5rem;">
        <div class="glass-card" style="padding: 1.75rem;">
            <div style="font-size: 2rem; margin-bottom: 0.75rem;">🔒</div>
            <h3 style="font-size: 1.1rem; margin-bottom: 0.4rem;">Zero Login & Private</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                No registration, password, or cookies required. All files are mapped to secret IDs and strictly excluded from public directory indexing.
            </p>
        </div>
        <div class="glass-card" style="padding: 1.75rem;">
            <div style="font-size: 2rem; margin-bottom: 0.75rem;">📂</div>
            <h3 style="font-size: 1.1rem; margin-bottom: 0.4rem;">S3-Style Folder Trees</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Upload nested folders and subfolders. Our S3 Browser interface allows smooth breadcrumb navigation through directories and deep trees.
            </p>
        </div>
        <div class="glass-card" style="padding: 1.75rem;">
            <div style="font-size: 2rem; margin-bottom: 0.75rem;">⚡</div>
            <h3 style="font-size: 1.1rem; margin-bottom: 0.4rem;">Instant Previews & ZIP Export</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem;">
                Preview images, media, code, and PDFs directly in your browser. Download individual files or bundle everything into a single ZIP archive on demand.
            </p>
        </div>
    </section>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
