# Troubleshooting Database Connection Issues

## Error: "No connection could be made because the target machine actively refused it"

This error means MySQL is **not running** or not accessible.

## ✅ Quick Fix Steps

### Step 1: Start MySQL in XAMPP
1. **Open XAMPP Control Panel**
   - Look for the XAMPP icon in your system tray or desktop
   - Or search "XAMPP" in Windows Start menu

2. **Start MySQL Service**
   - Find the **MySQL** row in the control panel
   - Click the **Start** button
   - Wait until it shows **"Running"** (green background)

### Step 2: Create the Database
1. **Open your browser**
2. **Go to:** `http://localhost/Perfumes Management System/setup_database.php`
3. **Wait for success message** - this will create all tables automatically

### Step 3: Test Your Application
- Go back to: `http://localhost/Perfumes Management System/login.php`
- It should work now!

---

## 🔍 Common Issues & Solutions

### Issue 1: MySQL Won't Start
**Symptoms:** Clicking Start doesn't work, or it stops immediately

**Solutions:**
- **Port 3306 is in use:**
  - Open XAMPP Control Panel
  - Click "Config" next to MySQL
  - Select "my.ini"
  - Change port from 3306 to 3307 (or another free port)
  - Update `db_connect.php` port if needed

- **Another MySQL instance running:**
  - Open Task Manager (Ctrl+Shift+Esc)
  - Look for "mysqld.exe" or "mysql.exe"
  - End those processes
  - Try starting MySQL again

### Issue 2: "Access Denied" Error
**Solution:** Check your MySQL root password
- Default XAMPP password is empty (blank)
- If you set a password, update `includes/db_connect.php`

### Issue 3: Database Exists But Still Getting Errors
**Solution:** Check table structure
- Open phpMyAdmin: `http://localhost/phpmyadmin`
- Select `perfume_db` database
- Check if all tables exist (users, products, orders, etc.)
- If tables are missing, run `setup_database.php` again

---

## 🛠️ Manual Database Setup (Alternative)

If the setup script doesn't work:

1. **Open phpMyAdmin:** `http://localhost/phpmyadmin`

2. **Create Database:**
   - Click "New" in left sidebar
   - Database name: `perfume_db`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import SQL (if you have a .sql file):**
   - Select `perfume_db` database
   - Click "Import" tab
   - Choose your SQL file
   - Click "Go"

---

## 📞 Still Having Issues?

Check these:

1. ✅ **XAMPP Apache is running** (required for PHP)
2. ✅ **MySQL is running** (green in XAMPP)
3. ✅ **Port 3306 is not blocked** by firewall
4. ✅ **No other MySQL services** running (check Services)
5. ✅ **Database exists** (check phpMyAdmin)

---

## 🔐 Security Note

**After setup, delete these files for security:**
- `setup_database.php` (after database is created)
- `TROUBLESHOOTING.md` (optional, but recommended)

---

## Quick Test

To verify MySQL is working:
1. Open Command Prompt
2. Type: `mysql -u root -p`
3. Press Enter (leave password blank if default)
4. If you see `mysql>`, MySQL is working!



