# 🚀 Complete Beginner's Guide to Deploying Your App

Welcome! This guide will walk you through deploying your SFS Appointment System step-by-step. Don't worry if you're new to this - we'll explain everything!

## 📋 What You'll Need (Before Starting)

1. **GitHub Account** - Free account at https://github.com (like Google Drive for code)
2. **Railway Account** - Free account at https://railway.app (hosts your website online)
3. **Supabase Account** - You already have this (your database)
4. **Git Software** - We'll help you install this if needed

---

## Part 1: Installing Git (If You Don't Have It)

**What is Git?** Git is a tool that helps you save and share your code.

### Step 1: Check if Git is Already Installed

1. Open **Command Prompt** (Windows) or **Terminal** (Mac/Linux)
   - Windows: Press `Win + R`, type `cmd`, press Enter
   - Mac: Press `Cmd + Space`, type `Terminal`, press Enter

2. Type this command and press Enter:
   ```bash
   git --version
   ```

3. **If you see a version number** (like `git version 2.40.0`): ✅ You're good! Skip to Part 2.

4. **If you see an error**: You need to install Git. Continue below.

### Step 2: Install Git

1. Go to https://git-scm.com/download/win (Windows) or https://git-scm.com/download/mac (Mac)
2. Download the installer
3. Run the installer and click "Next" through all steps (default settings are fine)
4. After installation, **close and reopen** your Command Prompt/Terminal
5. Type `git --version` again to verify it's installed

---

## Part 2: Creating a GitHub Account and Repository

**What is GitHub?** GitHub is like Google Drive, but for code. It stores your project online so Railway can access it.

### Step 1: Create GitHub Account (If You Don't Have One)

1. Go to https://github.com/signup
2. Enter your email, create a password, and choose a username
3. Verify your email address
4. Complete the setup

### Step 2: Create a New Repository

**What is a Repository?** Think of it as a folder for your project on GitHub.

1. Go to https://github.com/new
2. **Repository name**: Type something like `sfs-appointment-system` (no spaces, use hyphens)
3. **Description** (optional): "Appointment Management System for CJC"
4. **Visibility**: Choose "Public" (free) or "Private" (if you have GitHub Pro)
5. **IMPORTANT**: Do NOT check any of these boxes:
   - ❌ Add a README file
   - ❌ Add .gitignore
   - ❌ Choose a license
   
   (We already have these files!)
6. Click the green **"Create repository"** button

### Step 3: Copy Your Repository URL

After creating the repository, you'll see a page with setup instructions. Look for a green button that says "Code" and click it. Copy the HTTPS URL (it looks like `https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git`). **Save this URL** - you'll need it soon!

---

## Part 3: Uploading Your Code to GitHub

**What we're doing**: We're copying your project files from your computer to GitHub.

### Step 1: Open Command Prompt in Your Project Folder

**Option A: Using File Explorer (Easiest)**
1. Open File Explorer (Windows) or Finder (Mac)
2. Navigate to: `C:\Users\cuber\OneDrive\Desktop\SFSFINAL\sfs`
3. Click in the address bar and type `cmd` (Windows) or right-click and select "New Terminal at Folder" (Mac)
4. Press Enter - this opens Command Prompt in the right folder

**Option B: Using Command Prompt**
1. Open Command Prompt
2. Type this command and press Enter:
   ```bash
   cd C:\Users\cuber\OneDrive\Desktop\SFSFINAL\sfs
   ```

### Step 2: Initialize Git (First Time Only)

**What this does**: Tells Git to start tracking changes in this folder.

Type this command and press Enter:
```bash
git init
```

You should see: `Initialized empty Git repository...`

### Step 3: Check What Files Will Be Uploaded

Type this command:
```bash
git status
```

You'll see a list of files. This is normal! It shows all your project files.

### Step 4: Add All Files to Git

**What this does**: Tells Git which files to save.

Type this command:
```bash
git add .
```

The `.` means "all files in this folder". You won't see any output - that's normal!

### Step 5: Create Your First Commit

**What is a Commit?** Think of it as saving a snapshot of your project.

Type this command:
```bash
git commit -m "Initial commit: SFS Appointment System"
```

**Note**: If this is your first time using Git, you might need to set your name and email first:
```bash
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"
```

Then run the commit command again.

### Step 6: Connect to Your GitHub Repository

**What this does**: Links your local project to your GitHub repository.

Replace `YOUR_USERNAME` and `YOUR_REPO_NAME` with your actual GitHub username and repository name:

```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
```

**Example**: If your username is `johnsmith` and repo is `sfs-appointment-system`:
```bash
git remote add origin https://github.com/johnsmith/sfs-appointment-system.git
```

### Step 7: Push Your Code to GitHub

