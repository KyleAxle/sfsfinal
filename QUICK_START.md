# Quick Start: Supabase Integration

## 🚀 5-Minute Setup

### 1. Get Supabase Credentials
- Go to https://supabase.com → Create/Select Project
- Settings → Database → Copy connection details

### 2. Create `.env` File
```bash
# Copy the example file
cp config/env.example .env
```

Edit `.env` with your Supabase credentials:
```env
SUPABASE_DB_HOST=db.xxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASSWORD=your_password_here
SUPABASE_DB_SSLMODE=require
```

### 3. Run Database Schema
- Open Supabase Dashboard → SQL Editor
- Copy contents of `supabase/schema.sql`
- Paste and Run

### 4. Test Connection
```bash
php -r "require 'config/db.php'; \$pdo = require 'config/db.php'; echo 'Connected!';"
```

### 5. Enable PHP PostgreSQL Extension
**Windows (XAMPP):**
- Edit `php.ini`
- Uncomment: `extension=pdo_pgsql`
- Restart Apache

**Linux:**
```bash
sudo apt-get install php-pgsql
sudo systemctl restart apache2
```

## ✅ Done!

Your project is now connected to Supabase. See `SUPABASE_INTEGRATION_GUIDE.md` for detailed instructions.

