# Push to GitHub - Setup Guide

## Option 1: Using GitHub Desktop (Easiest)

1. **Download GitHub Desktop:**
   - Go to https://desktop.github.com/
   - Download and install GitHub Desktop

2. **Create Repository on GitHub:**
   - Go to https://github.com/new
   - Name your repository (e.g., "sfs-appointment-system")
   - Don't initialize with README (since you already have files)
   - Click "Create repository"

3. **Clone/Add Repository in GitHub Desktop:**
   - Open GitHub Desktop
   - Click "File" → "Add Local Repository"
   - Browse to: `C:\Users\Raphael Fate Pagaran\Documents\Capstone\New folder (2)\sfs`
   - If it says "This directory does not appear to be a Git repository":
     - Click "Create a Repository"
     - Name: `sfs-appointment-system`
     - Click "Create Repository"

4. **Publish to GitHub:**
   - Click "Publish repository" button
   - Choose your GitHub account
   - Make sure "Keep this code private" is checked (recommended for projects with credentials)
   - Click "Publish repository"

## Option 2: Using Git Command Line

### Step 1: Install Git

1. Download Git for Windows: https://git-scm.com/download/win
2. Install with default settings
3. Restart your terminal/command prompt

### Step 2: Initialize Repository

Open PowerShell or Command Prompt in your project folder:

```bash
cd "C:\Users\Raphael Fate Pagaran\Documents\Capstone\New folder (2)\sfs"
git init
```

### Step 3: Add Files

```bash
git add .
```

### Step 4: Create Initial Commit

```bash
git commit -m "Initial commit: Student/Faculty System with Supabase integration"
```

### Step 5: Create Repository on GitHub

1. Go to https://github.com/new
2. Repository name: `sfs-appointment-system` (or your preferred name)
3. **Don't** initialize with README, .gitignore, or license
4. Click "Create repository"

### Step 6: Connect and Push

GitHub will show you commands. Use these:

```bash
git remote add origin https://github.com/YOUR_USERNAME/sfs-appointment-system.git
git branch -M main
git push -u origin main
```

Replace `YOUR_USERNAME` with your GitHub username.

## ✅ Important: Security Checklist

Before pushing, make sure:

- ✅ `.env` file is in `.gitignore` (already done)
- ✅ No passwords or API keys are hardcoded in files
- ✅ `setup_with_password.php` is deleted (already done)

### Files That Should NOT Be Committed:

- `.env` - Contains database password ✅ (in .gitignore)
- Any files with hardcoded passwords
- `vendor/` folder (if using Composer) ✅ (in .gitignore)

### Files Safe to Commit:

- ✅ All PHP files (except those with passwords)
- ✅ HTML files
- ✅ CSS/JavaScript files
- ✅ `config/db.php` (uses environment variables)
- ✅ `supabase/schema.sql`
- ✅ Documentation files

## 🔒 Recommended: Make Repository Private

When creating the repository on GitHub, check "Private repository" to keep your code secure, especially since it contains database connection details.

## 📝 After Pushing

1. Verify `.env` is not in the repository:
   - Check GitHub → Your repo → Files
   - `.env` should NOT appear

2. Add a README.md (optional):
   - Create `README.md` with project description
   - Don't include passwords or sensitive info

3. Update `.env.example` if needed:
   - Keep `config/env.example` as a template
   - Others can copy it to `.env` and add their own credentials

## 🐛 Troubleshooting

### "Git is not recognized"
- Install Git: https://git-scm.com/download/win
- Restart terminal after installation

### "Permission denied"
- Make sure you're logged into GitHub
- Use HTTPS with personal access token, or SSH keys

### ".env file is showing in GitHub"
- Remove it: `git rm --cached .env`
- Commit: `git commit -m "Remove .env from tracking"`
- Push: `git push`

## 🎯 Quick Commands Reference

```bash
# Check status
git status

# Add all files
git add .

# Commit changes
git commit -m "Your commit message"

# Push to GitHub
git push

# Check what will be committed
git status
```

---

**Need help?** GitHub Desktop is the easiest option if you're not familiar with command line Git.


