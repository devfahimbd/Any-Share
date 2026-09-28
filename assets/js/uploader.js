/**
 * Any Share - Unified Uploader Engine
 * Supports unified file/folder drag-and-drop & direct text note uploads
 */

(function () {
    'use strict';

    let stagedFiles = []; // Array of { file: File, relativePath: string }

    // DOM Elements
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('fileInput');
    const folderInput = document.getElementById('folderInput');
    const secretIdInput = document.getElementById('secretIdInput');
    const generateIdBtn = document.getElementById('generateIdBtn');
    
    // Tabs
    const tabFilesBtn = document.getElementById('tabFilesBtn');
    const tabTextBtn = document.getElementById('tabTextBtn');
    const filesUploadTab = document.getElementById('filesUploadTab');
    const textUploadTab = document.getElementById('textUploadTab');

    // Text Upload Elements
    const textFilenameInput = document.getElementById('textFilenameInput');
    const textContentArea = document.getElementById('textContentArea');
    const uploadTextSubmitBtn = document.getElementById('uploadTextSubmitBtn');

    // Staged Files Elements
    const stagedSection = document.getElementById('stagedSection');
    const stagedList = document.getElementById('stagedList');
    const stagedCount = document.getElementById('stagedCount');
    const clearStagedBtn = document.getElementById('clearStagedBtn');
    const uploadSubmitBtn = document.getElementById('uploadSubmitBtn');

    // Progress & Success
    const progressCard = document.getElementById('progressCard');
    const progressFill = document.getElementById('progressFill');
    const progressPercent = document.getElementById('progressPercent');
    const progressStatus = document.getElementById('progressStatus');
    const successPanel = document.getElementById('uploadSuccessPanel');
    const successKeyText = document.getElementById('successKeyText');
    const copySuccessKeyBtn = document.getElementById('copySuccessKeyBtn');
    const openExplorerBtn = document.getElementById('openExplorerBtn');

    // Generate random secret key: e.g. as_k8m2p9
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

    // Tab Switching (Files vs Text)
    if (tabFilesBtn && tabTextBtn) {
        tabFilesBtn.addEventListener('click', () => {
            tabFilesBtn.classList.add('active');
            tabTextBtn.classList.remove('active');
            filesUploadTab.style.display = 'block';
            textUploadTab.style.display = 'none';
        });

        tabTextBtn.addEventListener('click', () => {
            tabTextBtn.classList.add('active');
            tabFilesBtn.classList.remove('active');
            textUploadTab.style.display = 'block';
            filesUploadTab.style.display = 'none';
        });
    }

    // Dropzone Events (Files & Folders)
    if (dropzone) {
        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('button') || e.target.closest('input')) return;
            fileInput.click();
        });

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

    // Handle Folders via Drag and Drop
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

    // Input handlers
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

    function handleFileList(fileList) {
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

    // Render Staged Files
    function renderStagedFiles() {
        if (!stagedSection || !stagedList) return;

        if (stagedFiles.length === 0) {
            stagedSection.style.display = 'none';
            if (uploadSubmitBtn) uploadSubmitBtn.disabled = true;
            return;
        }

        stagedSection.style.display = 'block';
        if (uploadSubmitBtn) uploadSubmitBtn.disabled = false;
        stagedCount.textContent = `${stagedFiles.length} item(s) ready to upload`;
        stagedList.innerHTML = '';

        stagedFiles.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'staged-item';
            div.innerHTML = `
                <div class="staged-file-info">
                    <span>📄</span>
                    <div>
                        <div class="staged-file-name" title="${item.relativePath}">${item.relativePath}</div>
                        <div class="staged-file-size">${formatBytes(item.file.size)}</div>
                    </div>
                </div>
                <button type="button" class="staged-file-remove" title="Remove" data-index="${index}">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
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

    // Submit File Uploads
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
                secretId = generateRandomSecretId();
                if (secretIdInput) secretIdInput.value = secretId;
            }

            const formData = new FormData();
            formData.append('unique_id', secretId);
            formData.append('extract_zip', document.getElementById('extractZipCheckbox')?.checked ? '1' : '0');

            stagedFiles.forEach((item) => {
                formData.append('files[]', item.file);
                formData.append('relative_paths[]', item.relativePath);
            });

            performUpload(formData, secretId);
        });
    }

    // Submit Text Upload
    if (uploadTextSubmitBtn) {
        uploadTextSubmitBtn.addEventListener('click', () => {
            const content = textContentArea ? textContentArea.value.trim() : '';
            if (!content) {
                showToast('Please enter some text or code to upload.', 'error');
                if (textContentArea) textContentArea.focus();
                return;
            }

            let secretId = (secretIdInput ? secretIdInput.value.trim() : '');
            if (!secretId) {
                secretId = generateRandomSecretId();
                if (secretIdInput) secretIdInput.value = secretId;
            }

            const filename = (textFilenameInput && textFilenameInput.value.trim()) ? textFilenameInput.value.trim() : 'note.txt';

            const formData = new FormData();
            formData.append('unique_id', secretId);
            formData.append('text_content', content);
            formData.append('text_filename', filename);

            performUpload(formData, secretId, true);
        });
    }

    // Unified Upload Function
    function performUpload(formData, secretId, isText = false) {
        progressCard.style.display = 'block';
        progressFill.style.width = '0%';
        progressPercent.textContent = '0%';
        progressStatus.textContent = isText ? 'Saving text snippet...' : 'Uploading files to storage...';
        if (uploadSubmitBtn) uploadSubmitBtn.disabled = true;
        if (uploadTextSubmitBtn) uploadTextSubmitBtn.disabled = true;
        if (successPanel) successPanel.style.display = 'none';

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
            if (uploadSubmitBtn) uploadSubmitBtn.disabled = false;
            if (uploadTextSubmitBtn) uploadTextSubmitBtn.disabled = false;

            try {
                const res = JSON.parse(xhr.responseText);
                if (xhr.status >= 200 && xhr.status < 300 && res.success) {
                    progressFill.style.width = '100%';
                    progressPercent.textContent = '100%';
                    progressStatus.textContent = 'Upload complete! Storage updated.';
                    showToast(res.message || 'Uploaded successfully!', 'success');

                    // Save to recent keys
                    saveRecentSecretId(secretId);

                    // Show success box
                    if (successPanel) {
                        successPanel.style.display = 'block';
                        successKeyText.textContent = secretId;
                        openExplorerBtn.href = res.browse_url;

                        copySuccessKeyBtn.onclick = () => {
                            copyToClipboard(secretId, 'Secret Key copied!');
                        };

                        document.getElementById('copySuccessLinkBtn').onclick = () => {
                            copyToClipboard(res.browse_url, 'Direct Explorer Link copied!');
                        };
                    }

                    // Reset form fields
                    stagedFiles = [];
                    renderStagedFiles();
                    if (textContentArea) textContentArea.value = '';
                } else {
                    progressStatus.textContent = 'Upload failed.';
                    showToast(res.error || 'Upload error occurred.', 'error');
                }
            } catch (err) {
                progressStatus.textContent = 'Server response error.';
                showToast('Server returned an unexpected response.', 'error');
            }
        };

        xhr.onerror = () => {
            if (uploadSubmitBtn) uploadSubmitBtn.disabled = false;
            if (uploadTextSubmitBtn) uploadTextSubmitBtn.disabled = false;
            progressStatus.textContent = 'Network error.';
            showToast('Network error while uploading.', 'error');
        };

        xhr.send(formData);
    }
})();
