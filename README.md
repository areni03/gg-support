# G&G Support Portal — Complete Setup Guide

> **Fresh rewrite — May 2026**
> Every file rebuilt from scratch. All BASE_URL issues fixed. All pages connected.

---

## Quick Start (3 steps)

### Step 1 — Copy files to XAMPP

1. Open XAMPP Control Panel → Start **Apache** and **MySQL** (both green)
2. Delete everything inside: `C:\xampp\htdocs\gg-support\`
3. Extract the ZIP into that folder so the structure looks like this:

```
C:\xampp\htdocs\gg-support\
  .htaccess
  index.php
  dashboard.php
  user_home.php
  generate_hash.php       ← delete after Step 3
  database_setup.sql      ← delete after Step 2
  includes\
  admin\
  assets\
  uploads\
```

---

### Step 2 — Set up the database

1. Open: **http://localhost/phpmyadmin**
2. In the left panel, check if `knowledgebase` database exists
   - If not: click **New** → type `knowledgebase` → click **Create**
3. Click the `knowledgebase` database
4. Click the **SQL** tab at the top
5. Open `database_setup.sql` from the project folder, select ALL, copy
6. Paste into phpMyAdmin SQL box → click **Go**
7. You should see success messages for all tables
8. **Delete `database_setup.sql`** from the project folder

---

### Step 3 — Fix the passwords

The database seed uses a placeholder hash. Fix it now:

1. Visit: **http://localhost/gg-support/generate_hash.php**
2. You will see a ready-made SQL UPDATE statement on screen
3. Copy it completely — it looks like:
   ```sql
   UPDATE knowledgebase.users SET password = '$2y$10$...';
   ```
4. Go back to phpMyAdmin → click `knowledgebase` → click **SQL** tab
5. Paste and click **Go**
6. **Delete `generate_hash.php`** from `C:\xampp\htdocs\gg-support\`

---

### Step 4 — Test the login

Visit: **http://localhost/gg-support**

| Role | Username | Password |
|------|----------|----------|
| System Admin | `sysadmin` | `Test@1234` |
| Admin | `admin` | `Test@1234` |
| User | `user1` | `Test@1234` |

---

## What Each File Does

### Core includes (every page loads these)

| File | Purpose |
|------|---------|
| `includes/db.php` | Database connection. Contains `BASE_URL`. Change here only. |
| `includes/auth_guard.php` | Session start, login check, role check, 30-min timeout |
| `includes/auth.php` | Handles the login form POST |
| `includes/logout.php` | Destroys session, redirects to login |
| `includes/header.php` | HTML head + sidebar. Included at top of every protected page |
| `includes/footer.php` | Closes layout, loads main.js |
| `includes/csrf.php` | `csrf_field()` and `csrf_verify()` helpers |
| `includes/sanitise.php` | `clean_string()`, `clean_int()`, `clean_post()`, `clean_get()` |

### AJAX endpoints (called by JavaScript)

| File | Called from | Purpose |
|------|-------------|---------|
| `includes/search.php` | `user_home.php` | Live solution search |
| `includes/submit_flag.php` | `user_home.php` | Flag a missing answer |
| `includes/submit_answer.php` | `user_home.php` | Submit an answer for review |
| `includes/get_categories.php` | JS | Returns child categories as JSON |
| `includes/get_stats.php` | JS | Returns dashboard counts as JSON |
| `includes/upload_image.php` | TinyMCE | Handles image uploads |

### Admin pages

| File | Role required | Purpose |
|------|---------------|---------|
| `admin/system_dashboard.php` | system_admin | Full system overview |
| `admin/dashboard.php` | admin, system_admin | Admin home |
| `admin/users.php` | system_admin | Add/edit/delete users |
| `admin/categories.php` | admin, system_admin | Category tree management |
| `admin/solutions.php` | admin, system_admin | Add/edit/approve solutions |
| `admin/announcements.php` | admin, system_admin | Manage announcements |
| `admin/pending_flags.php` | admin, system_admin | Resolve flagged questions |

---

## How BASE_URL Works

`BASE_URL` is defined once in `includes/db.php`:

```php
define('BASE_URL', '/gg-support');
```

Every link and redirect in every file uses it:

```php
// Redirects
header('Location: ' . BASE_URL . '/admin/dashboard.php');

