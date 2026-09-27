/**
 * Any Share - Uploader Engine
 * Handles drag-and-drop, full folder directory uploads (webkitdirectory), ZIP extraction, and progress
 */

(function () {
    'use strict';

    let stagedFiles = []; // Array of { file: File, relativePath: string }
    let currentMode = 'files'; // 'files' | 'folder' | 'zip'

    // DOM Elements
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const folderInput = document.getElementById('folderInput');
    const zipInput = document.getElementById('zipInput');
    const secretIdInput = document.getElementById('secretIdInput');
    const generateIdBtn = document.getElementById('generateIdBtn');
    const stagedSection = document.getElementById('stagedSection');
    const stagedList = document.getElementById('stagedList');
    const stagedCount = document.getElementById('stagedCount');
    const clearStagedBtn = document.getElementById('clearStagedBtn');
    const uploadSubmitBtn = document.getElementById('uploadSubmitBtn');
    const progressCard = document.getElementById('progressCard');
    const progressFill = document.getElementById('progressFill');
    const progressPercent = document.getElementById('progressPercent');
    const progressStatus = document.getElementById('progressStatus');
    const successPanel = document.getElementById('uploadSuccessPanel');
    const successKeyText = document.getElementById('successKeyText');
    const copySuccessKeyBtn = document.getElementById('copySuccessKeyBtn');
    const openExplorerBtn = document.getElementById('openExplorerBtn');
    const tabBtns = document.querySelectorAll('.upload-tab');

    // Generate random secret key: e.g. as_x7a9k2
    function generateRandomSecretId() {
        const chars = 'abcdefghjkmnpqrstuvwxyz23456789';
        let res = 'as_';
        for (let i = 0; i < 6; i++) {
            res += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return res;
    }

    if (generateIdBtn && secretIdInput) {
        generateIdBtn.addEventListener('click', () => {
            secretIdInput.value = generateRandomSecretId();
            secretIdInput.focus();
            showToast('Random Secret ID generated!', 'info');
        });
    }

    // Tab switching (Files, Folder, ZIP)
    tabBtns.forEach(tab => {
        tab.addEventListener('click', () => {
            tabBtns.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentMode = tab.dataset.mode;

            const title = document.getElementById('dropzoneTitle');
            const hint = document.getElementById('dropzoneHint');

            if (currentMode === 'files') {
                title.textContent = 'Drag & drop files here, or browse';
                hint.textContent = 'Upload any document, image, video, audio or code file. Multiple files supported.';
            } else if (currentMode === 'folder') {
                title.textContent = 'Upload an entire Folder';
                hint.textContent = 'Directory structure and subfolders are automatically preserved.';
            } else if (currentMode === 'zip') {
                title.textContent = 'Upload a ZIP Archive';
                hint.textContent = 'ZIP archives can be browsed as-is or auto-extracted into an S3 folder tree.';
            }
        });
    });

    // Dropzone click triggers corresponding input
    if (dropzone) {
        dropzone.addEventListener('click', (e) => {
            // Don't trigger if clicked on child button
            if (e.target.closest('button') || e.target.closest('input')) return;

            if (currentMode === 'files') {
                fileInput.click();
            } else if (currentMode === 'folder') {
                folderInput.click();
            } else if (currentMode === 'zip') {
                zipInput.click();
            }
        });

        // Drag & Drop events
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            if (dt && dt.items) {
                handleDataTransferItems(dt.items);
            } else if (dt && dt.files) {
                handleFileList(dt.files);
            }
        });
    }

    // DataTransferItems handling (supports dragging folders in modern browsers)
    async function handleDataTransferItems(items) {
        const queue = [];
        for (let i = 0; i < items.length; i++) {
            const entry = items[i].webkitGetAsEntry ? items[i].webkitGetAsEntry() : null;
            if (entry) {
                queue.push(traverseFileTree(entry));
            }
        }
        await Promise.all(queue);
        renderStagedFiles();
    }

    async function traverseFileTree(item, path = '') {
        if (item.isFile) {
            return new Promise((resolve) => {
                item.file((file) => {
                    stagedFiles.push({
                        file: file,
                        relativePath: path ? `${path}/${file.name}` : file.name
                    });
                    resolve();
                });
            });
        } else if (item.isDirectory) {
            const dirReader = item.createReader();
            const entries = await readAllDirectoryEntries(dirReader);
            const promises = entries.map(entry => traverseFileTree(entry, path ? `${path}/${item.name}` : item.name));
            return Promise.all(promises);
        }
    }

    async function readAllDirectoryEntries(dirReader) {
        let entries = [];
        let readEntries = async () => {
            return new Promise((resolve, reject) => {
                dirReader.readEntries(results => {
                    if (results.length) {
                        entries = entries.concat(Array.from(results));
                        readEntries().then(resolve);
                    } else {
                        resolve(entries);
                    }
                }, reject);
            });
        };
        return await readEntries();
    }

    // Input change handlers
    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            handleFileList(e.target.files);
            fileInput.value = '';
        });
    }

    if (folderInput) {
        folderInput.addEventListener('change', (e) => {
            handleFileList(e.target.files, true);
            folderInput.value = '';
        });
    }

    if (zipInput) {
        zipInput.addEventListener('change', (e) => {
            handleFileList(e.target.files);
            zipInput.value = '';
        });
    }

    function handleFileList(fileList, isFolder = false) {
        for (let i = 0; i < fileList.length; i++) {
            const file = fileList[i];
            const relPath = file.webkitRelativePath || file.name;
            stagedFiles.push({
                file: file,
                relativePath: relPath
            });
        }
        renderStagedFiles();
    }

    // Render staged files list
    function renderStagedFiles() {
        if (!stagedSection || !stagedList) return;

        if (stagedFiles.length === 0) {
            stagedSection.style.display = 'none';
            if (uploadSubmitBtn) uploadSubmitBtn.disabled = true;
            return;
        }

        stagedSection.style.display = 'block';
        if (uploadSubmitBtn) uploadSubmitBtn.disabled = false;
        stagedCount.textContent = `${stagedFiles.length} item(s) selected`;
        stagedList.innerHTML = '';

        stagedFiles.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'staged-item';
            div.innerHTML = `
                <div class="staged-file-info">
                    <span style="font-size: 1.1rem;">📄</span>
                    <div>
                        <div class="staged-file-name" title="${item.relativePath}">${item.relativePath}</div>
                        <div class="staged-file-size">${formatBytes(item.file.size)}</div>
                    </div>
                </div>
                <button type="button" class="staged-file-remove" title="Remove file" data-index="${index}">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            `;

            div.querySelector('.staged-file-remove').addEventListener('click', (e) => {
                e.stopPropagation();
                stagedFiles.splice(index, 1);
                renderStagedFiles();
            });

            stagedList.appendChild(div);
        });
    }

    function formatBytes(bytes) {
        if (bytes <= 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    if (clearStagedBtn) {
        clearStagedBtn.addEventListener('click', () => {
            stagedFiles = [];
            renderStagedFiles();
        });
    }

    // Submit Upload Form
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (stagedFiles.length === 0) {
                showToast('Please select at least one file or folder to upload.', 'error');
                return;
            }

            let secretId = (secretIdInput ? secretIdInput.value.trim() : '');
            if (!secretId) {
                // Auto generate if empty
                secretId = generateRandomSecretId();
                if (secretIdInput) secretIdInput.value = secretId;
            }

            // Prepare FormData
            const formData = new FormData();
            formData.append('unique_id', secretId);
            formData.append('extract_zip', document.getElementById('extractZipCheckbox')?.checked ? '1' : '0');

            stagedFiles.forEach((item) => {
                formData.append('files[]', item.file);
                formData.append('relative_paths[]', item.relativePath);
            });

            // UI Progress state
            progressCard.style.display = 'block';
            progressFill.style.width = '0%';
            progressPercent.textContent = '0%';
            progressStatus.textContent = 'Uploading files to storage...';
            uploadSubmitBtn.disabled = true;
            if (successPanel) successPanel.style.display = 'none';

            // Send via XMLHttpRequest for upload progress tracking
            const xhr = new XMLHttpRequest();
            xhr.open('POST', `${window.ANY_SHARE.baseUrl}/api/upload.php`, true);

            xhr.upload.onprogress = (evt) => {
                if (evt.lengthComputable) {
                    const percent = Math.round((evt.loaded / evt.total) * 100);
                    progressFill.style.width = `${percent}%`;
                    progressPercent.textContent = `${percent}%`;
                    progressStatus.textContent = `Uploading: ${formatBytes(evt.loaded)} of ${formatBytes(evt.total)}`;
                }
            };

            xhr.onload = () => {
                uploadSubmitBtn.disabled = false;
                try {
                    const res = JSON.parse(xhr.responseText);
                    if (xhr.status >= 200 && xhr.status < 300 && res.success) {
                        progressFill.style.width = '100%';
                        progressPercent.textContent = '100%';
                        progressStatus.textContent = 'Upload complete! Storage updated.';
                        showToast(res.message || 'Files uploaded successfully!', 'success');

                        // Save in recent searches
                        saveRecentSecretId(secretId);

                        // Show success box
                        if (successPanel) {
                            successPanel.style.display = 'block';
                            successKeyText.textContent = secretId;
                            openExplorerBtn.href = res.browse_url;

                            copySuccessKeyBtn.onclick = () => {
                                copyToClipboard(secretId, 'Secret ID copied!');
                            };

                            document.getElementById('copySuccessLinkBtn').onclick = () => {
                                copyToClipboard(res.browse_url, 'S3 Browser link copied!');
                            };
                        }

                        // Clear staged files
                        stagedFiles = [];
                        renderStagedFiles();
                    } else {
                        progressStatus.textContent = 'Upload failed.';
                        showToast(res.error || 'Upload error occurred.', 'error');
                    }
                } catch (err) {
                    progressStatus.textContent = 'Failed to parse server response.';
                    showToast('Server returned an unexpected response.', 'error');
                }
            };

            xhr.onerror = () => {
                uploadSubmitBtn.disabled = false;
                progressStatus.textContent = 'Network error.';
                showToast('Network error while uploading.', 'error');
            };

            xhr.send(formData);
        });
    }
})();
