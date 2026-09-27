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
                <span class="footer-brand">Any<span class="accent">Share</span></span>
                <span class="footer-sep">•</span>
                <span class="footer-note">Private S3 Browser & Cloud File Sharing</span>
            </div>
            <div class="footer-right">
                <span class="footer-version">v<?= htmlspecialchars(get_config('app', 'app_version', '1.0.0')) ?></span>
                <span class="footer-sep">•</span>
                <a href="https://github.com/devfahimbd/Any-Share" target="_blank" rel="noopener" class="footer-link">GitHub Repository</a>
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
