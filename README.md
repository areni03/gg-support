# 🛡️ G&G Support Portal

A role-based internal support portal built with PHP, MySQL, and vanilla JavaScript.
Designed for government and public sector organisations.

---

## ✨ Features

- **Role-based access** — System Admin, Admin, and User roles
- **Solution base** — searchable knowledge base with admin-only and public solutions
- **Ticket management** — raise, assign, escalate, and resolve support tickets
- **SLA tracking** — configurable attendance and resolution deadlines per level
- **Round-robin assignment** — tickets auto-assigned across admins in rotation
- **Time extension** — admins can request extensions with configurable reasons
- **Announcement bar** — priority-ordered notices shown to all users
- **Category management** — nested category tree (Category → Sub-category → Sub-title)
- **User management** — system admin creates and manages all users (no public signup)
- **Audit trail** — every ticket action logged with timestamps

---

## 🗂️ Tech Stack

| Layer      | Technology               |
|------------|--------------------------|
| Backend    | PHP 8.2                  |
| Database   | MySQL 8.0                |
| Frontend   | HTML5, CSS3, JavaScript  |
| Rich Text  | TinyMCE 6                |
| Web Server | Apache (XAMPP)           |

---

## 🚀 Quick Setup (XAMPP)

### Prerequisites
- [XAMPP](https://www.apachefriends.org) with PHP 8.0+ and MySQL
- A web browser

### Step 1 — Clone the repository

```bash
git clone https://github.com/YOURUSERNAME/gg-support.git
cd gg-support
```

Or download the ZIP and extract it.

### Step 2 — Place in XAMPP

Copy the entire `gg-support` folder into:

```
C:\xampp\htdocs\gg-support\
```

### Step 3 — Configure database connection

Copy the example config file:

```bash
cp includes/db.example.php includes/db.php
```

Open `includes/db.php` and update if needed:

```php
define('BASE_URL', '/gg-support');  // keep this for XAMPP subfolder
define('DB_HOST',  'localhost');
define('DB_NAME',  'knowledgebase');
define('DB_USER',  'root');
define('DB_PASS',  '');             // XAMPP default is empty
```

### Step 4 — Create the database

1. Start XAMPP — make sure both **Apache** and **MySQL** are green
2. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
3. Click **New** in the left panel → name it `knowledgebase` → set encoding to `utf8mb4_unicode_ci` → click **Create**
4. Click on `knowledgebase` → click the **SQL** tab
5. Open `database_setup.sql` from this project → copy all contents → paste → click **Go**
6. Then open `ticket_schema.sql` → copy all contents → paste → click **Go**

### Step 5 — Set correct passwords

The seed data uses a placeholder password hash. You must replace it:

1. Create a temporary file at `C:\xampp\htdocs\gg-support\hash.php`:

```php
<?php echo password_hash('Test@1234', PASSWORD_BCRYPT);
```

2. Visit [http://localhost/gg-support/hash.php](http://localhost/gg-support/hash.php)
3. Copy the hash shown
4. In phpMyAdmin → SQL tab → run:

```sql
UPDATE users SET password = 'PASTE_YOUR_HASH_HERE';
```

5. **Delete `hash.php` immediately after**

### Step 6 — Open the portal

Visit: [http://localhost/gg-support](http://localhost/gg-support)

---

## 👤 Default Login Credentials

> ⚠️ Change all passwords immediately after first login on any real server.

| Role           | Username   | Password    |
|----------------|------------|-------------|
| System Admin   | `sysadmin` | `Test@1234` |
| Admin          | `admin`    | `Test@1234` |
| User           | `user1`    | `Test@1234` |

---

## 📁 Project Structure

```
gg-support/
├── admin/                    ← Admin panel pages
│   ├── system_dashboard.php  ← System Admin home
│   ├── dashboard.php         ← Admin home
│   ├── users.php             ← User management
│   ├── categories.php        ← Category management
│   ├── solutions.php         ← Solution management
│   ├── announcements.php     ← Announcement management
│   ├── pending_flags.php     ← Flagged questions
│   ├── tickets.php           ← All tickets view
│   └── ticket_config.php     ← Ticket level & SLA config
├── assets/
│   ├── css/style.css         ← Main stylesheet
│   └── js/main.js            ← All JavaScript
├── includes/
│   ├── db.example.php        ← ⬅ Copy to db.php and configure
│   ├── auth.php              ← Login handler
│   ├── auth_guard.php        ← Session + role protection
│   ├── header.php            ← Shared sidebar + HTML head
│   ├── footer.php            ← Closes layout
│   ├── ticket_helpers.php    ← Ticket logic (round-robin, SLA)
│   └── ...
├── uploads/                  ← TinyMCE image uploads (gitignored)
├── database_setup.sql        ← ⬅ Run this first in phpMyAdmin
├── ticket_schema.sql         ← ⬅ Run this second in phpMyAdmin
├── index.php                 ← Login page
├── dashboard.php             ← Role router
├── user_home.php             ← User search page
├── user_tickets.php          ← User's ticket list
└── ticket_detail.php         ← View/manage single ticket
```

---

## 🔐 Role Permissions

| Feature                    | User | Admin | System Admin |
|----------------------------|:----:|:-----:|:------------:|
| Search solutions           | ✅   | ✅    | ✅           |
| Raise tickets              | ✅   | ✅    | ✅           |
| View announcements         | ✅   | ✅    | ✅           |
| Attend & resolve tickets   | ❌   | ✅    | ✅           |
| Extend ticket time         | ❌   | ✅    | ✅           |
| Add/manage solutions       | ❌   | ✅    | ✅           |
| Manage categories          | ❌   | ✅    | ✅           |
| Manage announcements       | ❌   | ✅    | ✅           |
| Manage users               | ❌   | ❌    | ✅           |
| Configure ticket levels    | ❌   | ❌    | ✅           |
| Configure SLA settings     | ❌   | ❌    | ✅           |
| Manage extension reasons   | ❌   | ❌    | ✅           |

---

## ⚙️ Ticket System Configuration

System Admin can configure from **Admin Panel → Ticket Configuration**:

- **Levels** — define how many admin levels exist (default: 3)
- **Assign admins** — assign specific admins to each level with round-robin order
- **SLA per level** — set attendance and resolution time limits in minutes
- **Extension reasons** — add/remove/disable reasons for the extension dropdown

---

## 🔄 SLA & Escalation Logic

1. User raises ticket → assigned to Level 1 admin via round-robin
2. Admin must attend (click Take Up) within **attendance SLA**
3. After take-up, admin must resolve within **resolution SLA**
4. SLA deadlines are **fixed-time** (based on when ticket was raised, not when attended)
5. If attendance SLA missed → ticket escalates to next admin in level, then next level
6. If all levels exhausted → ticket marked **Unattended**

---

## 🤝 Contributing

1. Fork this repository
2. Create a feature branch: `git checkout -b feature/your-feature-name`
3. Make your changes
4. Commit: `git commit -m "Add: your feature description"`
5. Push: `git push origin feature/your-feature-name`
6. Open a Pull Request

---

## 📋 Roadmap

- [ ] Asset management module (hardware + software inventory)
- [ ] Network monitoring dashboard
- [ ] AI assistant integration (Ollama)
- [ ] Email notifications on ticket assignment
- [ ] PDF export for ticket reports
- [ ] Two-factor authentication

---

## 📄 License

MIT License — free to use, modify, and distribute.

---

## 📞 Support

If you find a bug or have a feature request, open an issue on GitHub.
