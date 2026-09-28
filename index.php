<?php
/**
 * Any Share - Clean White & Emerald Homepage
 * Direct, fast upload (Files / Text) and Secret Key Search
 */
$pageTitle = 'Upload & Share';
$extraScripts = ['assets/js/uploader.js'];

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Clean Search Bar -->
    <section class="search-card-wrapper" id="searchSection">
        <form id="secretSearchForm" class="search-box">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="text" id="secretSearchInput" class="search-input" placeholder="Enter Secret ID to access files..." autocomplete="off" spellcheck="false" required>
            <button type="submit" class="search-btn">
                <span>Browse</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
        </form>

        <div class="search-hints">
            <div class="recent-ids">
                <span>Recent:</span>
                <div id="recentKeysList" class="recent-ids"></div>
            </div>
            <span>Auto-expires after 30 mins</span>
        </div>
    </section>

    <!-- Upload Hub -->
    <section class="upload-hub-card">
        <form id="uploadForm">
            <!-- Secret Key Input -->
            <div class="secret-id-bar">
                <label for="secretIdInput" class="secret-id-label">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary);"><path d="m21 2-2 2m-6 6 2-2m-8 8 2-2m-4 4 2-2"/><circle cx="7.5" cy="15.5" r="5.5"/><path d="m15.5 7.5 3 3L22 7l-3-3"/></svg>
                    <span>Secret Key:</span>
                </label>
                <div class="secret-id-field-wrap">
                    <input type="text" id="secretIdInput" name="unique_id" class="input-styled" placeholder="Enter custom secret key or generate..." required pattern="^[a-zA-Z0-9_\-\.]{3,64}$" title="3-64 characters">
                    <button type="button" id="generateIdBtn" class="btn-sm-random" title="Generate random key">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="1.5"/><circle cx="15" cy="15" r="1.5"/></svg>
                        <span>🎲 Random Key</span>
                    </button>
                </div>
            </div>

            <!-- Two Clean Tabs: 1. File Upload, 2. Text Upload -->
            <div class="upload-tabs">
                <button type="button" class="upload-tab active" id="tabFilesBtn" data-tab="files">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                    <span>Upload Files / Folders</span>
                </button>
                <button type="button" class="upload-tab" id="tabTextBtn" data-tab="text">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span>Write / Paste Text</span>
                </button>
            </div>

            <!-- Tab 1: File Upload (Unified for files, folders, zips) -->
            <div id="filesUploadTab" class="dropzone-container">
                <input type="file" id="fileInput" multiple style="display: none;">
                <input type="file" id="folderInput" webkitdirectory directory multiple style="display: none;">

                <div id="dropzone" class="dropzone">
                    <div class="dropzone-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/>
                            <path d="M12 12v9"/>
                            <path d="m16 16-4-4-4 4"/>
                        </svg>
                    </div>
                    <h3 class="dropzone-title">Click or drag files / folders here</h3>
                    <p class="dropzone-hint">Any file type supported. Max <?= (int) get_config('storage', 'max_file_size_mb', 512) ?> MB.</p>
                    <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('fileInput').click()">Choose Files</button>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('folderInput').click()">Choose Folder</button>
                    </div>
                </div>

                <!-- Staged Files Preview -->
                <div id="stagedSection" class="staged-section" style="display: none;">
                    <div class="staged-header">
                        <span id="stagedCount" class="staged-title">0 item(s) selected</span>
                        <div style="display: flex; align-items: center; gap: 0.8rem;">
                            <label style="font-size: 0.78rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer;">
                                <input type="checkbox" id="extractZipCheckbox" checked>
                                <span>Unpack ZIP files</span>
                            </label>
                            <button type="button" id="clearStagedBtn" class="btn btn-sm btn-secondary" style="color: var(--rose);">Clear</button>
                        </div>
                    </div>
                    <div id="stagedList" class="staged-list"></div>

                    <div style="margin-top: 1.25rem; display: flex; justify-content: flex-end;">
                        <button type="submit" id="uploadSubmitBtn" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span>Upload Files</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Text / Note Upload -->
            <div id="textUploadTab" class="text-upload-container">
                <div class="text-meta-row">
                    <input type="text" id="textFilenameInput" class="text-filename-input" placeholder="File name (e.g. note.txt, code.py)" value="note.txt">
                    <span style="font-size: 0.78rem; color: var(--text-dim);">Saved under your Secret Key</span>
                </div>
                <textarea id="textContentArea" class="text-editor-area" placeholder="Write or paste your text, note, or code snippet here..."></textarea>
                
                <div style="margin-top: 1.25rem; display: flex; justify-content: flex-end;">
                    <button type="button" id="uploadTextSubmitBtn" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Save & Upload Text</span>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <h3 style="font-size: 1.25rem; margin-bottom: 0.2rem; color: var(--emerald-dark);">Uploaded Successfully!</h3>
                <p style="font-size: 0.8rem; color: var(--text-muted);">This upload will automatically self-destruct after <strong>30 minutes</strong>.</p>
                
                <div class="success-key-box">
                    <span id="successKeyText" class="success-key-text">---</span>
                    <button type="button" id="copySuccessKeyBtn" class="btn btn-sm btn-secondary">Copy Key</button>
                </div>

                <div style="display: flex; justify-content: center; gap: 0.6rem; margin-top: 1rem; flex-wrap: wrap;">
                    <a id="openExplorerBtn" href="#" class="btn btn-primary">Open in Explorer</a>
                    <button type="button" id="copySuccessLinkBtn" class="btn btn-secondary">Copy Link</button>
                </div>
            </div>
        </form>
    </section>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
