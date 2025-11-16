# Supabase Integration Guide

This guide will help you integrate your Student/Faculty System (SFS) project with Supabase.

## Prerequisites

1. A Supabase account (sign up at https://supabase.com)
2. PHP 7.4 or higher with PDO PostgreSQL extension enabled
3. Your project files

## Step 1: Create a Supabase Project

1. Go to https://supabase.com and sign in
2. Click "New Project"
3. Fill in:
   - **Name**: Your project name (e.g., "sfs-appointment-system")
   - **Database Password**: Create a strong password (save this!)
   - **Region**: Choose the closest region to your users
4. Wait for the project to be created (takes 1-2 minutes)

## Step 2: Get Your Database Connection Details

1. In your Supabase project dashboard, go to **Settings** → **Database**
2. Scroll down to **Connection string** section
3. Select **URI** tab
4. Copy the connection details. You'll see something like:
   ```
   postgresql://postgres:[YOUR-PASSWORD]@db.xxxxx.supabase.co:5432/postgres
   ```

5. Extract the following information:
   - **Host**: `db.xxxxx.supabase.co` (the part after `@` and before `:5432`)
   - **Port**: `5432`
   - **Database**: `postgres`
   - **User**: `postgres`
   - **Password**: The password you set when creating the project

## Step 3: Configure Environment Variables

1. Copy the example environment file:
   ```bash
   cp config/env.example .env
   ```

2. Edit the `.env` file in your project root and fill in your Supabase credentials:
   ```env
   SUPABASE_DB_HOST=db.xxxxx.supabase.co
   SUPABASE_DB_PORT=5432
   SUPABASE_DB_NAME=postgres
   SUPABASE_DB_USER=postgres
   SUPABASE_DB_PASSWORD=your_actual_password_here
   SUPABASE_DB_SSLMODE=require
   ```

   **Important**: Replace `xxxxx` with your actual Supabase project reference ID.

## Step 4: Set Up the Database Schema

1. In Supabase dashboard, go to **SQL Editor**
2. Click **New Query**
3. Open the file `supabase/schema.sql` from your project
4. Copy the entire contents
5. Paste it into the SQL Editor
6. Click **Run** (or press Ctrl+Enter)
7. Verify that all tables were created successfully

The schema includes:
- `users` table (with phone field)
- `admins` table
- `offices` table
- `appointments` table (with all required fields)
- `appointment_offices` table
- `feedback` table
- `office_profile_events` table
- Automatic triggers for `updated_at` timestamps

## Step 5: Test the Connection

Create a test file `test_connection.php` in your project root:

```php
<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo = require __DIR__ . '/config/db.php';
    echo "✅ Successfully connected to Supabase!\n";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "Users in database: " . $result['count'] . "\n";
} catch (Exception $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
}
?>
```

Run it:
```bash
php test_connection.php
```

If you see "✅ Successfully connected to Supabase!", you're good to go!

## Step 6: Enable PostgreSQL Extension in PHP

Make sure your PHP installation has the PostgreSQL PDO extension enabled:

**On Windows (XAMPP/WAMP):**
1. Open `php.ini`
2. Find the line: `;extension=pdo_pgsql`
3. Remove the semicolon: `extension=pdo_pgsql`
4. Restart your web server

**On Linux:**
```bash
sudo apt-get install php-pgsql
sudo systemctl restart apache2  # or nginx
```

**Verify:**
```bash
php -m | grep pdo_pgsql
```

## Step 7: Update Your Code (Already Done)

The following files have been updated to work with Supabase:

✅ `config/db.php` - Already configured for Supabase PostgreSQL
✅ `google_callback.php` - Updated to use PDO instead of MySQLi
✅ `supabase/schema.sql` - Updated to match your code requirements

## Step 8: Security Best Practices

1. **Never commit `.env` file to Git**
   - Add `.env` to your `.gitignore` file
   - Only commit `config/env.example` as a template

2. **Use Row Level Security (RLS) in Supabase**
   - Go to **Authentication** → **Policies** in Supabase dashboard
   - Set up policies to restrict database access based on user roles

3. **Use Environment Variables in Production**
   - Don't hardcode credentials in your code
   - Use your hosting provider's environment variable system

## Step 9: Migrate Existing Data (If Applicable)

If you have existing data in MySQL/MariaDB, you'll need to:

1. Export your data from MySQL
2. Convert the SQL syntax to PostgreSQL
3. Import into Supabase using the SQL Editor

**Note**: The schema has been updated to match your code, so column names should align correctly.

## Troubleshooting

### Connection Refused
- Check that your `.env` file has the correct credentials
- Verify the host doesn't include `https://` or `http://`
- Ensure SSL mode is set to `require`

### SSL Connection Error
- Make sure `SUPABASE_DB_SSLMODE=require` in your `.env`
- Some PHP installations may need additional SSL certificates

### Column Not Found Errors
- Make sure you ran the updated `supabase/schema.sql`
- Check that all columns match between schema and code

### Password Verification Fails
- The schema uses `password` column (not `password_hash`)
- Make sure passwords are hashed using `password_hash()` in PHP

## Additional Resources

- [Supabase Documentation](https://supabase.com/docs)
- [PostgreSQL PHP PDO Documentation](https://www.php.net/manual/en/ref.pdo-pgsql.php)
- [Supabase SQL Editor Guide](https://supabase.com/docs/guides/database/tables)

## Support

If you encounter issues:
1. Check the Supabase dashboard logs
2. Enable PHP error reporting: `error_reporting(E_ALL); ini_set('display_errors', 1);`
3. Check your PHP error logs
4. Verify your `.env` file is in the correct location

---

**Your project is now ready to use Supabase!** 🎉

