    </main>

    <!-- Global File Preview Modal (S3-Style Viewer) -->
    <div id="filePreviewModal" class="modal-backdrop" aria-hidden="true" role="dialog">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title-group">
                    <span id="modalFileIcon" class="modal-file-icon">📄</span>
                    <div>
                        <h3 id="modalFileName" class="modal-file-name">filename.ext</h3>
                        <span id="modalFileMeta" class="modal-file-meta">0 KB • Unknown Type</span>
                    </div>
                </div>
                <div class="modal-actions">
                    <a id="modalDownloadBtn" href="#" class="btn btn-sm btn-primary" download>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Download</span>
                    </a>
                    <button type="button" class="btn btn-sm btn-icon" id="modalCopyLinkBtn" title="Copy Direct View Link">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                    </button>
                    <button type="button" class="btn btn-sm btn-icon modal-close" id="modalCloseBtn" aria-label="Close modal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>

            <div class="modal-body" id="modalBody">
                <div class="modal-loader" id="modalLoader">
                    <div class="spinner"></div>
                    <span>Loading preview...</span>
                </div>
                <!-- Dynamic Content (Img / Video / Audio / PDF / Code) injected here -->
                <div id="modalDynamicContainer" class="modal-dynamic-container"></div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container" aria-live="polite"></div>

    <footer class="app-footer">
        <div class="container footer-inner">
            <div class="footer-left">
                <span class="footer-brand">
                    <img src="<?= $baseUrl ?>/assets/img/logo.png" alt="Any Share" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;margin-right:6px;filter:drop-shadow(0 1px 3px rgba(16,185,129,0.3));">
                    Any<span class="accent">Share</span>
                </span>
                <span class="footer-sep">•</span>
                <span class="footer-note">Private S3 Browser & Cloud File Sharing</span>
            </div>
            <div class="footer-right">
                <span class="footer-version">v<?= htmlspecialchars(get_config('app', 'app_version', '1.1.0')) ?></span>
                <span class="footer-sep">•</span>
                <a href="https://github.com/devfahimbd/Any-Share" target="_blank" rel="noopener" class="footer-github-link" title="GitHub Repository" aria-label="GitHub Repository">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                </a>
            </div>
        </div>
    </footer>

    <script src="<?= $baseUrl ?>/assets/js/app.js"></script>
    <?php if (!empty($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?= $baseUrl ?>/<?= htmlspecialchars($script) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
