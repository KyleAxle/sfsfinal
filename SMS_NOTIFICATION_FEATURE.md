# 📱 SMS Notification Feature for Appointment Acceptance

## Overview

When staff members accept an appointment in the staff dashboard, the system automatically sends an SMS notification to the user with the appointment details.

## How It Works

### 1. **When Staff Accepts an Appointment**
- Staff clicks "Accept" button on a pending appointment
- Staff can optionally add a custom message
- System updates appointment status to "accepted/approved"

### 2. **Automatic SMS Sending**
- After successfully updating the appointment status, the system:
  - Fetches user's phone number from the database
  - Formats the appointment date and time
  - Generates a personalized SMS message
  - Sends SMS via your existing SMS API

### 3. **SMS Message Format**

The generated message includes:
- User's name (if available)
- Office name
- Appointment date (formatted: "January 15, 2025")
- Appointment time (formatted: "2:30 PM")
- Optional staff message (if provided)
- Reminder to arrive on time

**Example Message:**
```
Hello John Doe! Your appointment at Registrar Office has been ACCEPTED. Date: January 15, 2025 at 2:30 PM. Note: Please bring your ID. Please arrive on time. Thank you!
```

## Technical Details

### Files Modified
- `staff_update_appointment.php` - Added SMS sending functionality

### Database Requirements
- Users table must have a `phone` column (already exists in your schema)
- Phone numbers should be stored in a format compatible with your SMS API

### SMS API Integration
- Uses the same API as `send_sms.php`
- API Endpoint: `https://sms.iprogtech.com/api/v1/sms_messages`
- API Token: Configured in the code (same as `send_sms.php`)

### Error Handling
- If user has no phone number: SMS is skipped (logged, but doesn't fail)
- If SMS API fails: Error is logged, but appointment update still succeeds
- All errors are logged to PHP error log for debugging

## Features

✅ **Automatic Notification** - Sends SMS when appointment is accepted  
✅ **Personalized Message** - Includes user name and appointment details  
✅ **Custom Staff Messages** - Staff can add notes that appear in SMS  
✅ **Formatted Date/Time** - Human-readable format (e.g., "January 15, 2025 at 2:30 PM")  
✅ **Error Resilient** - SMS failures don't block appointment updates  
✅ **Logging** - All SMS attempts are logged for debugging  

## Testing

### To Test the Feature:

1. **Ensure User Has Phone Number**
   - User must have a phone number in the `users` table
   - Phone number should be in correct format for your SMS API

2. **Accept an Appointment**
   - Log in as staff
   - Find a pending appointment
   - Click "Accept"
   - Optionally add a message
   - Click "Confirm"

3. **Check SMS Delivery**
   - User should receive SMS within a few seconds
   - Check PHP error logs for SMS status:
     - Success: "SMS notification sent successfully..."
     - Failure: "SMS sending failed..." or "SMS notification skipped..."

### Debugging

Check PHP error logs for SMS-related messages:
- `SMS notification sent successfully to [phone] for appointment ID [id]`
- `SMS notification skipped: No phone number for appointment ID [id]`
- `SMS sending error for appointment ID [id]: [error message]`

## Configuration

### SMS API Token
The API token is currently hardcoded in `staff_update_appointment.php`:
```php
$api_token = 'dadd747dcc588f49217a6d239d9ddf6a81a6e91b';
```

**To change the API token:**
1. Update the token in `staff_update_appointment.php` (line 160)
2. Also update it in `send_sms.php` if you want consistency

### Message Customization
To customize the SMS message format, edit the `sendAppointmentAcceptanceSMS()` function in `staff_update_appointment.php` (around line 149).

## Future Enhancements

Possible improvements:
- SMS notifications for declined appointments
- SMS reminders before appointment date
- Configurable message templates
- Support for multiple languages
- SMS delivery status tracking

---

**Note:** Make sure users have valid phone numbers in the database for SMS notifications to work!

