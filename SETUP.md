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

- **Database**: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- **BASE_URL**: Your site URL (no trailing slash)
- **SMTP (for email and OTP)**: Set `MAIL_ENABLED` to `true`, then set `SMTP_HOST`, `SMTP_PORT`, `SMTP_ENCRYPTION` (`tls` or `ssl`), `SMTP_USERNAME`, `SMTP_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. Without SMTP, forgot-password and login/registration verification emails will not be sent.

If you have an existing database, run `database/migrations/001_update_staff_table.sql` in phpMyAdmin to add the new staff columns, `email_verified`, and the `verification_codes` table (required for registration verification and login OTP). If any ALTER fails with "Duplicate column name", that column already exists—comment out that line and run again.

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

### 7. PDF Export

PDF export uses **client-side** generation (html2pdf.js in the browser), like the Cosmopolitan Bank receipt flow. No server-side library or install needed.

- From **View Staff** or **Staff List**, click **Download PDF**. A printable page opens; click **Download PDF** on that page to save the file.
- The page loads html2pdf from a CDN; the site must be able to load `cdnjs.cloudflare.com` (or deploy `assets/js/html2pdf.bundle.min.js` locally and change the script src in `admin/export-pdf.php`).

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
- PDF is generated in the browser. Ensure JavaScript is enabled and the page can load the CDN script (cdnjs.cloudflare.com). If your environment blocks CDNs, copy `html2pdf.bundle.min.js` into `assets/js/` and update the script src in `admin/export-pdf.php`.

**Forgot password email not sent**
- Shared hosting may not have mail() configured
- The reset page shows the reset link on screen if mail fails (for testing)
