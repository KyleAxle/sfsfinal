# 📱 SMS API Setup Guide (iprogsms.com)

## Overview

The SMS system has been updated to work with the new iprogsms.com requirements. All SMS messages now automatically include a **header** (required) and optional **footer** text that you configure in your account.

## Quick Setup

### 1. Configure Your SMS Account on iprogsms.com

1. Go to https://iprogsms.com/kyc/new
2. Fill out the registration form:
   - **Account Type**: Select "Student" (for capstone projects)
   - **Project Title**: Enter "SFS" (or your project name)
   - **Project Description**: Describe your appointment system
   - **School/University Name**: Enter your school name (e.g., "Cor Jesu College")
   - **Course/Program**: Enter your course/program

3. **Set up SMS Template** (IMPORTANT):
   - **Header Text** (REQUIRED): 
     - Must be related to your project title or school name
     - Examples: 
       - "SFS Appointment System"
       - "CJC Student Management"
       - "SFS Student Portal"
   - **Footer Text** (OPTIONAL):
     - Can be left empty, or add something like:
     - "Thank you for using CJC SFS"
     - "Cor Jesu College"

4. Submit your account verification

### 2. Update Your Configuration

After your account is verified, update the SMS configuration in your project:

#### Option A: Using Environment Variables (Recommended for Railway)

1. In Railway, go to your project → Variables
2. Add these environment variables:

```
SMS_API_TOKEN=your_api_token_from_iprogsms
SMS_HEADER_TEXT=SFS Appointment System
SMS_FOOTER_TEXT=
```

**Important**: The `SMS_HEADER_TEXT` must match exactly what you entered in your iprogsms.com account!

#### Option B: Edit Config File Directly

1. Open `config/sms.php`
2. Update these lines:

```php
// Header Text (REQUIRED) - Must match your iprogsms.com account
define('SMS_HEADER_TEXT', 'SFS Appointment System');

// Footer Text (OPTIONAL)
define('SMS_FOOTER_TEXT', ''); // Leave empty or add your footer
```

3. Update the API token:

```php
define('SMS_API_TOKEN', 'your_api_token_here');
```

### 3. Get Your API Token

1. Log in to your iprogsms.com account
2. Go to your dashboard/API settings
3. Copy your API token
4. Add it to your configuration (see step 2 above)

## How It Works

### Message Format

All SMS messages are automatically formatted like this:

```
[Header Text]

[Your message content]

[Footer Text]
```

### Example

If your header is "SFS Appointment System" and footer is empty, a message will look like:

```
SFS Appointment System

Hello John Doe! Your appointment at Registrar Office has been ACCEPTED. Date: January 15, 2025 at 2:30 PM. Please arrive on time. Thank you!
```

## Files Updated

- ✅ `config/sms.php` - Centralized SMS configuration
- ✅ `send_sms.php` - Updated to use header/footer template
- ✅ `staff_update_appointment.php` - Updated to use header/footer template

## Testing

1. Make sure your iprogsms.com account is verified
2. Ensure the header text in your config matches your account
3. Accept an appointment as staff
4. Check if the SMS is sent successfully
5. Verify the message includes your header text

## Troubleshooting

### SMS Not Sending

1. **Check API Token**: Make sure your API token is correct
2. **Check Header Text**: The header must match exactly what's in your iprogsms.com account
3. **Check Account Status**: Ensure your account is verified on iprogsms.com
4. **Check SMS Credits**: Make sure you have credits in your account
5. **Check Logs**: Look at PHP error logs for SMS-related errors

### Header Text Mismatch

If you get an error about header text:
- The header in `config/sms.php` must match exactly what you set in iprogsms.com
- Check for typos, extra spaces, or case sensitivity

### Footer Not Appearing

- Footer is optional - if you don't set it, it won't appear
- Make sure `SMS_FOOTER_TEXT` is not empty if you want a footer

## Configuration Reference

### Environment Variables

```bash
# Required
SMS_API_TOKEN=your_token_here

# Required - Must match iprogsms.com account
SMS_HEADER_TEXT=SFS Appointment System

# Optional
SMS_FOOTER_TEXT=Thank you for using CJC SFS
```

### Config File Constants

```php
define('SMS_API_TOKEN', 'your_token_here');
define('SMS_HEADER_TEXT', 'SFS Appointment System');
define('SMS_FOOTER_TEXT', ''); // Optional
```

## Need Help?

- Check iprogsms.com documentation
- Review PHP error logs for detailed error messages
- Ensure your account is verified and has credits
