# 📥 How to Get libpq.dll for PostgreSQL Extensions

## Why You Need This

The PostgreSQL PHP extensions (`php_pdo_pgsql.dll`, `php_pgsql.dll`) require `libpq.dll` (PostgreSQL client library) to function. Without it, the extensions won't load even if they're enabled in `php.ini`.

## 🎯 Method 1: Download from PostgreSQL Official Site (Recommended)

1. **Go to PostgreSQL Downloads:**
   - https://www.postgresql.org/download/windows/

2. **Download "Command Line Tools" or "Binaries":**
   - Look for "PostgreSQL Binaries" or "Command Line Tools"
   - OR download the full installer (you can choose "Command Line Tools" only during install)

3. **Extract libpq.dll:**
   - The DLL will be in the `bin` folder
   - Copy `libpq.dll` to: `C:\xampp\php\`

4. **Copy dependencies (if present):**
   - `libintl-8.dll`
   - `libiconv-2.dll`
   - `libssl-*.dll`
   - `libcrypto-*.dll`

## 🎯 Method 2: Use Pre-compiled Binaries

1. **Download from GitHub:**
   - https://github.com/PostgreSQL/pgbuildtools/releases
   - Look for Windows binaries

2. **OR Search for:**
   - "libpq.dll windows download"
   - "PostgreSQL client library windows"

3. **Copy to PHP directory:**
   ```
   C:\xampp\php\libpq.dll
   ```

## 🎯 Method 3: Install PostgreSQL (Full Install)

1. **Download PostgreSQL installer:**
   - https://www.postgresql.org/download/windows/

2. **Install with "Command Line Tools":**
   - During installation, select "Command Line Tools"
   - This installs `libpq.dll` to System32

3. **No need to copy** - Windows will find it automatically

## ✅ After Installing

1. **Restart PHP server:**
   ```bash
   # Stop current server (Ctrl+C)
   php -S localhost:8000
   ```

2. **Test:**
   ```
   http://localhost:8000/check_driver_web.php
   ```

3. **Should now work!** ✅

## 🔍 Verify Installation

Run this command:
```bash
php -r "if (extension_loaded('pdo_pgsql')) { echo 'SUCCESS'; } else { echo 'FAILED - libpq.dll might be missing'; }"
```

Or check if file exists:
```bash
dir C:\xampp\php\libpq.dll
```

---

**Quick Link:** https://www.postgresql.org/download/windows/

