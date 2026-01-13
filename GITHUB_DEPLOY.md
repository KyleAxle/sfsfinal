# Quick GitHub & Railway Deployment Guide

## 🚀 Quick Start

### Step 1: Push to GitHub

Open PowerShell or Command Prompt in your project folder and run:

```bash
# Navigate to project (if not already there)
cd C:\Users\cuber\OneDrive\Desktop\SFSFINAL\sfs

# Check if git is initialized
git status

# If not initialized, run:
git init

# Add all files
git add .

# Commit
git commit -m "Initial commit: SFS Appointment System ready for deployment"

# Add your GitHub repository (replace with your actual repo URL)
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git

# Push to GitHub
git branch -M main
git push -u origin main
```

**If you get authentication errors:**
- Use GitHub Personal Access Token instead of password
- Or set up SSH: https://docs.github.com/en/authentication/connecting-to-github-with-ssh

### Step 2: Deploy to Railway

1. **Sign up/Login to Railway**: https://railway.app
2. **Create New Project** → "Deploy from GitHub repo"
3. **Select your repository**
4. **Add Environment Variables** (in Railway dashboard → Variables):

```
SUPABASE_DB_HOST=your-host.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres.your-ref
SUPABASE_DB_PASSWORD=your-password
SUPABASE_DB_SSLMODE=require
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-secret
GOOGLE_REDIRECT_URI=https://your-app.railway.app/google_callback.php
GROQ_API_KEY=your-key (optional)
USE_PHP_FALLBACK=false
```

5. **Wait for deployment** (Railway auto-detects PHP)
6. **Get your domain** from Railway dashboard
7. **Update Google OAuth** redirect URI with your Railway domain

### Step 3: Database Setup

1. Go to Supabase Dashboard → SQL Editor
2. Run `supabase/schema.sql`
3. Verify tables are created

## 📝 Important Notes

- ✅ `.env` file is already in `.gitignore` (won't be committed)
- ✅ `uploads/` folder is ignored (user uploads)
- ✅ Debug/test files are ignored
- ✅ Railway will auto-deploy on every git push

## 🔄 Updating Your App

After making changes:

```bash
git add .
git commit -m "Your changes description"
git push origin main
```

Railway will automatically redeploy!

## 📚 Full Documentation

See `DEPLOYMENT.md` for detailed instructions.
