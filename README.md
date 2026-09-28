# Any-Share 🚀
> **Instant, Anonymous & Temporary Cloud File & Text Sharing with S3-Style Explorer**

Any Share is a lightweight, zero-dependency, self-hosted file and text sharing platform written in pure PHP with **MySQL database support**. It features a modern **White & Emerald Green** aesthetic and allows anyone to upload files, full directory trees, or text snippets without any login or account. Each upload is mapped to a private **Unique Secret ID (Access Key)**.

All uploads are **strictly unindexed** and **automatically self-destruct 30 minutes after upload** from both the server disk and the MySQL database. Anyone with the valid Secret Key can search, explore through an **S3-style browser**, view live countdown timers, preview media/documents/code, and download individual files or dynamic ZIP archives.

---

## 🌟 Key Features

- **⏱️ 30-Minute Auto-Expiry & Deletion**: Every upload automatically purges 30 minutes after creation from disk storage and MySQL. Includes live countdown timer in the browser!
- **🎨 Clean White & Emerald Green Theme**: Beautiful, minimal, high-contrast, distraction-free modern interface.
- **🔒 Zero Login & 100% Anonymous**: No accounts, passwords, or cookies required.
- **🛡️ Private & Unindexed Storage**: Buckets are hidden from public indexing; accessible only via their specific Secret ID.
- **📁 Unified File & Folder Uploads**: Drag and drop any file, folder (preserving directory trees via `webkitdirectory`), or ZIP archive.
- **📝 Direct Text & Note Upload**: Quick in-browser text and code editor to share notes, snippets, or logs under a Secret ID.
- **🗄️ MySQL Database Integration**: Synchronizes buckets, file metadata, views counter, and download metrics.
- **🔄 Auto-Database Provisioning**: Automatically creates database `any_share` and required tables in local XAMPP with zero manual configuration.
- **🌐 cPanel Ready**: Includes `schema.sql` for 1-click phpMyAdmin import on cPanel or production servers.
- **⚡ S3-Style Cloud Browser**:
  - Interactive breadcrumb directory navigation.
  - Switchable **Table View** and **Grid View**.
  - Live 30-minute countdown badge (`⏱️ Expires in: mm:ss`).
  - Folder and file type indicators (images, video, audio, code, documents, archives).
  - Download counters per file and total bucket view/download analytics.
- **👁️ Universal In-Browser Previews**:
  - **Images**: High-resolution image zoom and preview.
  - **Media**: Built-in HTML5 video and audio players.
  - **Documents**: PDF viewer directly in-modal.
  - **Code & Text**: Formatted text/code viewer with copy button.
- **📥 Dynamic ZIP Exporter**: Download individual files or download entire folders/buckets as dynamic ZIP archives on the fly.
- **⚙️ Configurable via `.ini`**: Easily configure database connection, storage limits (512 MB default), auto-deletion minutes (30 mins), and UI settings in `config.ini`.
- **🚀 Zero External Dependencies**: Native PHP 8+, Vanilla CSS, and Vanilla JavaScript. Runs directly inside `htdocs` in XAMPP, WAMP, or cPanel.

---

## 📂 Project Directory Structure

```text
Any-Share/
├── api/
│   ├── download.php        # Streams single files or generates dynamic ZIP archives
│   ├── search.php          # Validates Secret IDs, checks expiry, and fetches bucket stats
│   ├── upload.php          # Handles file, folder, zip, and text uploads with 30m expiry
│   └── view.php            # Streams media, PDFs, and code for in-browser preview
├── assets/
│   ├── css/
│   │   └── style.css       # Clean White & Emerald Green stylesheet
│   └── js/
│       ├── app.js          # Global notifications, search handler & preview modal
│       ├── browser.js      # S3 Explorer interactions, view toggle & live countdown timer
│       └── uploader.js     # Unified file/folder drag-and-drop & text upload engine
├── includes/
│   ├── config.php          # Parses config.ini and initializes environment
│   ├── db.php              # MySQL PDO database connection & auto-schema provisioning
│   ├── footer.php          # Global preview modal, toasts, and scripts
│   ├── functions.php       # Directory scanner, security sanitizer, MIME, ZIP & 30m purge engine
│   └── header.php          # Minimal header with logo, 30m badge, and MySQL status
├── uploads/                # Root storage directory for uploaded buckets
│   └── .gitkeep            # Preserves directory presence
├── browse.php              # S3-style directory explorer & file viewer with countdown
├── cleanup.php             # 30-minute auto-deletion cron worker (CLI & HTTP)
├── config.ini              # Global application configuration file (with DB settings)
├── index.php               # Homepage with Secret Key search bar & Unified Upload Center
├── LICENSE                 # MIT License
├── README.md               # Project documentation & structure tree
└── schema.sql              # MySQL database schema for XAMPP & cPanel phpMyAdmin
```

