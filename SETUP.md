# ⚡ Quick Setup Checklist

Follow these steps in order. Each step must be completed before the next.

## ✅ Checklist

### Environment
- [ ] XAMPP installed (https://www.apachefriends.org)
- [ ] Apache is running (green in XAMPP Control Panel)
- [ ] MySQL is running (green in XAMPP Control Panel)

### Files
- [ ] Project folder placed at `C:\xampp\htdocs\gg-support\`
- [ ] `includes/db.example.php` copied to `includes/db.php`
- [ ] `includes/db.php` updated with your database credentials

### Database
- [ ] Database `knowledgebase` created in phpMyAdmin
- [ ] `database_setup.sql` run in phpMyAdmin SQL tab
- [ ] `ticket_schema.sql` run in phpMyAdmin SQL tab
- [ ] Passwords updated using hash.php method
- [ ] hash.php deleted after use

### First Login
- [ ] Opened http://localhost/gg-support
- [ ] Logged in as sysadmin with Test@1234
- [ ] Changed default passwords for all users

### Ticket Configuration (System Admin)
- [ ] Go to Admin Panel → Ticket Configuration
- [ ] Verify 3 default levels are present
- [ ] Assign admin users to levels
- [ ] Adjust SLA times to match your requirements
- [ ] Review/update extension reasons

---

## 🔧 Common Problems

### "Not Found" 404 error
- Make sure folder is at exactly `C:\xampp\htdocs\gg-support\`
- Make sure Apache is running in XAMPP
- Try `http://localhost/gg-support/index.php` (with index.php explicit)

### "Database connection failed"
- Make sure MySQL is running (green in XAMPP)
- Check `includes/db.php` has correct credentials
- XAMPP default: host=localhost, user=root, password=(empty)

### "Invalid username or password"
- Run the hash.php step to regenerate passwords
- Make sure you ran both SQL files

### "No such file" errors
- Make sure `includes/db.php` exists (copied from db.example.php)
- Make sure both SQL files were run successfully

### Port conflict (Apache won't start)
- Something else is using port 80 (could be IIS, Skype, or Docker)
- In XAMPP httpd.conf change `Listen 80` to `Listen 8090`
- Then use `http://localhost:8090/gg-support`
