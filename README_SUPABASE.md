# 🚀 Supabase Integration - Quick Start

Your project is configured for Supabase with session pooler connection string:
```
postgresql://postgres.ndnoevxzgczvyghaktxn:[YOUR_PASSWORD]@aws-1-ap-southeast-1.pooler.supabase.com:5432/postgres
```

## ⚡ Quick Setup (3 Steps)

### 1️⃣ Create .env File
```bash
php create_env.php
```
Then edit `.env` and replace `YOUR_SUPABASE_PASSWORD` with your actual password.

### 2️⃣ Run Database Schema
- Go to Supabase Dashboard → SQL Editor
- Copy contents of `supabase/schema.sql`
- Paste and Run

### 3️⃣ Test Connection
```bash
php test_connection.php
```
Or open in browser: `http://localhost/sfs/test_connection.php`

## ✅ What's Already Done

- ✅ Database connection configured (`config/db.php`)
- ✅ Schema updated to match your code (`supabase/schema.sql`)
- ✅ Google OAuth updated for Supabase (`google_callback.php`)
- ✅ All PHP files use Supabase connection

## 📋 Your Connection Details

- **Host**: `aws-1-ap-southeast-1.pooler.supabase.com`
- **Port**: `5432`
- **Database**: `postgres`
- **User**: `postgres.ndnoevxzgczvyghaktxn`
- **Password**: [Set in .env file]
- **Connection Type**: Session Pooler (IPv4 compatible)

## 📚 Full Documentation

- **Detailed Setup**: `SETUP_INSTRUCTIONS.md`
- **Integration Guide**: `SUPABASE_INTEGRATION_GUIDE.md`
- **Quick Reference**: `QUICK_START.md`

## 🔧 Enable PHP PostgreSQL Extension

**Windows (XAMPP):**
1. Edit `php.ini`
2. Uncomment: `extension=pdo_pgsql`
3. Restart Apache

**Linux:**
```bash
sudo apt-get install php-pgsql
sudo systemctl restart apache2
```

## 🎯 Next Steps

1. Run `php create_env.php`
2. Add your password to `.env`
3. Run schema in Supabase SQL Editor
4. Test with `php test_connection.php`
5. Start using your app!

---

**Need help?** Check `SETUP_INSTRUCTIONS.md` for detailed troubleshooting.