---

## ⚙️ Configuration (`config.ini`)

All database and runtime options are managed in `config.ini`:

```ini
[app]
app_name = "Any Share"
app_version = "1.1.0"
app_description = "Instant, Anonymous & Temporary Cloud File & Text Sharing"
base_url = "" ; Leave empty for auto-detection (e.g. http://localhost/Any%20Share)

[database]
; Database connection settings (Default for XAMPP is root with empty password)
; When deploying to cPanel, update these with your cPanel database details.
db_host = "localhost"
db_port = "3306"
db_name = "any_share"
db_user = "root"
db_pass = ""
db_charset = "utf8mb4"
auto_create_db = true ; Automatically create database & tables if missing on local

[storage]
upload_dir = "uploads"
max_file_size_mb = 512 ; Maximum upload size (512 MB as requested)
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

## 🚀 Localhost Testing (XAMPP)

1. Open your **XAMPP Control Panel** and start **Apache** and **MySQL**.
2. Keep this project inside your web root:
   ```text
   C:/xampp/htdocs/Any Share
   ```
   *(Or clone directly into `htdocs`)*.
3. Open your browser and navigate to:
   ```text
   http://localhost/Any%20Share/
   ```
4. **Auto-Creation**: The system will automatically connect to MySQL, create the `any_share` database, and initialize the `buckets` and `files` tables with the `expires_at` column. Look for the green `🟢 MySQL` indicator in the header.

---

## ⏱️ 30-Minute Auto-Deletion & Cron Setup

Files and database records are automatically pruned:
1. **Automatic on Request**: Every time a user visits, searches, uploads, or browses, the system sweeps for any uploads older than 30 minutes and deletes them from both disk and MySQL.
2. **Scheduled Cron (Recommended for cPanel)**:
   In your cPanel **Cron Jobs** section, add a cron job to run every 5 minutes:
   ```bash
   */5 * * * * php /home/username/public_html/cleanup.php >/dev/null 2>&1
   ```
   Or trigger it via curl:
   ```bash
   */5 * * * * curl -s https://yourdomain.com/cleanup.php >/dev/null 2>&1
   ```

---

## 🌐 Deploying to cPanel

When you are ready to upload this to your cPanel hosting:
1. **Upload Files**: Upload all project files to your cPanel `public_html` (or a subdomain directory).
2. **Create Database in cPanel**:
   - Go to **MySQL® Databases** in cPanel.
   - Create a new database (e.g. `youruser_anyshare`) and a database user with all privileges.
3. **Import SQL**:
   - Open **phpMyAdmin** in cPanel.
   - Select your database and click **Import**.
   - Choose `schema.sql` from the project and click **Import**.
4. **Update `config.ini`**:
   Edit `config.ini` in cPanel File Manager:
   ```ini
   [database]
   db_host = "localhost"
   db_name = "youruser_anyshare"
   db_user = "youruser_dbuser"
   db_pass = "your_strong_password"
   auto_create_db = false
   ```
5. Done! Your cloud storage explorer is live in production.

---

## 🛠️ How to Use

1. **Upload Files or Text**:
   - Go to the homepage.
   - Enter your preferred **Secret Key (ID)** or click **🎲 Random Key**.
   - Choose **Upload Files / Folders** or **Write / Paste Text**.
   - Click **Upload Files** or **Save & Upload Text**.
2. **Access & Search Files**:
   - On the homepage search bar, enter your **Secret Key** and click **Browse**.
   - Your S3-style file explorer will load instantly.
3. **Live Countdown & Expiry**:
   - Watch the live timer countdown. Once 30 minutes lapse from upload, the files are permanently purged.
4. **Explore, Preview & Download**:
   - Click any folder to navigate inside with breadcrumbs.
   - Click files to open the **Live Preview Modal** (supports images, audio, video, PDF, and code).
   - Click **Download as ZIP** to download the entire bucket or folder as a single archive.

---

## 📄 License
This project is open-source and licensed under the [MIT License](LICENSE).
