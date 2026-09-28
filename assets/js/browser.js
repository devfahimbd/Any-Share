/**
 * Any Share - S3 Browser Script
 * Interactive explorer view, breadcrumb navigation, table/grid view toggle, and actions
 */

(function () {
    'use strict';

    const viewModeKey = 'any_share_view_mode';
    const tableView = document.getElementById('s3TableView');
    const gridView = document.getElementById('s3GridView');
    const tableBtn = document.getElementById('viewTableBtn');
    const gridBtn = document.getElementById('viewGridBtn');

    // Switch view mode
    function setViewMode(mode) {
        localStorage.setItem(viewModeKey, mode);
        if (mode === 'grid') {
            if (tableView) tableView.style.display = 'none';
            if (gridView) gridView.style.display = 'grid';
            if (gridBtn) gridBtn.classList.add('active');
            if (tableBtn) tableBtn.classList.remove('active');
        } else {
            if (tableView) tableView.style.display = 'block';
            if (gridView) gridView.style.display = 'none';
            if (tableBtn) tableBtn.classList.add('active');
            if (gridBtn) gridBtn.classList.remove('active');
        }
    }

    if (tableBtn && gridBtn) {
        tableBtn.addEventListener('click', () => setViewMode('table'));
        gridBtn.addEventListener('click', () => setViewMode('grid'));

        const savedMode = localStorage.getItem(viewModeKey) || 'table';
        setViewMode(savedMode);
    }

    // Attach row/card preview handlers
    document.querySelectorAll('[data-preview-item]').forEach(el => {
        el.addEventListener('click', (e) => {
            // If download or copy clicked, don't open preview
            if (e.target.closest('.action-download') || e.target.closest('.action-copy')) return;

            try {
                const itemData = JSON.parse(el.getAttribute('data-preview-item'));
                const uniqueId = el.getAttribute('data-unique-id');
                if (window.openFilePreview) {
                    window.openFilePreview(itemData, uniqueId);
                }
            } catch (err) {}
        });
    });

    // Copy Share URL button
    const copyShareUrlBtn = document.getElementById('copyShareUrlBtn');
    if (copyShareUrlBtn) {
        copyShareUrlBtn.addEventListener('click', () => {
            copyToClipboard(window.location.href, 'Explorer link copied to clipboard!');
        });
    }

    // Copy Secret ID button
    const copyIdBtn = document.getElementById('copyIdBtn');
    if (copyIdBtn) {
        copyIdBtn.addEventListener('click', () => {
            const id = copyIdBtn.getAttribute('data-id');
            if (id) {
                copyToClipboard(id, 'Secret ID copied!');
            }
        });
    }

    // Copy Individual File Link buttons
    document.querySelectorAll('.action-copy').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const url = btn.getAttribute('data-url');
            if (url) {
                copyToClipboard(url, 'Direct link copied!');
            }
        });
    });

    // Live Expiry Countdown Timer
    const countdownEl = document.getElementById('bucketCountdown');
    if (countdownEl) {
        let remainingSeconds = parseInt(countdownEl.getAttribute('data-seconds'), 10) || 1800;

        function updateTimer() {
            if (remainingSeconds <= 0) {
                countdownEl.textContent = 'Expired';
                countdownEl.style.color = '#ef4444';
                if (window.showToast) {
                    showToast('This share has expired (30m limit).', 'error');
                }
                clearInterval(timerInterval);
                return;
            }

            const mins = Math.floor(remainingSeconds / 60);
            const secs = remainingSeconds % 60;
            countdownEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
            remainingSeconds--;
        }

        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);
    }
})();
