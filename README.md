# Any Share 🚀
> **Instant, Anonymous & Ephemeral Cloud File & Text Sharing with S3 Explorer & IDE Code Viewer**

[![PHP Version](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-10b981?style=flat)](LICENSE)
[![Dependencies](https://img.shields.io/badge/Dependencies-Zero%20(Native%20Vanilla)-10b981?style=flat)]()

**Any Share** is a self-hosted, lightweight, high-performance file and text sharing platform built with native PHP and MySQL. It offers a distraction-free **Clean White & Emerald Green** aesthetic that lets anyone instantly upload files, folder directory trees, or text snippets with **zero login, zero registration, and complete anonymity**.

Every upload is assigned a **Secret Key (Unique Access ID)**. All data is unindexed and **automatically self-destructs 30 minutes after creation** from both the server disk storage and the MySQL database.

---

## 🌟 Key Features

### ⚡ Dual-Mode Upload Center
- **📁 File & Directory Uploads**: Drag-and-drop individual files, batch files, or complete nested directory structures (preserves folder hierarchies using `webkitdirectory`).
- **📝 Direct Text & Code Notes**: Write or paste markdown notes, terminal logs, passwords, or code snippets directly into the browser without creating a file first.
- **📦 ZIP Archive Support**: Upload ZIP archives directly to share compressed packages.

### 🗂️ S3-Style Cloud Explorer (`browse.php`)
- **Breadcrumb Directory Navigation**: Navigate through multi-level nested folders effortlessly.
- **Dual Layout Modes**: Switch dynamically between **Table View** (detailed rows with file size, category, and download metrics) and **Grid View** (visual thumbnail cards).
- **⏱️ Live 30-Minute Expiry Countdown**: Real-time ticker badge (`⏱️ Expires in: mm:ss`) counts down to the exact second of automatic self-destruction.
- **🔍 In-Bucket Instant Filter**: Filter and locate files in large buckets instantly as you type.
- **📊 Real-time Bucket Analytics**: Displays total file count, combined storage size, view counts, and per-file download counts.

### 💻 Built-in IDE Code Viewer & Universal Preview Modal
- **IDE-Style Code Inspector**: Preview source code with line numbers, monospace formatting, and a 1-click **Copy Snippet** button.
- **Syntax Detection**: Built-in support for:
  - `PHP`, `JavaScript`, `TypeScript`, `Python`, `HTML`, `CSS`, `JSON`, `SQL`, `Bash / Shell`, `Markdown`, `XML`, and plain text.
- **Universal Media Players**:
  - **Images**: High-resolution zoom and preview (PNG, JPG, SVG, WebP, GIF).
  - **Audio**: In-modal HTML5 audio player (MP3, WAV, OGG).
  - **Video**: Responsive HTML5 video player (MP4, WebM).
  - **Documents**: Embedded in-browser PDF reader.

### 📥 Dynamic On-The-Fly ZIP Exporter
- Download individual files directly with original names.
- Or click **Download as ZIP** to dynamically stream the entire bucket or current subfolder as a ZIP archive on the fly without consuming temporary disk space.

### 🔒 Enterprise Security & Storage Hardening
- **`.htaccess` Upload Lockdown**: The `uploads/` directory explicitly disables script execution (`php`, `cgi`, `pl`, `py`), preventing Remote Code Execution (RCE) vulnerabilities.
- **PDO Prepared Statements**: 100% parameter-bound queries guarding against SQL Injection attacks.
- **Path Traversal Sanitization**: Strict path normalization prevents directory traversal exploits (`../`).
- **Input Sanitization**: Secret IDs are strictly validated via regular expressions (`/^[a-zA-Z0-9_\-\.]+$/`).

### 📱 Responsive & Modern UI/UX
- Minimalist **White & Emerald Green** high-contrast design.
- Segmented modern tabs for toggling between *Upload Files* and *Write Note*.
- Fully responsive across desktop, tablet, and mobile browsers.

---

## 📂 Project Directory Structure

```text
Any-Share/
├── api/
│   ├── download.php        # Streams single files or generates dynamic ZIP archives
│   ├── search.php          # Validates Secret IDs, checks expiry, and fetches bucket stats
│   ├── upload.php          # Handles file, folder, zip, and text uploads with 30m expiry
│   └── view.php            # Streams media, PDFs, and formatted code for preview
├── assets/
│   ├── css/
│   │   └── style.css       # Clean White & Emerald Green responsive stylesheet
│   └── js/
│       ├── app.js          # Global notifications, search handler & preview modal
│       ├── browser.js      # S3 Explorer interactions, layout switcher & countdown timer
│       └── uploader.js     # Unified file/folder drag-and-drop & text upload engine
├── includes/
│   ├── config.php          # Parses config.ini and initializes runtime environment
│   ├── db.php              # MySQL PDO database connection & auto-schema provisioning
│   ├── footer.php          # Global preview modal, toast container, and footer scripts
│   ├── functions.php       # Directory scanner, MIME resolver, ZIP streamer & purge engine
│   └── header.php          # Minimalist header with logo and GitHub repository link
├── uploads/                # Root storage directory for uploaded buckets
│   ├── .gitkeep            # Preserves directory in git
│   ├── .htaccess           # Security: Blocks script execution (PHP, CGI, Perl, Python)
│   └── index.php           # Security: Blank index preventing directory listing
├── .htaccess               # Root web server rules and header security
├── browse.php              # S3-style directory explorer & file viewer with countdown
├── cleanup.php             # 30-minute auto-deletion cron worker (CLI & HTTP)
├── config.ini              # Global application configuration file (with DB settings)
├── favicon.ico             # Application favicon
├── index.php               # Homepage with Secret Key search bar & Unified Upload Center
├── LICENSE                 # MIT License
├── README.md               # Project documentation & setup instructions
└── schema.sql              # MySQL database schema for manual or cPanel phpMyAdmin import
```

---

## ⚙️ Configuration Reference (`config.ini`)

All database credentials, storage limits, and security settings are centralized in `config.ini`:

```ini
; ====================================================================
; Any Share - Global Configuration File
; Compatible with XAMPP (Localhost) & cPanel Hosting
; ====================================================================

[app]
app_name = "Any Share"
app_version = "1.1.0"
app_description = "Instant, Anonymous & Temporary Cloud File & Text Sharing"
base_url = "" ; Leave empty for auto-detection (works on localhost, http/https, or custom domains)
timezone = "Asia/Dhaka" ; Default timezone for upload and expiry calculations

[database]
; Database connection settings (Default for local XAMPP is root with empty password)
; When deploying to cPanel or production, enter your hosting MySQL details.
db_host = "localhost"
db_port = 3306
db_name = "any_share"
db_user = "root"
db_pass = ""
db_charset = "utf8mb4"
auto_create_db = true ; Automatically creates database & tables if missing on local

[storage]
upload_dir = "uploads"
max_file_size_mb = 512 ; Maximum upload size (512 MB default)
auto_delete_minutes = 30 ; Auto delete all files and database records after 30 minutes
allow_zip_extraction = true ; Auto-extract ZIP files if requested during upload
preserve_directory_structure = true ; Preserve folder trees on folder uploads

[security]
min_id_length = 3
max_id_length = 64
allowed_id_pattern = "^[a-zA-Z0-9_\-\.]+$" ; Alphanumeric, dash, underscore, dot
allow_public_indexing = false ; Keep secret IDs private and unindexed

[ui]
theme = "emerald-light" ; Clean White + Emerald Green theme
items_per_page = 50
enable_code_syntax_highlight = true
```

---

## 🚀 How to Run & Install

### Option 1: Localhost with XAMPP / WAMP / LAMP (Recommended)

1. **Start Services**: Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. **Deploy Code**: Place the `Any Share` folder inside your web server's root directory:
   ```text
   C:/xampp/htdocs/Any-Share
   ```
3. **Open in Browser**: Navigate to:
   ```text
   http://localhost/Any-Share/
   ```
4. **Zero-Configuration Auto-Setup**:
   When `auto_create_db = true` is set in `config.ini`, Any Share connects to MySQL, automatically creates the `any_share` database if it doesn't exist, and provisions both the `buckets` and `files` tables automatically.

---

### Option 2: PHP Built-in Server (Fast CLI Run)

If you have PHP CLI and MySQL installed:
1. Ensure your MySQL server is running.
2. Open terminal in the project directory:
   ```bash
   php -S localhost:8000
   ```
3. Open `http://localhost:8000` in your web browser.

---

### Option 3: Deploying to cPanel / Shared Hosting

1. **Upload Files**:
   - Upload the project files (or upload and extract the ZIP) into your cPanel `public_html` or a subdomain folder (e.g. `public_html/anyshare`).
2. **Create MySQL Database in cPanel**:
   - Go to **MySQL® Databases** in cPanel.
   - Create a database (e.g. `cpaneluser_anyshare`).
   - Create a database user, assign a password, and grant **ALL PRIVILEGES** to the database.
3. **Import Database Schema**:
   - Open **phpMyAdmin** in cPanel.
   - Select your newly created database.
   - Click **Import** > select `schema.sql` from the project > click **Import**.
4. **Update `config.ini`**:
   Edit `config.ini` in cPanel File Manager with your database credentials:
   ```ini
   [database]
   db_host = "localhost"
   db_port = 3306
   db_name = "cpaneluser_anyshare"
   db_user = "cpaneluser_dbuser"
   db_pass = "YourStrongPasswordHere"
   auto_create_db = false
   ```
5. **Set Permissions**: Ensure the `uploads/` directory has write permissions (`755` or `775`).

---

## ⏱️ 30-Minute Auto-Deletion & Cron Setup

Any Share purges expired uploads in two ways:
1. **On-Demand Sweeps**: Every time a user visits, searches, uploads, or browses, the system cleans up any bucket older than 30 minutes.
2. **Automated Background Cron Worker (Recommended for Production)**:
   In your cPanel **Cron Jobs** section, add a cron job to run every 5 minutes:
   ```bash
   /usr/local/bin/php -q /home/username/public_html/cleanup.php >/dev/null 2>&1
   ```
   *(Replace `/home/username/public_html/` with your actual cPanel home path)*.
   
   Alternatively, you can trigger it via cURL:
   ```bash
   curl -s https://yourdomain.com/cleanup.php >/dev/null 2>&1
   ```

---

## 🛠️ How to Use

1. **Upload Files or Text**:
   - On the homepage, enter a custom **Secret Key** (e.g. `project-demo-2026`).
   - Choose **Upload Files** to drag-and-drop files or folders.
   - Or choose **Write Note** to paste text, markdown, or code.
   - Click **Upload Files** or **Save & Upload Note**.
2. **Search & Access**:
   - Enter the **Secret Key** into the search bar and press **Enter** or click **Browse**.
   - Your private S3-style explorer loads immediately.
3. **Live Countdown & Expiry**:
   - Watch the live 30-minute countdown badge. When the time expires, all files and database records are permanently deleted.
4. **Preview & Download**:
   - Click any code, image, video, audio, or PDF file to launch the **Live Preview Modal**.
   - Click individual download buttons or **Download as ZIP** to download the whole bucket as a single compressed archive.

---

## 📄 License
This project is open-source and licensed under the [MIT License](LICENSE).
Feel free to use, modify, and deploy for personal or commercial projects.
