# Staff Management Portal - Setup Instructions

## Shared Hosting (cPanel) Setup

### 1. Upload Files

Upload the entire `staff-portal` folder to your web root. For example:
- `public_html/staff-portal/` (if using a subfolder)
- `public_html/` (if using the root domain)

### 2. Create MySQL Database (cPanel)

1. Log in to cPanel
2. Open **MySQL Databases**
3. Create a new database (e.g. `youruser_staffportal`)
4. Create a database user with a strong password
5. Add the user to the database with **ALL PRIVILEGES**
6. Note the database name, username, and password

### 3. Import Database Schema

1. Open **phpMyAdmin** from cPanel
2. Select your database
3. Click **Import**
4. Choose `database/sigsol_sigsolportal.sql` (or your main schema file)
5. Click **Go** to import

This single schema file includes all tables and columns. For a fresh install, import it once. If you already have an older database, you may need to add missing columns manually or re-import (back up data first).

### 4. Configure config.php

Edit `config/config.php` and update:

```php
define('DB_HOST', 'localhost');      // Usually localhost on shared hosting
define('DB_NAME', 'your_db_name');   // Your database name
define('DB_USER', 'your_db_user');   // Your database username
define('DB_PASS', 'your_db_password'); // Your database password

define('BASE_URL', 'https://yourdomain.com/staff-portal'); // Your site URL (no trailing slash)
```

### 5. Set Permissions

- Ensure `uploads/profile_images/` is writable (755 or 775)
- In cPanel File Manager: Right-click `uploads/profile_images` > Change Permissions > 755

### 6. Create Default Admin Account

**Option A: Use create_admin.php (recommended)**

1. Visit: `https://yourdomain.com/staff-portal/database/create_admin.php`
2. This creates/updates the admin: `admin@example.com` / `Admin@123`
3. **Delete the file** `database/create_admin.php` after use for security

**Option B: If SQL import includes default admin**

- The pre-inserted hash may not work on all systems. Use Option A to ensure correct password.

### 7. PDF Export (Optional)

For PDF export functionality:

**Option A (auto-install):** Visit `https://yourdomain.com/staff-portal/lib/fpdf/install_fpdf.php` in your browser. It will download and install FPDF. Delete the install file afterward.

**Option B (manual):** Download FPDF from https://www.fpdf.org/ (single file: fpdf.php), create folder `lib/fpdf/`, and place `fpdf.php` inside.

### 8. First Login

- **Admin**: Go to `https://yourdomain.com/staff-portal/admin/login.php`
  - Default: `admin@example.com` / `Admin@123` (if you used create_admin.php)
- **Staff**: Go to `https://yourdomain.com/staff-portal/login.php`
  - Staff must register first at `https://yourdomain.com/staff-portal/register.php`

### 9. Security Checklist

- [ ] Delete `database/create_admin.php` after creating admin
- [ ] Change default admin password in Admin Settings
- [ ] Enable HTTPS and uncomment force HTTPS in root `.htaccess`
- [ ] Set `display_errors` to 0 in `config.php` for production

### 10. Force HTTPS (if SSL is enabled)

Edit root `.htaccess` and uncomment:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Default Credentials (Change Immediately)

- **Admin**: Run `database/create_admin.php` once, then delete the file
- **Staff**: Self-register at `/register.php`

---

## Troubleshooting

**Database connection failed**
- Verify DB_HOST, DB_NAME, DB_USER, DB_PASS in config.php
- On shared hosting, DB_NAME might need a prefix (e.g. `cpaneluser_dbname`)

**Uploads not working**
- Check `uploads/profile_images/` is writable (755 or 775)
- Ensure PHP `upload_max_filesize` is at least 2MB
- Ensure allowed file types: JPG, PNG

**PDF export not working**
- Download fpdf.php from fpdf.org and place in `lib/fpdf/fpdf.php`
- Ensure the lib/fpdf folder exists and is readable

**Forgot password email not sent**
- Shared hosting may not have mail() configured
- The reset page shows the reset link on screen if mail fails (for testing)
