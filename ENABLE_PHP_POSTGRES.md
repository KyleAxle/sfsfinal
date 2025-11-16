# Enable PHP PostgreSQL Extension

The connection test shows: **"could not find driver"** - this means PHP PostgreSQL extension is not enabled.

## ✅ .env File Created Successfully!

Your `.env` file is ready with your Supabase credentials.

## 🔧 Enable PHP PostgreSQL Extension

### For Windows (XAMPP/WAMP):

1. **Find your php.ini file:**
   - Usually located at: `C:\xampp\php\php.ini` or `C:\wamp64\bin\php\php8.x\php.ini`
   - Or run: `php --ini` to find the location

2. **Edit php.ini:**
   - Open `php.ini` in a text editor (as Administrator if needed)
   - Search for: `;extension=pdo_pgsql`
   - Remove the semicolon: `extension=pdo_pgsql`
   - Also search for: `;extension=pgsql` and uncomment it: `extension=pgsql`

3. **Save and restart:**
   - Save the file
   - Restart Apache/XAMPP/WAMP

4. **Verify it's enabled:**
   ```bash
   php -m | findstr pdo_pgsql
   ```
   You should see `pdo_pgsql` in the output.

### Alternative: Check if extension files exist

If the extensions don't exist, you may need to:
1. Download PHP PostgreSQL DLL files
2. Place them in your PHP `ext` folder
3. Enable in php.ini

## 🧪 Test Again

After enabling the extension and restarting Apache:

```bash
php test_connection.php
```

Or open in browser: `http://localhost/sfs/test_connection.php`

## 📋 Next Steps After Extension is Enabled

1. ✅ .env file is ready (already done)
2. ⏳ Enable PHP PostgreSQL extension (you're here)
3. ⏳ Run database schema in Supabase SQL Editor
4. ⏳ Test connection

## 🔍 Quick Check

Run this to see if extension is loaded:
```bash
php -r "echo extension_loaded('pdo_pgsql') ? 'Enabled' : 'Not enabled';"
```

If it says "Not enabled", follow the steps above.

