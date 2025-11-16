# Supabase Setup Instructions for Your Project

## ✅ Step 1: Create and Configure .env File

### Option A: Automated (Recommended)

Run this command in your project directory:
```bash
php create_env.php
```

This will create the `.env` file with your Supabase connection details.

### Option B: Manual

Create a file named `.env` in your project root with this content:
```env
SUPABASE_DB_HOST=db.ndnoevxzgczvyghaktxn.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASSWORD=YOUR_SUPABASE_PASSWORD
SUPABASE_DB_SSLMODE=require
```

### Then Update Your Password

1. Open the `.env` file in your project root
2. Replace `YOUR_SUPABASE_PASSWORD` with your actual Supabase database password
   - This is the password you set when creating your Supabase project
   - If you forgot it, you can reset it in Supabase Dashboard → Settings → Database

Your `.env` file should look like this:
```env
SUPABASE_DB_HOST=db.ndnoevxzgczvyghaktxn.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASSWORD=your_actual_password_here
SUPABASE_DB_SSLMODE=require
```

## ✅ Step 2: Set Up Database Schema

1. Go to your Supabase Dashboard: https://supabase.com/dashboard
2. Select your project
3. Click on **SQL Editor** in the left sidebar
4. Click **New Query**
5. Open the file `supabase/schema.sql` from your project
6. Copy the entire contents of `supabase/schema.sql`
7. Paste it into the SQL Editor
8. Click **Run** (or press Ctrl+Enter)
9. You should see "Success" message

This will create all the necessary tables:
- `users` (with phone field)
- `admins`
- `offices`
- `appointments` (with all required fields)
- `appointment_offices`
- `feedback`
- `office_profile_events`

## ✅ Step 3: Enable PHP PostgreSQL Extension

### For Windows (XAMPP/WAMP):

1. Open `php.ini` file (usually in `C:\xampp\php\php.ini` or similar)
2. Search for: `;extension=pdo_pgsql`
3. Remove the semicolon to uncomment: `extension=pdo_pgsql`
4. Also uncomment: `extension=pgsql` (if present)
5. Save the file
6. Restart Apache/XAMPP

### For Linux:

```bash
sudo apt-get update
sudo apt-get install php-pgsql
sudo systemctl restart apache2  # or nginx, depending on your setup
```

### Verify Installation:

Open a terminal/command prompt and run:
```bash
php -m | grep pdo_pgsql
```

If you see `pdo_pgsql` in the output, you're good to go!

## ✅ Step 4: Test the Connection

I've already created `test_connection.php` for you! Just:

Open it in your browser: `http://localhost/sfs/test_connection.php`

Or run from command line: `php test_connection.php`

If you see "✅ Successfully connected to Supabase!", everything is working!

## ✅ Step 5: Your Project is Ready!

Once the connection test passes, your project is fully integrated with Supabase. All your PHP files are already configured to use the Supabase connection via `config/db.php`.

### Files Already Updated:
- ✅ `config/db.php` - Configured for Supabase
- ✅ `google_callback.php` - Updated to use Supabase PDO
- ✅ `supabase/schema.sql` - Updated to match your code requirements
- ✅ `.env` - Use `php create_env.php` to create it, then add your password

### What Works Now:
- User registration (`register_process.php`)
- User login (`login_process.php`)
- Admin login (`admin/admin_login_process.php`)
- Appointment creation (`save_appointment.php`)
- Google OAuth login (`google_callback.php`)
- All dashboard queries

## 🔒 Security Reminder

**Important**: The `.env` file contains sensitive credentials. Make sure:
- ✅ It's in your `.gitignore` (already done)
- ✅ Never commit it to Git
- ✅ Don't share it publicly

## 🐛 Troubleshooting

### "Connection refused" or "Could not connect"
- Double-check your password in `.env` file
- Verify the host is correct: `db.ndnoevxzgczvyghaktxn.supabase.co`
- Make sure SSL mode is set to `require`

### "Call to undefined function pg_connect()"
- PHP PostgreSQL extension is not enabled
- Follow Step 3 above to enable it

### "Table does not exist"
- You haven't run the schema yet
- Follow Step 2 to set up the database schema

### "Column 'password' does not exist"
- Make sure you ran the updated `supabase/schema.sql`
- The schema uses `password` (not `password_hash`)

## 📚 Additional Resources

- Full integration guide: `SUPABASE_INTEGRATION_GUIDE.md`
- Quick reference: `QUICK_START.md`
- Supabase Dashboard: https://supabase.com/dashboard

---

**Need Help?** Check the error messages carefully - they usually tell you exactly what's wrong!

