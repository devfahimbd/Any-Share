# Any-Share 🚀
> **Private, Instant & Anonymous S3-Style Cloud File Sharing & Directory Explorer**

Any Share is a lightweight, zero-dependency, self-hosted cloud file-sharing platform written in pure PHP with **MySQL database support**. It allows users to upload files, full directory trees, or ZIP archives without requiring any user registration or login. Each upload is mapped to a private **Unique Secret ID (Access Key)**. 

Uploads are **strictly unindexed**, meaning files cannot be discovered or listed publicly. Anyone with the valid Secret Key can search and explore the files through a rich **S3-style file browser** interface with live inline previews, download counters, view analytics, and on-the-fly ZIP downloads.

---

## 🌟 Key Features

- **🔒 Zero Login & 100% Anonymous**: No accounts, passwords, or cookies required.
- **🛡️ Private & Unindexed Storage**: Buckets are hidden from public indexing; accessible only via their specific Secret ID.
- **🗄️ MySQL Database Integration**: Seamless synchronization of buckets, file metadata, views counter, and download metrics.
- **🔄 Auto-Database Provisioning**: Automatically creates database `any_share` and required tables in local XAMPP with zero manual configuration.
- **🌐 cPanel Ready**: Includes `schema.sql` for 1-click phpMyAdmin import on cPanel or production servers.
- **📁 Full Folder & Directory Tree Uploads**: Preserve nested directories and subfolders using modern browser folder uploading (`webkitdirectory`).
- **📦 ZIP Archive Support**: Upload `.zip` archives with automatic extraction into browsable S3 folders.
- **⚡ S3-Style Cloud Browser**:
  - Interactive breadcrumb directory navigation.
  - Switchable **Table View** and **Grid View**.
  - Folder and file type indicators (images, video, audio, code, documents, archives).
  - Download counters per file and total bucket view/download analytics.
- **👁️ Universal In-Browser Previews**:
  - **Images**: High-resolution image zoom and preview.
  - **Media**: Built-in HTML5 video and audio players.
  - **Documents**: PDF viewer directly in-modal.
  - **Code & Text**: Formatted text/code viewer with copy button.
- **📥 Dynamic ZIP Exporter**: Download individual files or download entire folders/buckets as dynamic ZIP archives on the fly.
- **⚙️ Configurable via `.ini`**: Easily configure database connection, storage limits, security patterns, and UI settings in `config.ini`.
- **🚀 Zero External Dependencies**: Native PHP 8+, Vanilla CSS, and Vanilla JavaScript. Runs directly inside `htdocs` in XAMPP, WAMP, or cPanel.

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
│   ├── db.php              # MySQL PDO database connection & auto-schema provisioning
│   ├── footer.php          # Global preview modal, toasts, and scripts
│   ├── functions.php       # Directory scanner, security sanitizer, MIME & ZIP engine
│   └── header.php          # Common HTML header, branding, and navigation
├── uploads/                # Root storage directory for uploaded buckets
│   └── .gitkeep            # Preserves directory presence
├── browse.php              # S3-style directory explorer & file viewer page
├── config.ini              # Global application configuration file (with DB settings)
├── index.php               # Homepage with Secret Key search bar & Upload Center
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
app_version = "1.0.0"
app_description = "Private, Instant & Anonymous S3-Style Cloud File Sharing"
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
4. **Auto-Creation**: The system will automatically connect to MySQL, create the `any_share` database, and initialize the `buckets` and `files` tables automatically! Look for the green `🟢 MySQL` indicator in the header.

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
