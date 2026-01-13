# 🔧 Fix Google Sign-In on Railway

## Problem
The error shows:
```
Failed to open stream: No such file or directory in /app/google_login.php on line 2
Failed opening required '/app/google-api-php-client/vendor/autoload.php'
```

This happens because the `vendor` directory (Composer dependencies) isn't being installed on Railway.

## ✅ Solution Applied

I've updated `nixpacks.toml` to:
1. Install Composer during build
2. Run `composer install` in the `google-api-php-client` directory
3. Install all required dependencies

## 🚀 Deploy the Fix

1. **Commit and push the changes:**
   ```bash
   git add nixpacks.toml
   git commit -m "Fix Google Sign-In: Install Composer dependencies on Railway"
   git push origin main
   ```

2. **Railway will automatically:**
   - Detect the changes
   - Rebuild the application
   - Install Composer
   - Run `composer install` in `google-api-php-client/`
   - Deploy with all dependencies

3. **Wait for deployment** (usually 2-3 minutes)

4. **Test Google Sign-In:**
   - Visit your Railway URL
   - Click "Sign in with Google"
   - Should work now! ✅

## 📋 What Changed

**File: `nixpacks.toml`**

**Before:**
```toml
[phases.setup]
nixPkgs = ["php82", "php82Extensions.pdo", ...]

[phases.install]
cmds = ["echo 'No additional install steps needed'"]
```

**After:**
```toml
[phases.setup]
nixPkgs = ["php82", "php82Extensions.pdo", ..., "composer"]

[phases.install]
cmds = [
    "cd google-api-php-client && composer install --no-dev --optimize-autoloader"
]
```

## ⚠️ Important Notes

1. **Make sure `composer.json` and `composer.lock` are committed:**
   - These files should be in `google-api-php-client/`
   - They're needed for Railway to install dependencies

2. **The `vendor/` directory is ignored** (in `.gitignore`):
   - This is correct! It will be installed during build
   - Don't commit the vendor folder

3. **If it still doesn't work:**
   - Check Railway build logs for errors
   - Verify `composer.json` exists in `google-api-php-client/`
   - Make sure the build completes successfully

## 🎯 Expected Result

After deployment, when you visit:
```
https://your-app.railway.app/google_login.php
```

It should:
- ✅ Load without errors
- ✅ Redirect to Google OAuth
- ✅ Allow users to sign in with Google

---

**Status**: ✅ Fix applied - Ready to deploy
