# ✅ Phone Number Setup Complete

## What Was Done

### 1. ✅ Database Updated
- **Phone column added** to `users` table
- Column type: `VARCHAR(20)` (nullable)
- Run `php add_phone_column.php` to verify/add the column

### 2. ✅ Registration Updated
- `register_process.php` now saves phone numbers during registration
- Phone field is optional (users can leave it blank)
- Phone number is stored when provided

### 3. ✅ Profile Update
- `update_profile.php` already handles phone number updates
- Users can add/update their phone number in their profile

### 4. ✅ SMS Notification Enhanced
- `staff_update_appointment.php` now has better debugging
- Logs phone number fetching and SMS sending attempts
- Handles cases where phone number is missing

## How It Works

### For New Users:
1. User registers → Can optionally provide phone number
2. Phone number is saved to database
3. When staff accepts appointment → SMS is sent automatically

### For Existing Users:
1. User goes to Profile page
2. Adds/updates phone number
3. Saves profile
4. Next appointment acceptance → SMS will be sent

### For Staff:
1. Staff accepts appointment
2. System checks if user has phone number
3. If phone exists → SMS sent automatically
4. If no phone → Logged but appointment still accepted

## Testing

### Test 1: Add Phone Column (Already Done)
```bash
php add_phone_column.php
```
✅ Result: Phone column added successfully

### Test 2: Register New User with Phone
1. Go to registration page
2. Fill in all fields including phone number
3. Submit registration
4. Check database: `SELECT user_id, email, phone FROM public.users WHERE email = 'test@example.com';`

### Test 3: Update Existing User Phone
1. Log in as user
2. Go to Profile page
3. Add phone number
4. Save changes
5. Verify in database

### Test 4: Test SMS Notification
1. Ensure user has phone number in database
2. Staff accepts appointment
3. Check PHP error logs for SMS status:
   - Success: "SMS notification sent successfully to [phone]..."
   - Failure: "SMS notification skipped: No phone number..."

## Current Status

- ✅ Phone column exists in database
- ✅ Registration saves phone numbers
- ✅ Profile update handles phone numbers
- ✅ SMS function fetches phone numbers
- ✅ Debugging added for troubleshooting

## Statistics

After running `add_phone_column.php`:
- Total users: 7
- Users with phone: 0
- Users without phone: 7

**Next Step:** Users need to add their phone numbers via:
1. Profile page (for existing users)
2. Registration (for new users)

## Debugging

Check PHP error logs for SMS-related messages:
- `SMS function called for appointment ID: [id]`
- `Fetched appointment data for ID [id]: phone=[value]`
- `SMS will be sent to phone: [number]`
- `SMS notification sent successfully...`
- `SMS notification skipped: No phone number...`

## Quick SQL Commands

### Check if phone column exists:
```sql
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_schema = 'public' 
AND table_name = 'users' 
AND column_name = 'phone';
```

### Check users with phone numbers:
```sql
SELECT user_id, first_name, last_name, email, phone 
FROM public.users 
WHERE phone IS NOT NULL AND phone != '';
```

### Update a user's phone number (for testing):
```sql
UPDATE public.users 
SET phone = '+639123456789' 
WHERE email = 'user@example.com';
```

## Next Steps

1. **For Existing Users:** Ask them to add phone numbers via Profile page
2. **For New Users:** Phone field is available during registration
3. **Test SMS:** Accept an appointment for a user with a phone number
4. **Monitor Logs:** Check error logs to see SMS sending status

---

**✅ Everything is set up and ready! Users can now add phone numbers and receive SMS notifications when appointments are accepted.**