**What this does**: Uploads all your files to GitHub.

Type these commands one by one:
```bash
git branch -M main
git push -u origin main
```

### Step 8: Authenticate with GitHub

When you run `git push`, you'll be asked for credentials:

1. **Username**: Enter your GitHub username
2. **Password**: **DO NOT use your GitHub password!** Instead, use a **Personal Access Token**

#### How to Create a Personal Access Token:

1. Go to https://github.com/settings/tokens
2. Click **"Generate new token"** → **"Generate new token (classic)"**
3. Give it a name like "Railway Deployment"
4. Select expiration (90 days or "No expiration")
5. Check the box **"repo"** (this gives access to repositories)
6. Scroll down and click **"Generate token"**
7. **COPY THE TOKEN IMMEDIATELY** (you won't see it again!)
8. Paste this token when Git asks for your password

**If you get an error**: Make sure you copied the token correctly and that it has "repo" permissions.

### Step 9: Verify Upload

1. Go to your GitHub repository page: `https://github.com/YOUR_USERNAME/YOUR_REPO_NAME`
2. You should see all your files! ✅

**Common Issues:**
- **"remote origin already exists"**: Run `git remote remove origin` first, then try again
- **"Authentication failed"**: Make sure you're using a Personal Access Token, not your password

---

## Part 4: Deploying to Railway

**What is Railway?** Railway is a service that takes your code and makes it available on the internet as a website.

### Step 1: Create Railway Account

1. Go to https://railway.app
2. Click **"Start a New Project"** or **"Login"**
3. Sign up with your GitHub account (easiest option!)
4. Authorize Railway to access your GitHub

### Step 2: Create a New Project

1. In Railway dashboard, click **"New Project"**
2. Select **"Deploy from GitHub repo"**
3. You'll see a list of your GitHub repositories
4. Click on your repository (`sfs-appointment-system` or whatever you named it)
5. Railway will start setting up automatically

### Step 3: Add Environment Variables

**What are Environment Variables?** These are secret settings (like passwords) that your app needs to work. We store them securely in Railway instead of in your code.

1. In Railway, click on your project
2. Click on the **"Variables"** tab (or look for a "Variables" button)
3. Click **"New Variable"** or **"Raw Editor"**
4. Add each of these variables one by one (click "Add" after each):

```
SUPABASE_DB_HOST=your-supabase-host.supabase.co
```

**Where to find your Supabase credentials:**
- Go to your Supabase project dashboard
- Click "Settings" → "Database"
- Copy the connection details

**Add these variables:**
- `SUPABASE_DB_HOST` - Your Supabase host (from Supabase dashboard)
- `SUPABASE_DB_PORT` - Usually `5432`
- `SUPABASE_DB_NAME` - Usually `postgres`
- `SUPABASE_DB_USER` - Your Supabase database user
- `SUPABASE_DB_PASSWORD` - Your Supabase database password
- `SUPABASE_DB_SSLMODE` - Set to `require`
- `GOOGLE_CLIENT_ID` - Your Google OAuth Client ID (if you have one)
- `GOOGLE_CLIENT_SECRET` - Your Google OAuth Secret (if you have one)
- `GOOGLE_REDIRECT_URI` - We'll update this later, use: `https://placeholder.railway.app/google_callback.php` for now
- `GROQ_API_KEY` - (Optional) Your Groq API key if you're using AI features
- `USE_PHP_FALLBACK` - Set to `false`

**Important Tips:**
- No spaces around the `=` sign
- Don't use quotes around values
- Copy values exactly as they appear

### Step 4: Wait for Deployment

1. Railway will automatically start building your app
2. You'll see a progress indicator
3. This can take 2-5 minutes
4. Watch the logs - you'll see messages like "Building..." and "Deploying..."

**What to look for:**
- ✅ Green checkmark = Success!
- ❌ Red X = Error (check the logs)

### Step 5: Get Your Website URL

1. After deployment succeeds, click on your service
2. Go to **"Settings"** tab
3. Scroll down to **"Domains"** section
4. Click **"Generate Domain"**
5. Copy your domain (it looks like `your-app-name.railway.app`)

**Save this URL!** This is your live website address.

### Step 6: Update Google OAuth Settings (If Using Google Login)

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click the hamburger menu (☰) → **"APIs & Services"** → **"Credentials"**
3. Find your OAuth 2.0 Client ID and click the edit icon (pencil)
4. Under **"Authorized redirect URIs"**, click **"Add URI"**
5. Add: `https://YOUR-RAILWAY-DOMAIN.railway.app/google_callback.php`
   (Replace `YOUR-RAILWAY-DOMAIN` with your actual Railway domain)
6. Click **"Save"**

### Step 7: Update Railway Environment Variable

1. Go back to Railway → Your Project → Variables
2. Find `GOOGLE_REDIRECT_URI`
3. Click to edit it
4. Change the value to: `https://YOUR-RAILWAY-DOMAIN.railway.app/google_callback.php`
5. Save

Railway will automatically redeploy with the new setting.

---

## Part 5: Setting Up Your Database

**What we're doing**: Creating the database tables your app needs.

1. Go to your **Supabase Dashboard**
2. Click **"SQL Editor"** in the left sidebar
3. Click **"New query"**
4. Open the file `supabase/schema.sql` from your project folder
5. Copy ALL the contents of that file
6. Paste it into the Supabase SQL Editor
7. Click **"Run"** (or press Ctrl+Enter)
8. You should see "Success. No rows returned" - this means it worked!

**Verify it worked:**
- In Supabase, go to **"Table Editor"**
- You should see tables like `users`, `appointments`, `offices`, etc.

---

## Part 6: Testing Your Live Website

1. Open your Railway domain in a browser: `https://your-app.railway.app`
2. Try these:
   - ✅ Visit the homepage
   - ✅ Try logging in
   - ✅ Test creating an account
   - ✅ Test booking an appointment (if logged in)

**If something doesn't work:**
- Check Railway logs (click "Deployments" → "View Logs")
- Check that all environment variables are set correctly
- Verify database tables were created in Supabase

---

## 🔧 Troubleshooting Common Problems

### Problem: "Git is not recognized"

**Solution**: Git is not installed or not in your PATH. Reinstall Git and make sure to check "Add to PATH" during installation.

### Problem: "Authentication failed" when pushing to GitHub

**Solution**: 
- Make sure you're using a Personal Access Token, not your password
- The token needs "repo" permissions
- Tokens expire - create a new one if yours is old

### Problem: "Remote origin already exists"

**Solution**: 
```bash
git remote remove origin
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git
```

### Problem: Railway build fails

**Solution**:
- Check Railway logs for error messages
- Make sure `nixpacks.toml` file is in your project root
- Verify all environment variables are set (no empty values)

### Problem: Website shows "500 Error" or "Internal Server Error"

**Solution**:
- Check Railway logs
- Verify all environment variables are correct
- Make sure database tables exist in Supabase
- Check that database credentials are correct

### Problem: Database connection fails

**Solution**:
- Double-check all `SUPABASE_DB_*` variables in Railway
- Make sure `SUPABASE_DB_SSLMODE=require`
- Verify your Supabase project is active
- Check Supabase dashboard → Settings → Database for correct credentials

### Problem: Google Login doesn't work

**Solution**:
- Make sure redirect URI in Google Console matches Railway domain exactly
- Check `GOOGLE_REDIRECT_URI` in Railway variables
- Ensure you're using `https://` (not `http://`)
- Wait a few minutes after updating - changes can take time to propagate

---

## 📝 Updating Your Website After Making Changes

**What we're doing**: When you make changes to your code, you need to upload them again.

1. Make your changes to files on your computer
2. Open Command Prompt in your project folder
3. Run these commands:

```bash
# Add all changed files
git add .

# Save the changes with a message
git commit -m "Description of what you changed"

# Upload to GitHub
git push origin main
```

4. Railway will **automatically** detect the changes and redeploy (usually takes 2-3 minutes)
5. Check Railway dashboard to see the new deployment

---

## 🎉 Congratulations!

Your app is now live on the internet! Share your Railway URL with others.

---

## 💡 Tips for Beginners

- **Save your credentials**: Keep a secure note of all passwords and tokens
- **Check logs first**: When something breaks, always check Railway logs
- **Test locally first**: Make changes and test on `localhost` before pushing
- **One change at a time**: Make small changes so you know what broke if something goes wrong
- **Use descriptive commit messages**: "Fixed login bug" is better than "update"

---

## 🆘 Need More Help?

- **Railway Documentation**: https://docs.railway.app
- **Railway Discord**: https://discord.gg/railway (very helpful community!)
- **GitHub Help**: https://docs.github.com
- **Git Tutorial**: https://learngitbranching.js.org (interactive learning)

---

## ✅ Quick Checklist

Before deploying, make sure:
- [ ] Git is installed and working
- [ ] GitHub account created
- [ ] Repository created on GitHub
- [ ] Code pushed to GitHub successfully
- [ ] Railway account created
- [ ] Project connected to GitHub repo
- [ ] All environment variables added to Railway
- [ ] Database tables created in Supabase
- [ ] Railway domain generated
- [ ] Google OAuth redirect URI updated (if using)
- [ ] Website tested and working

---

**Remember**: Everyone starts as a beginner. Take your time, read carefully, and don't hesitate to ask for help! 🚀
