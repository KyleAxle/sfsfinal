# 🔐 Google Login Setup Guide

Your application already has Google OAuth login functionality built in! Follow these steps to enable it.

## ✅ What's Already Done

- ✅ Google API PHP Client library installed
- ✅ `google_login.php` - Initiates Google OAuth flow
- ✅ `google_callback.php` - Handles OAuth callback and user creation/login
- ✅ Login page has "Sign in with Google" link
- ✅ Automatic user registration for new Google users
- ✅ Session management integrated

## 🚀 Setup Steps

### Step 1: Create Google OAuth Credentials

1. **Go to Google Cloud Console**
   - Visit: https://console.cloud.google.com/
   - Sign in with your Google account

2. **Create or Select a Project**
   - Click the project dropdown at the top
   - Click "New Project" or select an existing one
   - Give it a name (e.g., "CJC School Services")

3. **Enable Google+ API**
   - Go to: https://console.cloud.google.com/apis/library
   - Search for "Google+ API" or "People API"
   - Click on it and click "Enable"

4. **Create OAuth 2.0 Credentials**
   - Go to: https://console.cloud.google.com/apis/credentials
   - Click "Create Credentials" → "OAuth client ID"
   - If prompted, configure the OAuth consent screen first:
     - User Type: External (unless you have a Google Workspace)
     - App name: "CJC School Frontline Services"
     - User support email: Your email
     - Developer contact: Your email
     - Click "Save and Continue" through the steps
   - Application type: **Web application**
   - Name: "CJC School Services Web Client"
   - **Authorized redirect URIs**: Add these:
     ```
     http://localhost:8000/google_callback.php
     http://localhost/sfs/google_callback.php
     ```
     (Add both if you're not sure which port you're using)
   - Click "Create"

5. **Copy Your Credentials**
   - You'll see a popup with:
     - **Client ID** (looks like: `123456789-abcdefg.apps.googleusercontent.com`)
     - **Client Secret** (looks like: `GOCSPX-abcdefghijklmnop`)
   - **Save these!** You won't see the secret again

### Step 2: Add Credentials to .env File

1. Open your `.env` file in the project root
2. Find the Google OAuth section (or add it if missing):
   ```env
   # Google OAuth Credentials
   GOOGLE_CLIENT_ID=your-client-id-here
   GOOGLE_CLIENT_SECRET=your-client-secret-here
   GOOGLE_REDIRECT_URI=http://localhost:8000/google_callback.php
   ```

3. Replace the placeholder values:
   - `your-client-id-here` → Your actual Client ID
   - `your-client-secret-here` → Your actual Client Secret
   - Update the redirect URI if you're using a different port/domain

### Step 3: Test Google Login

1. **Restart your PHP server** (if running)
   ```bash
   # Stop the server (Ctrl+C) and restart
   php -S localhost:8000
   ```

2. **Go to your login page**
   - Open: `http://localhost:8000/login.html`
   - Click "Sign in with Google"

3. **What should happen:**
   - You'll be redirected to Google's login page
   - After signing in, you'll be asked to grant permissions
   - You'll be redirected back to your app
   - If it's your first time, you'll be automatically registered
   - You'll be logged in and redirected to `proto2.html`

## 🔧 Troubleshooting

### "Google Sign-in Not Configured" Error
- ✅ Make sure `.env` file has `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`
- ✅ Restart your PHP server after updating `.env`
- ✅ Check that the values don't have extra spaces or quotes

### "Redirect URI Mismatch" Error
- ✅ Make sure the redirect URI in Google Console **exactly matches** the one in your `.env` file
- ✅ Check if you're using `localhost:8000` or `localhost/sfs`
- ✅ Add both URIs to Google Console if unsure

### "Access Denied" or "Error 400"
- ✅ Make sure you've enabled the Google+ API or People API
- ✅ Check that your OAuth consent screen is configured
- ✅ Verify the Client ID and Secret are correct

### User Not Created After Google Login
- ✅ Check your database connection (run `php test_connection.php`)
- ✅ Verify the `users` table exists and has `password_hash` column
- ✅ Check PHP error logs for database errors

## 📝 For Production Deployment

When deploying to a real server:

1. **Update Redirect URI in Google Console**
   - Add your production URL: `https://yourdomain.com/google_callback.php`
   - Keep localhost URIs for development

2. **Update .env File**
   ```env
   GOOGLE_REDIRECT_URI=https://yourdomain.com/google_callback.php
   ```

3. **Update OAuth Consent Screen**
   - Add your production domain to authorized domains
   - Submit for verification if needed (for public apps)

## 🎯 How It Works

1. User clicks "Sign in with Google" → `google_login.php`
2. Redirects to Google OAuth page
3. User grants permission → Google redirects to `google_callback.php`
4. Callback receives authorization code
5. Exchanges code for user info (email, name)
6. Checks if user exists in database
7. If new user: Creates account automatically
8. If existing user: Logs them in
9. Sets session variables and redirects to dashboard

## ✅ Current Status

- [ ] Google OAuth credentials created
- [ ] Credentials added to `.env` file
- [ ] Server restarted
- [ ] Google login tested successfully

---

**Need help?** Check the error messages - they usually tell you exactly what's wrong!

