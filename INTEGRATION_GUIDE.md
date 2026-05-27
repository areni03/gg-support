# Module 2 — Ticket Management: Integration Guide

## Files to Copy Into Your XAMPP Project

Copy every file from this package into your existing project at
`C:\xampp\htdocs\gg-support\` maintaining the same folder structure:

```
gg-support/
  ticket_schema.sql          ← Run this in phpMyAdmin first
  sla_cron.php               ← Schedule as a cron/task
  user_tickets.php           ← User's ticket page
  ticket_detail.php          ← View + act on any ticket

  includes/
    ticket_helpers.php       ← Core logic (assign, escalate, SLA, log)
    raise_ticket.php         ← AJAX: user raises a ticket
    ticket_action.php        ← AJAX: take_up / resolve / extend

  admin/
    tickets.php              ← Admin ticket list with filters
    ticket_config.php        ← System Admin: levels, SLA, reasons

  assets/css/
    tickets.css              ← Add-on styles (link in header.php)
```

---

## Step 1 — Run the SQL Schema

1. Open `http://localhost/phpmyadmin`
2. Select database `knowledgebase`
3. Click **SQL** tab → paste contents of `ticket_schema.sql` → Execute
4. It creates 6 tables: `ticket_levels`, `ticket_level_admins`,
   `ticket_extension_reasons`, `tickets`, `ticket_activity`,
   `ticket_extensions`, `round_robin_pointer`
5. Seed data inserts 3 default levels + 5 extension reasons

---

## Step 2 — Link the CSS

In `includes/header.php`, add inside `<head>`:

```html
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tickets.css">
```

---

## Step 3 — Add Ticket Links to Sidebar

In `includes/header.php` sidebar, add for **users**:

```html
<a href="<?= BASE_URL ?>/user_tickets.php">My Tickets</a>
```

For **admin / system_admin**:

```html
<a href="<?= BASE_URL ?>/admin/tickets.php">Ticket Management</a>
<?php if ($_SESSION['role'] === 'system_admin'): ?>
<a href="<?= BASE_URL ?>/admin/ticket_config.php">Ticket Configuration</a>
<?php endif; ?>
```

---

## Step 4 — Schedule the SLA Cron

**Windows (Task Scheduler):**
- Action: `php C:\xampp\htdocs\gg-support\sla_cron.php`
- Trigger: Every 10 minutes

Alternatively, the SLA check also runs automatically every time
an admin loads `admin/tickets.php`.

---

## How the Full Ticket Flow Works

```
User raises ticket
       ↓
ticket inserted → assignTicketToLevel(level 1, round-robin)
       ↓
Admin receives ticket (status: open)
       ↓
Admin clicks Take Up → status: in_progress, attended_at recorded
       ↓
Admin resolves → status: resolved, option to push to Solution Base
   OR
Admin marks unresolved → status: unresolved
   OR
Attend SLA breached → escalate to next admin in level / next level
Resolve SLA breached → escalate to next level
   → If no more levels → status: unattended
```

---

## Key Design Decisions (matching the audio notes)

| Requirement | Implementation |
|---|---|
| Configurable levels (not hardcoded) | `ticket_levels` table, managed via `ticket_config.php` |
| Round-robin assignment | `round_robin_pointer` table tracks last index per level |
| Fixed-time SLA (not sliding) | `attend_deadline` & `resolve_deadline` set at assignment time |
| Attendance + Resolution SLA | Two separate deadline columns per ticket |
| Escalate on breach | `runSlaCheck()` in `ticket_helpers.php` |
| Time extension before deadline | Validated with `$nowTime > $oldDeadline` check |
| Extension reasons configurable | `ticket_extension_reasons` table, managed in config UI |
| Full audit trail | Every event logged to `ticket_activity` |
| Push to Solution Base on resolve | Inserts into existing `solutions` table |
| No public signup | Existing `users` table used; role inferred from session |
| system_admin = super admin | All `system_admin` role checks map to super admin behaviour |

---

## Role Permissions Summary

| Action | user | admin | system_admin |
|---|---|---|---|
| Raise ticket | ✅ | ✅ | ✅ |
| View own tickets | ✅ | ✅ | ✅ |
| Take up assigned ticket | ✗ | ✅ | ✅ |
| Resolve / unresolve ticket | ✗ | ✅ | ✅ |
| Extend deadline | ✗ | ✅ | ✅ |
| View ALL tickets | ✗ | ✗ | ✅ |
| Configure levels / SLA | ✗ | ✗ | ✅ |
| Manage extension reasons | ✗ | ✗ | ✅ |
| Assign admins to levels | ✗ | ✗ | ✅ |

---

## Notes for Phase 8 (Security Hardening)

- All POST handlers already validate `csrf_token` via `verify_csrf_token()`
- All DB queries use PDO prepared statements
- Role checks are enforced at the top of every admin file
- Remember to apply `security_headers.php` when you enable it
