# 🚀 How to Run Your Project

## Quick Start

### Option 1: Full Check & Start (Recommended)
**Double-click:** `run_project.bat`

This will:
- ✅ Check PHP installation
- ✅ Check PostgreSQL extension
- ✅ Check .env file
- ✅ Test database connection
- ✅ Start the server
- ✅ Open browser automatically

### Option 2: Quick Start
**Double-click:** `START.bat`

This will:
- Start the server immediately
- Open browser automatically

## 📋 What You'll See

After running `run_project.bat`, you'll see:
1. PHP version check
2. PostgreSQL extension status
3. .env file check
4. Database connection test
5. Server starting on port 8000

## 🌐 Access Your Application

Once the server starts, your browser will open automatically to:
**http://localhost:8000**

### Main Pages:
- **Registration**: http://localhost:8000/register.html
- **Login**: http://localhost:8000/login.html
- **Main App**: http://localhost:8000/proto2.html
- **Client Dashboard**: http://localhost:8000/client_dashboard.html

## ⚠️ Troubleshooting

### If you see "PostgreSQL extension NOT enabled":
1. The batch file will open php.ini location
2. Edit php.ini and remove semicolons from:
   - `extension=pdo_pgsql`
   - `extension=pgsql`
3. Restart Apache in XAMPP
4. Run the batch file again

### If you see "Database connection failed":
1. Check your `.env` file has correct Supabase credentials
2. Make sure PostgreSQL extension is enabled
3. Run: `php test_connection.php` for details

## 🛑 To Stop the Server

Press `Ctrl+C` in the terminal window

## 🔄 Restart

Just double-click `run_project.bat` or `START.bat` again!

---

**That's it! Your project is running on Supabase!** 🎉

