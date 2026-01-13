# 🔐 How to Get Your Google OAuth Client Secret

## The Problem
Google masks client secrets for security - you'll see something like `****TQ9s` instead of the full secret. **You cannot copy or view the masked secret.**

## Solution: Generate a New Client Secret

Since you can't see the old secret, you need to create a new one. Here's how:

### Option 1: Add a New Secret (Recommended)

1. **On the "Client secrets" page you're viewing:**
   - Click the **"Add secret"** button (blue button with plus icon)
   - This will generate a NEW client secret

2. **When the new secret appears:**
   - ⚠️ **COPY IT IMMEDIATELY!** 
   - The full secret will be shown **ONLY ONCE**
   - It will look like: `GOCSPX-abcdefghijklmnopqrstuvwxyz123456`
   - After you close the popup, it will be masked again

3. **Update your .env file:**
   ```env
   GOOGLE_CLIENT_SECRET=GOCSPX-your-new-secret-here
   ```

4. **The old secret will still work** (if you have it saved somewhere), but you should use the new one going forward.

### Option 2: Reset the Existing Secret

If you want to replace the current secret:

1. Look for a **"Reset"** or **"Regenerate"** button next to the masked secret
2. Click it to generate a new secret
3. **Copy the new secret immediately** - it's shown only once
4. Update your `.env` file with the new secret

## Important Notes

### ⚠️ Security Best Practices:
- **Never commit secrets to Git** - they're already in `.gitignore`
- **Store secrets securely** - use environment variables or secure vaults
- **Rotate secrets regularly** - especially if they might be compromised

### 🔄 Multiple Secrets:
- You can have **multiple active secrets** at the same time
- This allows you to rotate secrets without downtime
- Old secrets will continue working until you disable/delete them

### 📝 What to Do Right Now:

1. Click **"Add secret"** button
2. **IMMEDIATELY copy the full secret** that appears
3. Paste it into your `.env` file:
   ```env
   GOOGLE_CLIENT_SECRET=GOCSPX-paste-the-full-secret-here
   ```
4. Save the `.env` file
5. Restart your PHP server

## Troubleshooting

### "I clicked Add secret but didn't copy it in time"
- You'll need to add another new secret
- The previous one is now masked and can't be retrieved

### "Can I see the old secret?"
- **No** - Google doesn't allow viewing masked secrets for security
- You must generate a new one

### "Will the old secret still work?"
- Yes, if you have it saved somewhere
- But it's better to use the new one and disable the old one for security

## Quick Steps Summary

```
1. Click "Add secret" button
2. Copy the FULL secret immediately (shown only once!)
3. Update .env file: GOOGLE_CLIENT_SECRET=your-new-secret
4. Save and restart server
```

---

**Remember:** The secret is shown **ONLY ONCE** when created. Have your `.env` file open and ready to paste it immediately!

