# AI Chatbot Quick Start Guide

## 🚀 Quick Setup (3 Steps)

### Step 1: Get Groq API Key (FREE - Optional!)
1. Visit: https://console.groq.com/
2. Sign up for a free account (no credit card required!)
3. Go to API Keys section
4. Click "Create API Key"
5. Copy the key (starts with `gsk_`)

**Note:** The chatbot works WITHOUT an API key using PHP fallback responses! The Groq API key is optional for AI-powered responses.

### Step 2: Add to .env File (Optional)
Open your `.env` file and add:
```
GROQ_API_KEY=gsk-your-key-here
```

**OR** leave it empty to use PHP fallback (works immediately, no setup needed!)

### Step 3: Test It!
1. Go to `proto2.html` (booking page)
2. Click the **"AI Assistant"** button (bottom right)
3. Ask: "Which office handles transcripts?" or "Hello"

## ✅ That's It!

The chatbot is now ready to help users with:
- Finding the right office
- Understanding booking process
- Office hours and services
- General questions

## 📋 Files Created

- `ai_chat.php` - Backend API endpoint (uses Groq API + PHP fallback)
- `assets/css/ai_chatbot.css` - Chatbot styling
- `AI_CHATBOT_SETUP.md` - Detailed documentation
- Updated `proto2.html` - Added chatbot widget

## 💰 Cost

- **FREE!** Groq API has a generous free tier
- **FREE!** PHP fallback works without any API key
- No payment method required
- No credit card needed

## 🆘 Troubleshooting

**"API key not configured"**
→ That's OK! The system will use PHP fallback responses automatically.

**Chatbot not appearing**
→ Check browser console for errors
→ Verify CSS file is loaded

**Want to use Groq API?**
→ Get free API key at: https://console.groq.com/
→ Add `GROQ_API_KEY=gsk-...` to your `.env` file

## 📖 Full Documentation

See `AI_CHATBOT_SETUP.md` for:
- Detailed setup instructions
- Customization options
- Security best practices
- How the PHP fallback works
