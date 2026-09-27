# Any-Share 🚀
> **Private, Instant & Anonymous S3-Style Cloud File Sharing & Directory Explorer**

Any Share is a lightweight, zero-dependency, self-hosted cloud file-sharing platform written in pure PHP. It allows users to upload files, full directory trees, or ZIP archives without requiring any user registration or login. Each upload is mapped to a private **Unique Secret ID (Access Key)**. 

Uploads are **strictly unindexed**, meaning files cannot be discovered or listed publicly. Anyone with the valid Secret Key can search and explore the files through a rich **S3-style file browser** interface with inline previews and on-the-fly ZIP downloads.

---

## 🌟 Key Features

- **🔒 Zero Login & 100% Anonymous**: No accounts, passwords, or cookies required.
- **🛡️ Private & Unindexed Storage**: Buckets are hidden from public indexing; accessible only via their specific Secret ID.
- **📁 Full Folder & Directory Tree Uploads**: Preserve nested directories and subfolders using modern browser folder uploading (`webkitdirectory`).
- **📦 ZIP Archive Support**: Upload `.zip` archives with automatic extraction into browsable S3 folders.
- **⚡ S3-Style Cloud Browser**:
  - Interactive breadcrumb directory navigation.
  - Switchable **Table View** and **Grid View**.
  - Folder and file type indicators (images, video, audio, code, documents, archives).
- **👁️ Universal In-Browser Previews**:
  - **Images**: High-resolution image zoom and preview.
  - **Media**: Built-in HTML5 video and audio players.
  - **Documents**: PDF viewer directly in-modal.
  - **Code & Text**: Formatted text/code viewer with copy button.
- **📥 Dynamic ZIP Exporter**: Download individual files or download entire folders/buckets as dynamic ZIP archives on the fly.
- **⚙️ Configurable via `.ini`**: Easily configure storage limits, security patterns, and UI settings in `config.ini`.
- **🚀 Zero External Dependencies**: Native PHP 8+, Vanilla CSS, and Vanilla JavaScript. Runs smoothly inside `htdocs` in XAMPP, WAMP, Laragon, or Apache/Nginx.

---

## 📂 Project Directory Structure

```text
Any-Share/
├── api/
│   ├── download.php        # Streams single files or generates dynamic ZIP archives
│   ├── search.php          # Validates Secret IDs and fetches bucket stats
│   ├── upload.php          # Handles multi-file, folder tree, and ZIP uploads
│   └── view.php            # Streams media, PDFs, and code for in-browser preview
├── assets/
│   ├── css/
│   │   └── style.css       # Modern dark-mode glassmorphism stylesheet
│   └── js/
│       ├── app.js          # Global notifications, search handler & preview modal
│       ├── browser.js      # S3 Explorer interactions & view mode switcher
│       └── uploader.js     # Drag-and-drop & webkitdirectory folder uploader
├── includes/
│   ├── config.php          # Parses config.ini and initializes environment
│   ├── footer.php          # Global preview modal, toasts, and scripts
│   ├── functions.php       # Directory scanner, security sanitizer, MIME & ZIP engine
│   └── header.php          # Common HTML header, branding, and navigation
├── uploads/                # Root storage directory for uploaded buckets
│   └── .gitkeep            # Preserves directory presence
├── browse.php              # S3-style directory explorer & file viewer page
├── config.ini              # Global application configuration file
├── index.php               # Homepage with Secret Key search bar & Upload Center
├── LICENSE                 # MIT License
└── README.md               # Project documentation & structure tree
```

---

## ⚙️ Configuration (`config.ini`)

All runtime options are managed in `config.ini`:

```ini
[app]
app_name = "Any Share"
app_version = "1.0.0"
app_description = "Private, Instant & Anonymous S3-Style Cloud File Sharing"
base_url = "" ; Leave empty for auto-detection (e.g. http://localhost/Any%20Share)

[storage]
upload_dir = "uploads"
max_file_size_mb = 1024 ; Maximum upload size in MB
allow_zip_extraction = true ; Extract uploaded ZIP files into folder trees
preserve_directory_structure = true ; Preserve folder trees on folder uploads

[security]
min_id_length = 3
max_id_length = 64
allowed_id_pattern = "^[a-zA-Z0-9_\-\.]+$" ; Alphanumeric, dash, underscore, dot
allow_public_indexing = false ; Keep secret IDs private and unindexed

[ui]
theme = "dark"
items_per_page = 50
enable_code_syntax_highlight = true
```

---

## 🚀 Getting Started

### 1. Running in XAMPP / WAMP / Localhost (`htdocs`)
1. Place or clone this repository into your web server root directory (e.g. `C:/xampp/htdocs/Any-Share`).
2. Ensure PHP 8.0 or newer is installed and the `zip` extension is enabled in `php.ini`.
3. Open your browser and navigate to:
   ```text
   http://localhost/Any-Share/
   ```

### 2. Running with PHP Built-in Server
You can also launch it instantly from your terminal without any server installation:
```bash
php -S localhost:8000
```
Then visit `http://localhost:8000` in your web browser.

---

## 🛠️ How to Use

1. **Upload Files or Folders**:
   - Go to the homepage.
   - Enter your preferred **Secret Key (ID)** or click **🎲 Random Key**.
   - Choose **Individual Files**, **Entire Folder Tree**, or **ZIP Archive**.
   - Drag & drop or select your items and click **Upload Now to Storage**.
2. **Access & Search Files**:
   - On the homepage search bar, enter your **Secret Key** and click **Browse Files**.
   - Your S3-style file explorer will load instantly.
3. **Explore, Preview & Download**:
   - Click any folder to navigate inside with breadcrumb navigation.
   - Click files to open the **Live Preview Modal** (supports images, audio, video, PDF, and code).
   - Click **Download as ZIP** to download the entire bucket or folder as a single archive.

---

## 📄 License
This project is open-source and licensed under the [MIT License](LICENSE).
