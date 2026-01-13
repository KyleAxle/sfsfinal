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

**What we're doing**: We're copying your project files from your computer to GitHub so Railway can access them.

---

### ⚠️ IMPORTANT: Understanding Command Format

**Before we start, you need to know this:**

When you see commands like this in the guide:
```
git init
```

**You should ONLY type**: `git init` (without the backticks or the word "bash")

**DO NOT type**:
- The three backticks (```)
- The word "bash"
- Any markdown formatting

**Just type the actual command** and press Enter!

---

### Step 1: Open Command Prompt in Your Project Folder

**Method 1: Using File Explorer (Easiest for Beginners)**

1. Open **File Explorer** (the folder icon on your taskbar)
2. In the address bar at the top, type: `C:\Users\cuber\OneDrive\Desktop\SFSFINAL\sfs`
3. Press **Enter** - this will take you to your project folder
4. Now, **click in the address bar again** and type: `cmd`
5. Press **Enter** - this opens Command Prompt in the correct folder!

You should see something like:
```
C:\Users\cuber\OneDrive\Desktop\SFSFINAL\sfs>
```

**Method 2: Using Command Prompt Directly**

1. Press the **Windows key + R**
2. Type: `cmd`
3. Press **Enter**
4. In the Command Prompt window, type this EXACTLY (copy and paste if possible):
   ```
   cd C:\Users\cuber\OneDrive\Desktop\SFSFINAL\sfs
   ```
5. Press **Enter**

You should now see:
```
C:\Users\cuber\OneDrive\Desktop\SFSFINAL\sfs>
```

✅ **If you see this, you're in the right place!** If not, check that the folder path is correct.

---

### Step 2: Set Up Git (First Time Only - Do This Once)

**What this does**: Tells Git who you are so it can track your changes.

**Only do this if you've never used Git before on this computer.**

Type these commands ONE AT A TIME (press Enter after each):

**Command 1: Set your name**
```
git config --global user.name "Your Name"
```
*(Replace "Your Name" with your actual name, like "Kyle Paden")*

**Command 2: Set your email**
```
git config --global user.email "your.email@example.com"
```
*(Replace with the email you used for GitHub)*

**Example:**
```
git config --global user.name "Kyle Paden"
git config --global user.email "cuberkyle969@gmail.com"
```

✅ **You only need to do this once!** After this, Git will remember who you are.

---

### Step 3: Initialize Git in Your Project Folder

**What this does**: Tells Git to start tracking this folder and all files in it.

Type this command and press Enter:
```
git init
```

**What you should see:**
```
Initialized empty Git repository in C:/Users/cuber/OneDrive/Desktop/SFSFINAL/sfs/.git/
```

✅ **If you see this message, it worked!**

**If you see "fatal: not a git repository"**: Make sure you're in the correct folder (see Step 1).

---

### Step 4: Check What Files Git Found

**What this does**: Shows you all the files that will be uploaded to GitHub.

Type this command:
```
git status
```

**What you'll see:**
- A list of files under "Untracked files"
- This is NORMAL! It's showing all your project files
- You might see files like: `login.html`, `admin/`, `assets/`, etc.

✅ **This is good!** It means Git found your files.

---

### Step 5: Tell Git to Add All Your Files

**What this does**: Tells Git "I want to save all these files."

Type this command:
```
git add .
```

**Important Notes:**
- The `.` (dot) means "all files in this folder"
- You won't see any output - **this is normal!**
- If you see an error, make sure you're in the right folder

✅ **No error message = it worked!**

---

### Step 6: Save Your Files (Create a Commit)

**What is a Commit?** Think of it like saving a game. You're taking a snapshot of all your files at this moment.

Type this command:
```
git commit -m "Initial commit: SFS Appointment System"
```

**What you should see:**
```
[main (root-commit) abc1234] Initial commit: SFS Appointment System
 X files changed, Y insertions(+)
```

✅ **If you see something like this, it worked!**

**Common Error: "Please tell me who you are"**
- This means you skipped Step 2
- Go back to Step 2 and set your name and email first

---

### Step 7: Connect Your Project to GitHub

**What this does**: Links your local project folder to your GitHub repository.

**First, get your GitHub repository URL:**
1. Go to your GitHub repository page (the one you created in Part 2)
2. Click the green **"Code"** button
3. Make sure **"HTTPS"** is selected (not SSH)
4. Copy the URL (it looks like: `https://github.com/KyleAxle/sfsfinal.git`)

**Now, in Command Prompt, type this command:**

**If you DON'T have a remote yet:**
```
git remote add origin https://github.com/KyleAxle/sfsfinal.git
```
*(Replace with YOUR actual repository URL)*

**If you get "remote origin already exists" error:**
1. First, remove the old remote:
   ```
   git remote remove origin
   ```
2. Then add the correct one:
   ```
   git remote add origin https://github.com/KyleAxle/sfsfinal.git
   ```

**Verify it's correct:**
```
git remote -v
```

**What you should see:**
```
origin  https://github.com/KyleAxle/sfsfinal.git (fetch)
origin  https://github.com/KyleAxle/sfsfinal.git (push)
```

✅ **If you see your repository URL twice, it's correct!**

---

### Step 8: Create a Personal Access Token (For Authentication)

**Why?** GitHub doesn't let you use your password anymore. You need a special token.

**Step 8a: Create the Token**

1. Go to: https://github.com/settings/tokens
2. Click **"Generate new token"** (or "Generate new token (classic)" if you see that option)
3. **Token name**: Type `Railway Deployment` (or any name you want)
4. **Expiration**: Choose:
   - **"90 days"** (safer, but you'll need to create a new one later)
   - **"No expiration"** (easier, but less secure)
5. **Select scopes**: Scroll down and find **"repo"**
   - ✅ **Check the box next to "repo"**
   - This gives the token permission to access your repositories
6. Scroll all the way down and click **"Generate token"** (green button)
7. **IMPORTANT**: You'll see a long string of letters and numbers
   - **COPY THIS IMMEDIATELY!** (Click the copy icon, or select all and Ctrl+C)
   - **You won't be able to see it again!**
   - **Save it somewhere safe** (like a text file or password manager)

**The token looks like:** `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

✅ **Keep this token safe!** You'll need it in the next step.

---

### Step 9: Upload Your Files to GitHub (Push)

**What this does**: Actually uploads all your files to GitHub.

**Step 9a: Rename your branch to "main"**

Type this command:
```
git branch -M main
```

**You won't see any output - that's normal!**

**Step 9b: Push to GitHub**

Type this command:
```
git push -u origin main
```

**What will happen:**
1. Git will ask for your **username**
   - Type your GitHub username (like `KyleAxle`)
   - Press Enter

2. Git will ask for your **password**
   - **DO NOT type your GitHub password!**
   - **Paste your Personal Access Token** (the one you created in Step 8)
   - Press Enter

**Important Notes:**
- When you paste the token, you won't see anything appear (this is normal for security)
- Just paste it and press Enter
- It might take 30 seconds to a few minutes depending on how many files you have

**What you should see:**
```
Enumerating objects: X, done.
Counting objects: 100% (X/X), done.
Writing objects: 100% (X/X), done.
To https://github.com/KyleAxle/sfsfinal.git
 * [new branch]      main -> main
Branch 'main' set up to track remote branch 'main' from 'origin'.
```

✅ **If you see "Writing objects" and "new branch", it worked!**

**Common Errors:**

**Error: "Permission denied" or "403"**
- You're using the wrong username or token
- Make sure you copied the token correctly
- Make sure the token has "repo" permissions
- Try creating a new token

**Error: "remote origin already exists"**
- Run: `git remote remove origin`
- Then run: `git remote add origin https://github.com/KyleAxle/sfsfinal.git`
- Then try pushing again

**Error: "Authentication failed"**
- Make sure you're using the Personal Access Token, not your password
- Make sure the token hasn't expired
- Try creating a new token

---

### Step 10: Verify Your Files Are on GitHub

**What this does**: Confirms that all your files were uploaded successfully.

1. Open your web browser
2. Go to: `https://github.com/KyleAxle/sfsfinal` (replace with your username and repo name)
3. **You should see all your files!** Like:
   - `login.html`
   - `admin/` folder
   - `assets/` folder
   - `config/` folder
   - All your other project files

✅ **If you can see your files on GitHub, congratulations! You did it!**

**If you don't see your files:**
- Wait a minute and refresh the page
- Check that you're looking at the right repository
- Make sure the push completed successfully (check Step 9 for errors)

---

## 🎉 Success! What's Next?

If you can see your files on GitHub, you're ready for the next part: **Deploying to Railway** (Part 4).

**Quick Summary of What You Just Did:**
1. ✅ Set up Git on your computer
2. ✅ Told Git to track your project folder
3. ✅ Saved all your files (created a commit)
4. ✅ Connected your folder to GitHub
5. ✅ Uploaded all files to GitHub

**Remember**: Every time you make changes to your code, you'll need to:
1. `git add .`
2. `git commit -m "Description of changes"`
3. `git push origin main`

But for now, you're done with GitHub! 🎊

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