// Links in PHP
echo '<a href="' . BASE_URL . '/admin/solutions.php">Solutions</a>';

// Links in HTML templates
<a href="<?= BASE_URL ?>/admin/users.php">Users</a>
```

**To move to a live server:** change `BASE_URL` to `''` (empty string) and update the DB credentials. That's it.

---

## How Sessions Work

Sessions are started in `auth_guard.php` using `guard_require_login()`.
Every protected page starts with:

```php
require_once __DIR__ . '/../includes/auth_guard.php';
guard_require_login();
guard_require_role(['admin', 'system_admin']); // omit on user pages
```

The session stores:
- `$_SESSION['user_id']`
- `$_SESSION['username']`
- `$_SESSION['full_name']`
- `$_SESSION['role']`
- `$_SESSION['last_activity']` — updated every page load, triggers 30-min timeout

---

## Troubleshooting

### Dashboard shows blank page / error
- Is XAMPP MySQL running? Check the Control Panel — MySQL must be green
- Open `http://localhost/phpmyadmin` — can you see it? If not, Apache is not running
- Check that `includes/db.php` has the correct DB settings:
  ```php
  define('BASE_URL', '/gg-support');
  define('DB_NAME', 'knowledgebase');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  ```

### Login says invalid credentials
- You have not run the password hash step (Step 3 above)
- Run `generate_hash.php` and update the database

### Links go to wrong URL / 404 errors
- `BASE_URL` in `db.php` must be `/gg-support` (with leading slash, no trailing slash)
- The project folder must be named exactly `gg-support` inside `htdocs`

### Session expires immediately / keeps logging out
- Check that only ONE session is started per page (auth_guard.php handles this)
- Make sure there is no `session_start()` call in any page file — it is all handled in auth_guard.php

### Port 3306 conflict (MySQL won't start)
- Docker may be running and using port 3306
- Open a terminal: `docker compose down` (in the Docker project folder)
- Then start XAMPP MySQL again

### TinyMCE shows "No API key" warning
- This is just a warning — TinyMCE still works fully
- To remove it: register free at https://www.tiny.cloud and replace `no-api-key` in `admin/solutions.php`

### Images not uploading via TinyMCE
- Make sure the `uploads/` folder exists at `C:\xampp\htdocs\gg-support\uploads\`
- Right-click the folder → Properties → make sure it is not read-only

---

## Role Permissions Summary

| Feature | User | Admin | System Admin |
|---------|------|-------|-------------|
| Search solutions | ✅ | ✅ | ✅ |
| See admin-only solutions | ❌ | ✅ | ✅ |
| Raise a flag | ✅ | ✅ | ✅ |
| Submit an answer | ✅ | ✅ | ✅ |
| Approve / reject solutions | ❌ | ✅ | ✅ |
| Manage categories | ❌ | ✅ | ✅ |
| Manage announcements | ❌ | ✅ | ✅ |
| Manage flags | ❌ | ✅ | ✅ |
| Manage users | ❌ | ❌ | ✅ |
| System dashboard | ❌ | ❌ | ✅ |

---

## Database Table Reference

```sql
users        — id, full_name, username, email, password, role, is_active
categories   — id, name, parent_id (self-referencing tree), created_by
solutions    — id, question, answer, category_id, submitted_by, status, requires_admin, verified_by
announcements— id, title, content, priority, is_active, created_by
flags        — id, question, raised_by, status (open/resolved/ignored), resolved_by
```

---

## Before Going Live

1. Change `BASE_URL` in `db.php` to `''`
2. Update DB credentials in `db.php`
3. Set `'secure' => true` in session cookie params (auth_guard.php + auth.php) for HTTPS
4. Uncomment security headers in `.htaccess`
5. Make sure `uploads/` folder has correct write permissions on the server
6. Delete `generate_hash.php` and `database_setup.sql` if still present
7. Change all user passwords from the default

---

*Last updated: May 2026*
