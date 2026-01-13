# 🔧 Fix: Extensions Load in CLI but NOT in Web Server

## 🔍 Problem

Your diagnostic shows:
- ✅ **CLI PHP**: Extensions ARE loaded (`pdo_pgsql`, `pgsql`)
- ❌ **Web Server**: Extensions are NOT loaded

This means the **PHP built-in server** is not loading the extensions, even though CLI PHP can.

## 🎯 Solution: Restart PHP Server Properly

### Step 1: Stop ALL PHP Processes

1. **Close ALL terminal/command prompt windows** running PHP
2. **Press Ctrl+C** in any terminal running `php -S localhost:8000`
3. **Check for running PHP processes:**
   ```bash
   tasklist | findstr php.exe
   ```
   If you see any, kill them:
   ```bash
   taskkill /F /IM php.exe
   ```

### Step 2: Verify PHP Configuration

Run this to make sure CLI PHP is using the correct php.ini:
```bash
php --ini
```

Should show: `C:\xampp\php\php.ini`

### Step 3: Start PHP Server Fresh

**Option A: Use the batch file (Recommended)**
```bash
run_project_fixed.bat
```

**Option B: Manual start**
```bash
cd "C:\Users\Raphael Fate Pagaran\Documents\Capstone\New folder (2)\sfs"
php -S localhost:8000
```

### Step 4: Test Immediately

Open in browser:
```
http://localhost:8000/check_driver_web.php
```

## 🔍 If Still Not Working: Check for Multiple PHP Installations

The web server might be using a different PHP installation than CLI.

### Check Which PHP the Server Uses

1. Create `phpinfo.php`:
   ```php
   <?php phpinfo(); ?>
   ```

2. Start server: `php -S localhost:8000`

3. Open: `http://localhost:8000/phpinfo.php`

4. Look for:
   - **"Loaded Configuration File"** - Should be `C:\xampp\php\php.ini`
   - **"extension_dir"** - Should be `C:\xampp\php\ext`
   - **"pdo_pgsql"** in the list of loaded extensions

### If phpinfo Shows Different PHP

If `phpinfo.php` shows a different PHP installation:
1. Use the **full path** to XAMPP PHP:
   ```bash
   C:\xampp\php\php.exe -S localhost:8000
   ```

2. Or update your batch file to use full path:
   ```batch
   C:\xampp\php\php.exe -S localhost:8000
   ```

## 🚀 Quick Fix Script

I'll create a script that uses the full path to ensure consistency.

## ✅ Expected Result

After restarting with the correct PHP:
- ✅ `check_driver_web.php` shows extensions loaded
- ✅ `test_connection.php` connects successfully
- ✅ Your application works

---

**Most Important:** Make sure you're using the SAME PHP binary for both CLI and web server!

