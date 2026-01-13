# 🚨 URGENT: Enable PHP PostgreSQL Extension

## The Error
```
Database connection failed: could not find driver
```

This means PHP PostgreSQL extension is **NOT enabled**.

## ✅ Quick Fix (Windows)

### Step 1: Find Your PHP Installation

Run this in Command Prompt:
```bash
where php
```

Common locations:
- XAMPP: `C:\xampp\php\php.exe`
- WAMP: `C:\wamp64\bin\php\php8.x\php.exe`
- Standalone: `C:\php\php.exe`

### Step 2: Find php.ini File

Run this:
```bash
php --ini
```

Look for: **Loaded Configuration File**

### Step 3: Edit php.ini

1. Open the `php.ini` file in Notepad (as Administrator)
2. Search for: `;extension=pdo_pgsql`
3. Remove the semicolon: `extension=pdo_pgsql`
4. Also search for: `;extension=pgsql`
5. Remove the semicolon: `extension=pgsql`
6. **Save the file**

### Step 4: Restart Your Server

**If using XAMPP:**
- Stop Apache in XAMPP Control Panel
- Start Apache again

**If using WAMP:**
- Click WAMP icon → Restart All Services

**If using PHP built-in server:**
- Stop it (Ctrl+C)
- Start again: `php -S localhost:8000`

### Step 5: Verify

Run this:
```bash
php -m | findstr pdo_pgsql
```

You should see: `pdo_pgsql`

## 🔍 Alternative: Check if Extension Files Exist

If the extensions don't exist, you may need to:

1. **For XAMPP:**
   - Check if `C:\xampp\php\ext\php_pdo_pgsql.dll` exists
   - If not, download PHP PostgreSQL DLLs

2. **For WAMP:**
   - Right-click WAMP icon → PHP → PHP Extensions
   - Check `php_pdo_pgsql` and `php_pgsql`

## 🚀 After Enabling

Test your connection:
```bash
php test_connection.php
```

Should show: ✅ Successfully connected to Supabase!

## 📝 Step-by-Step (XAMPP Example)

1. Open: `C:\xampp\php\php.ini` (in Notepad as Admin)
2. Press `Ctrl+F` → Search: `pdo_pgsql`
3. Find: `;extension=pdo_pgsql`
4. Change to: `extension=pdo_pgsql` (remove `;`)
5. Find: `;extension=pgsql`
6. Change to: `extension=pgsql` (remove `;`)
7. Save (Ctrl+S)
8. Open XAMPP Control Panel
9. Stop Apache → Start Apache
10. Test: `php test_connection.php`

---

**This is the ONLY thing preventing your Supabase connection from working!**


