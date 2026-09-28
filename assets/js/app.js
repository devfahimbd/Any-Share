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

        // Render preview according to file type
        if (category === 'image') {
            const img = new Image();
            img.className = 'modal-preview-img';
            img.alt = fileName;
            img.onload = () => {
                loader.style.display = 'none';
                container.appendChild(img);
            };
            img.onerror = () => {
                loader.style.display = 'none';
                container.innerHTML = '<div class="text-dim">Unable to preview image.</div>';
            };
            img.src = fileUrl;
        } else if (category === 'video') {
            loader.style.display = 'none';
            const video = document.createElement('video');
            video.className = 'modal-preview-video';
            video.controls = true;
            video.autoplay = false;
            video.src = fileUrl;
            container.appendChild(video);
        } else if (category === 'audio') {
            loader.style.display = 'none';
            const audio = document.createElement('audio');
            audio.className = 'modal-preview-audio';
            audio.controls = true;
            audio.autoplay = false;
            audio.src = fileUrl;
            container.appendChild(audio);
        } else if (ext === 'pdf') {
            loader.style.display = 'none';
            const iframe = document.createElement('iframe');
            iframe.className = 'modal-preview-iframe';
            iframe.src = fileUrl;
            container.appendChild(iframe);
        } else if (category === 'code' || ['txt', 'md', 'json', 'log', 'csv', 'xml'].includes(ext)) {
            fetch(`${fileUrl}&raw=1`)
                .then(r => r.json())
                .then(res => {
                    loader.style.display = 'none';
                    if (res.success) {
                        const pre = document.createElement('pre');
                        pre.className = 'modal-preview-code';
                        const code = document.createElement('code');
                        code.textContent = res.content;
                        pre.appendChild(code);
                        container.appendChild(pre);
                    } else {
                        container.innerHTML = `<div class="text-dim">${res.error || 'Preview unavailable'}</div>`;
                    }
                })
                .catch(() => {
                    loader.style.display = 'none';
                    container.innerHTML = '<div class="text-dim">Failed to load code preview.</div>';
                });
        } else {
            loader.style.display = 'none';
            container.innerHTML = `
                <div style="text-align:center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                    <h4>No live preview available for this file type</h4>
                    <p class="text-dim" style="margin-top: 0.5rem; font-size: 0.85rem;">You can download it to view locally on your device.</p>
                    <a href="${downloadUrl}" class="btn btn-primary" style="margin-top: 1.25rem;" download>Download File</a>
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
