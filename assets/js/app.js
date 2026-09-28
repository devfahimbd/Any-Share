/**
 * Any Share - Core Application Logic
 * Handles global notifications, recent history, search handler, and preview modal
 */

(function () {
    'use strict';

    // Toast notification utility
    window.showToast = function (message, type = 'info', duration = 3500) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let icon = 'ℹ️';
        if (type === 'success') icon = '✅';
        if (type === 'error') icon = '⚠️';

        toast.innerHTML = `<span>${icon}</span><span>${message}</span>`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    };

    // Copy to clipboard helper
    window.copyToClipboard = function (text, successMsg = 'Copied to clipboard!') {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showToast(successMsg, 'success');
            }).catch(() => fallbackCopy(text, successMsg));
        } else {
            fallbackCopy(text, successMsg);
        }
    };

    function fallbackCopy(text, successMsg) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            showToast(successMsg, 'success');
        } catch (err) {
            showToast('Failed to copy', 'error');
        }
        textArea.remove();
    }

    // Clear any previously saved recent keys from browser
    try {
        localStorage.removeItem('any_share_recent_keys');
    } catch (e) {}

    window.saveRecentSecretId = function () {};
    window.renderRecentKeys = function () {};

    // Initialize Search Bar
    function initSearch() {
        const form = document.getElementById('secretSearchForm');
        const input = document.getElementById('secretSearchInput');
        if (!form || !input) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const id = input.value.trim();
            if (!id) {
                showToast('Please enter a Secret ID.', 'error');
                input.focus();
                return;
            }

            const searchBtn = form.querySelector('.search-btn');
            const originalText = searchBtn.innerHTML;
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Searching...';

            fetch(`${window.ANY_SHARE.baseUrl}/api/search.php?id=${encodeURIComponent(id)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.browse_url;
                    } else {
                        showToast(data.error || 'No files found for this Secret ID.', 'error');
                        searchBtn.disabled = false;
                        searchBtn.innerHTML = originalText;
                    }
                })
                .catch(() => {
                    showToast('Connection error during search.', 'error');
                    searchBtn.disabled = false;
                    searchBtn.innerHTML = originalText;
                });
        });
    }

    // Universal File Preview Modal (S3-Style Viewer)
    window.openFilePreview = function (item, uniqueId) {
        const modal = document.getElementById('filePreviewModal');
        const nameEl = document.getElementById('modalFileName');
        const metaEl = document.getElementById('modalFileMeta');
        const iconEl = document.getElementById('modalFileIcon');
        const container = document.getElementById('modalDynamicContainer');
        const loader = document.getElementById('modalLoader');
        const downloadBtn = document.getElementById('modalDownloadBtn');
        const copyBtn = document.getElementById('modalCopyLinkBtn');

        if (!modal) return;

        // Reset state
        container.innerHTML = '';
        loader.style.display = 'flex';
        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');

        const fileName = item.name;
        const ext = (item.extension || '').toLowerCase();
        const category = item.category || 'file';
        const fileUrl = `${window.ANY_SHARE.baseUrl}/api/view.php?id=${encodeURIComponent(uniqueId)}&path=${encodeURIComponent(item.path)}`;
        const downloadUrl = `${window.ANY_SHARE.baseUrl}/api/download.php?id=${encodeURIComponent(uniqueId)}&path=${encodeURIComponent(item.path)}`;

        nameEl.textContent = fileName;
        metaEl.textContent = `${item.size_formatted || ''} • ${ext.toUpperCase() || 'FILE'} • ${item.modified_formatted || ''}`;
        
        let icon = '📄';
        if (category === 'image') icon = '🖼️';
        else if (category === 'video') icon = '🎬';
        else if (category === 'audio') icon = '🎵';
        else if (category === 'code') icon = '💻';
        else if (ext === 'pdf') icon = '📑';
        else if (category === 'archive') icon = '📦';
        iconEl.textContent = icon;

        downloadBtn.href = downloadUrl;
        downloadBtn.setAttribute('download', fileName);

        copyBtn.onclick = function () {
            copyToClipboard(fileUrl, 'Direct preview link copied!');
        };

        // Helper to safely escape HTML
        function escapeHtml(text) {
            if (typeof text !== 'string') text = String(text ?? '');
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        const isTextDoc = category === 'code' || [
            'txt', 'md', 'json', 'log', 'csv', 'tsv', 'xml', 'ini', 'sql',
            'sh', 'bat', 'conf', 'yml', 'yaml', 'env', 'htaccess', 'svg',
            'toml', 'dockerfile', 'c', 'cpp', 'py', 'java', 'js', 'css', 'html', 'php'
        ].includes(ext);

        // Render preview according to file type
        if (category === 'image') {
            const wrap = document.createElement('div');
            wrap.className = 'modal-media-wrap';
            const img = new Image();
            img.className = 'modal-preview-img';
            img.alt = fileName;
            img.onload = () => {
                loader.style.display = 'none';
                wrap.appendChild(img);
                container.appendChild(wrap);
            };
            img.onerror = () => {
                loader.style.display = 'none';
                container.innerHTML = '<div class="preview-fallback-box"><div style="font-size:2.5rem;margin-bottom:0.75rem;">🖼️</div><p class="text-dim">Unable to preview image file.</p></div>';
            };
            img.src = fileUrl;
        } else if (category === 'video') {
            loader.style.display = 'none';
            const wrap = document.createElement('div');
            wrap.className = 'modal-media-wrap';
            const video = document.createElement('video');
            video.className = 'modal-preview-video';
            video.controls = true;
            video.autoplay = false;
            video.src = fileUrl;
            wrap.appendChild(video);
            container.appendChild(wrap);
        } else if (category === 'audio') {
            loader.style.display = 'none';
            const wrap = document.createElement('div');
            wrap.className = 'modal-audio-wrap';
            wrap.innerHTML = `
                <div class="audio-player-card">
                    <div class="audio-art">🎵</div>
                    <div class="audio-info">
                        <div class="audio-name">${escapeHtml(fileName)}</div>
                        <div class="audio-meta">${escapeHtml(item.size_formatted || '')} • Audio Player</div>
                    </div>
                    <audio controls class="modal-preview-audio" src="${fileUrl}"></audio>
                </div>
            `;
            container.appendChild(wrap);
        } else if (ext === 'pdf') {
            loader.style.display = 'none';
            const iframe = document.createElement('iframe');
            iframe.className = 'modal-preview-iframe';
            iframe.src = fileUrl;
            container.appendChild(iframe);
        } else if (isTextDoc) {
            fetch(`${fileUrl}&raw=1`)
                .then(r => r.json())
                .then(res => {
                    loader.style.display = 'none';
                    if (res.success) {
                        const rawText = res.content || '';
                        const lines = rawText.split('\n');
                        const lineCount = lines.length;
                        const charCount = rawText.length;
                        const displayExt = (res.extension || ext || 'TXT').toUpperCase();

                        // Build line table
                        const rowsHtml = lines.map((line, idx) => {
                            return `<div class="code-line-row"><span class="code-line-num">${idx + 1}</span><span class="code-line-text">${escapeHtml(line) || '&nbsp;'}</span></div>`;
                        }).join('');

                        const viewerBox = document.createElement('div');
                        viewerBox.className = 'code-viewer-container';
                        viewerBox.innerHTML = `
                            <div class="code-viewer-toolbar">
                                <div class="code-viewer-info">
                                    <span class="code-badge">${escapeHtml(displayExt)}</span>
                                    <span class="code-stat">${lineCount} ${lineCount === 1 ? 'line' : 'lines'}</span>
                                    <span class="code-dot">•</span>
                                    <span class="code-stat">${charCount} characters</span>
                                    <span class="code-dot">•</span>
                                    <span class="code-stat">${escapeHtml(res.size_formatted || '')}</span>
                                </div>
                                <div class="code-viewer-actions">
                                    <button type="button" class="btn-code-copy" id="btnCopyCodeText" title="Copy entire text content">
                                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                        <span id="btnCopyCodeTextLabel">Copy Content</span>
                                    </button>
                                </div>
                            </div>
                            <div class="code-viewer-content">
                                <div class="code-editor-table">${rowsHtml}</div>
                            </div>
                        `;

                        container.appendChild(viewerBox);

                        const copyContentBtn = viewerBox.querySelector('#btnCopyCodeText');
                        const copyLabel = viewerBox.querySelector('#btnCopyCodeTextLabel');
                        if (copyContentBtn) {
                            copyContentBtn.addEventListener('click', () => {
                                copyToClipboard(rawText, 'File content copied to clipboard!');
                                if (copyLabel) {
                                    const orig = copyLabel.textContent;
                                    copyLabel.textContent = 'Copied!';
                                    setTimeout(() => { copyLabel.textContent = orig; }, 2000);
                                }
                            });
                        }
                    } else {
                        container.innerHTML = `<div class="preview-fallback-box"><div style="font-size:2.5rem;margin-bottom:0.75rem;">⚠️</div><p class="text-dim">${escapeHtml(res.error || 'Preview unavailable')}</p></div>`;
                    }
                })
                .catch(() => {
                    loader.style.display = 'none';
                    container.innerHTML = '<div class="preview-fallback-box"><div style="font-size:2.5rem;margin-bottom:0.75rem;">⚠️</div><p class="text-dim">Failed to load code/text preview.</p></div>';
                });
        } else {
            loader.style.display = 'none';
            container.innerHTML = `
                <div class="preview-fallback-box">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                    <h4 style="font-weight:700; color:var(--text-main);">No live preview available for this file type</h4>
                    <p class="text-dim" style="margin-top: 0.5rem; font-size: 0.85rem; max-width: 400px;">This binary or proprietary file format cannot be rendered directly in the browser. You can download it to view locally on your device.</p>
                    <a href="${downloadUrl}" class="btn btn-primary" style="margin-top: 1.25rem;" download>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Download File</span>
                    </a>
                </div>
            `;
        }
    };

    function initModal() {
        const modal = document.getElementById('filePreviewModal');
        const closeBtn = document.getElementById('modalCloseBtn');
        if (!modal) return;

        function closeModal() {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            const container = document.getElementById('modalDynamicContainer');
            if (container) container.innerHTML = '';
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('open')) {
                closeModal();
            }
        });
    }

    // Document Ready
    document.addEventListener('DOMContentLoaded', function () {
        initSearch();
        initModal();
    });
})();
