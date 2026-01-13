# 🔍 Where to Find Your Google OAuth Credentials

## Quick Navigation Guide

### Step 1: Go to Google Cloud Console
**Direct Link:** https://console.cloud.google.com/apis/credentials

Or navigate manually:
1. Go to: https://console.cloud.google.com/
2. Sign in with your Google account
3. Select your project (or create a new one)
4. In the left sidebar, click **"APIs & Services"**
5. Click **"Credentials"**

### Step 2: Find Your OAuth 2.0 Client ID

Once you're on the Credentials page:

1. **Look for "OAuth 2.0 Client IDs" section**
   - It should be in the middle of the page
   - You'll see a list of OAuth clients if you've created any

2. **If you haven't created one yet:**
   - Click the **"+ CREATE CREDENTIALS"** button at the top
   - Select **"OAuth client ID"**
   - Follow the setup wizard

3. **If you already created one:**
   - Find your OAuth client in the list (usually named something like "Web client 1" or "CJC School Services Web Client")
   - Click on the **pencil icon (✏️)** or the client name to edit/view it

### Step 3: View Your Credentials

When you click on your OAuth client, you'll see:

- **Client ID**: A long string like `123456789-abcdefghijklmnop.apps.googleusercontent.com`
- **Client secret**: A string like `GOCSPX-abcdefghijklmnopqrstuvwxyz`

**⚠️ IMPORTANT:** 
- The Client Secret is only shown **once** when you first create it
- If you don't see the secret, you'll need to:
  - Click "RESET SECRET" to generate a new one
  - Or create a new OAuth client

### Step 4: Copy to Your .env File

1. Open your `.env` file in the project root
2. Find the Google OAuth section:
   ```env
   GOOGLE_CLIENT_ID=YOUR_GOOGLE_CLIENT_ID
   GOOGLE_CLIENT_SECRET=YOUR_GOOGLE_CLIENT_SECRET
   ```
3. Replace the placeholders with your actual credentials

## Visual Guide

```
Google Cloud Console
└── Your Project
    └── APIs & Services (left sidebar)
        └── Credentials
            └── OAuth 2.0 Client IDs
                └── [Your Client Name] ← Click here
                    ├── Client ID: [Copy this]
                    └── Client secret: [Copy this]
```

## Troubleshooting

### "I can't see the Client Secret"
- You may have already created the client before
- Click "RESET SECRET" to generate a new one
- **Save it immediately** - it won't be shown again!

### "I don't see OAuth client ID option"
- Make sure you've configured the OAuth consent screen first
- Go to: APIs & Services → OAuth consent screen
- Complete the consent screen setup, then come back to Credentials

### "Where is the Authorized redirect URI?"
- When creating/editing the OAuth client, scroll down to find:
  - **"Authorized redirect URIs"** section
  - Click **"+ ADD URI"**
  - Add: `http://localhost:8000/google_callback.php`

## Quick Links

- **Credentials Page:** https://console.cloud.google.com/apis/credentials
- **OAuth Consent Screen:** https://console.cloud.google.com/apis/credentials/consent
- **API Library:** https://console.cloud.google.com/apis/library

---

**Need help?** Make sure you're signed in to the correct Google account and have selected the right project!

