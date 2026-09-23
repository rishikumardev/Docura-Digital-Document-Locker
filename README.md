# Docura — Full-Stack Digital Document Locker (No XAMPP)

This version is the college-level Docura project without React and without XAMPP/MySQL.

## Stack
- HTML
- CSS
- JavaScript
- PHP 8+
- SQLite
- PHP built-in development server

## Why this version
You do NOT need to download XAMPP or MySQL.

Only PHP is required.

## Run

Open a terminal in the `Docura_No_XAMPP_SQLite` folder:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/setup.php
```

Click **Open Docura**.

Or directly:

```text
http://localhost:8000/
```

## Windows check

Check PHP:

```bash
php -v
```

If that command works, run the project.

If `php` is not recognized, PHP itself is not installed. In that case the project cannot execute PHP APIs until PHP is installed.

## Database

SQLite is stored in:

```text
database/docura.sqlite
```

Tables:
- users
- folders
- documents
- shares
- activity

## Features
- Sign In / Create Account
- Session authentication
- Password hashing
- CSRF protection
- Profile details + Logout
- Dark mode
- Colorful interactive dashboard
- Upload / drag & drop
- Server-side file storage
- PDF/image preview
- Download with access checks
- Search / filter / sort
- Folders
- Starred documents
- Sharing between registered users
- Shared With Me
- Trash / restore / permanent delete
- Activity log
- Security Center
- Admin role + Admin Console
- Responsive UI

## Admin demo

After registering a user, edit the database using a SQLite tool or the included project flow. You can also use a small PHP script later to promote one account:

```sql
UPDATE users SET role='admin' WHERE email='your-email@example.com';
```

For a real production deployment, use HTTPS, stronger upload controls, rate limiting, backups, malware scanning and secure infrastructure.
