# AI Chatbot Assistant Setup Guide

## Overview

The AI Chatbot Assistant helps users before they book appointments by:
- Answering questions about which office to choose
- Explaining the appointment booking process
- Providing information about office hours and services
- Offering general guidance

**NEW: Now uses Groq API (FREE) with PHP fallback!**

## Two Modes of Operation

### Mode 1: PHP Fallback (Default - Works Immediately!)
- **No setup required** - Works right out of the box!
- **No API key needed** - Completely free
- **Rule-based responses** - Intelligent pattern matching
- **Always available** - Never fails due to API issues

### Mode 2: Groq API (Optional - For AI-Powered Responses)
- **FREE tier** - No payment required
- **Very fast** - Ultra-low latency responses
- **AI-powered** - More natural conversations
- **Easy setup** - Just add API key to `.env`

## Prerequisites

1. **PHP cURL Extension** - Should already be enabled (used for API calls)
2. **Groq API Key (Optional)** - Only if you want AI-powered responses

## Step 1: Get Groq API Key (Optional)

**If you want AI-powered responses:**

1. Go to [Groq Console](https://console.groq.com/)
2. Sign up for a free account (no credit card required!)
3. Navigate to **API Keys** section
4. Click **"Create API Key"**
5. Give it a name (e.g., "CJC Appointment System")
6. **Copy the API key immediately** - it starts with `gsk_`

⚠️ **Important**: Keep your API key secure and never commit it to version control.

**If you want to use PHP fallback only:**
- Skip this step! The chatbot works without any API key.

## Step 2: Add API Key to Environment (Optional)

**Only if you got a Groq API key:**

1. Open your `.env` file in the project root (create it if it doesn't exist)
2. Add this line:
   ```
   GROQ_API_KEY=gsk-your-actual-api-key-here
   ```
3. Replace `gsk-your-actual-api-key-here` with your actual Groq API key
4. Save the file

**Example `.env` file:**
```env
SUPABASE_DB_HOST=your-host.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASSWORD=your_password
SUPABASE_DB_SSLMODE=require

# Groq API Key (Optional - for AI responses)
GROQ_API_KEY=gsk-abc123xyz789...

# Force PHP fallback (set to 'true' to disable Groq API)
USE_PHP_FALLBACK=false
```

**To force PHP fallback only:**
```env
USE_PHP_FALLBACK=true
```

## Step 3: Verify Setup

1. Make sure `ai_chat.php` is in your project root
2. Make sure `assets/css/ai_chatbot.css` exists
3. Make sure `proto2.html` includes the chatbot widget
4. Test the chatbot by:
   - Going to the booking page (`proto2.html`)
   - Clicking the "AI Assistant" button in the bottom right
   - Asking a question like "Which office handles transcripts?" or "Hello"

## How It Works

### For Users:
1. Click the **"AI Assistant"** button (floating button in bottom right)
2. Chat panel opens
3. Type your question
4. AI responds with helpful information
5. Continue the conversation as needed

### Technical Details:

**Backend (`ai_chat.php`):**
- **First tries Groq API** (if API key is provided and not disabled)
- **Falls back to PHP** if Groq fails or is not configured
- Loads office information from database for context
- Returns response to frontend

**Frontend (`proto2.html`):**
- Floating chatbot widget
- Message history management
- Real-time chat interface
- Auto-scrolling messages

**AI Context (Groq API mode):**
The AI is provided with:
- List of all offices and their descriptions
- Current date and time
- Guidelines on how to help users
- Conversation history (last 10 messages)

**PHP Fallback Mode:**
- Pattern matching for common queries
- Office-specific responses
- Booking process guidance
- Greeting and help responses

## Cost Considerations

**Groq API Pricing:**
- **FREE tier** - Generous limits, no payment required
- **No credit card needed** - Sign up and use immediately
- **Very fast** - Ultra-low latency responses
- Uses Llama 3.1 8B Instant model (fast and efficient)

**PHP Fallback:**
- **Completely FREE** - No API calls, no costs
- **Always available** - Works offline
- **No rate limits** - Unlimited usage

## Customization

### Change AI Model (Groq API)

Edit `ai_chat.php`, in the `callGroqAPI` function:
```php
'model' => 'llama-3.1-8b-instant',  // Fast and free
// Other options:
// 'llama-3.1-70b-versatile' - More capable (slower)
// 'mixtral-8x7b-32768' - Good balance
```

### Modify System Prompt (Groq API)

Edit `ai_chat.php`, the `$systemPrompt` variable in `callGroqAPI` function to customize:
- Tone of responses
- Information provided
- Response style
- Additional guidelines

### Enhance PHP Fallback Responses

Edit `ai_chat.php`, the `generatePHPResponse` function to:
- Add more pattern matching rules
- Customize responses
- Add more office-specific queries
- Improve default responses

### Change Chatbot Position

Edit `assets/css/ai_chatbot.css`:
```css
.ai-chatbot-widget {
    bottom: 24px;  /* Change position */
    right: 24px;
}
```

## Troubleshooting

### "API key not configured"
- **This is OK!** The system will automatically use PHP fallback
- If you want Groq API, add `GROQ_API_KEY` to your `.env` file

### "API request failed" (Groq)
- Check your internet connection
- Verify Groq API is accessible from your server
- Check PHP error logs for details
- System will automatically fall back to PHP responses

### "Invalid response from AI service"
- Check Groq API status
- Verify your API key is valid
- Check PHP error logs
- System will automatically fall back to PHP responses

### Chatbot not appearing
- Check browser console for JavaScript errors
- Verify `ai_chatbot.css` is loaded
- Check that the HTML structure is correct in `proto2.html`

### Want to force PHP fallback only?
Add to your `.env` file:
```
USE_PHP_FALLBACK=true
```

## Security Notes

1. **Never commit `.env` file** - It contains your API key (if you use Groq)
2. **Use environment variables** - Don't hardcode API keys
3. **Rate limiting** - Consider adding rate limiting to prevent abuse
4. **Input validation** - Already implemented, but review regularly

## PHP Fallback Features

The PHP fallback system handles:
- ✅ Greetings (hello, hi, kamusta, etc.)
- ✅ Transcript/Records queries
- ✅ Payment/Tuition questions
- ✅ Guidance/Counseling requests
- ✅ Library services
- ✅ Clinic/Health concerns
- ✅ Office hours questions
- ✅ Booking process guidance
- ✅ Office listings
- ✅ Default helpful responses

## Alternative AI Services

If you want to use a different AI service, you can modify `ai_chat.php` to use:

- **Hugging Face Inference API** - Free tier available
- **Cohere API** - Free tier available
- **DeepSeek API** - Free tier available
- **Local LLM** - Run your own model (more complex setup)

## Support

If you encounter issues:
1. Check PHP error logs
2. Check browser console for JavaScript errors
3. Verify API key is correct (if using Groq)
4. Test without API key (PHP fallback should work)

## Next Steps

Once the chatbot is working, you can:
1. Customize the system prompt for your specific needs (Groq mode)
2. Enhance PHP fallback with more patterns
3. Add more context (office hours, specific procedures)
4. Integrate with appointment booking (suggest times, etc.)
5. Add analytics to track common questions

## Why Groq?

- **FREE** - No payment required
- **Fast** - Ultra-low latency responses
- **Reliable** - Good uptime
- **Easy** - Simple API integration
- **Fallback** - PHP responses always available if API fails
