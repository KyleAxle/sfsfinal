# 📱 SMS Status Check Guide - Verifying Status: 200

## Overview

This guide explains how to verify that SMS messages are being sent successfully by checking for `status: 200` in the API response from iprogsms.com.

## Understanding the API Response

### Successful Response Format

When SMS is sent successfully, iprogsms.com returns:

```json
{
  "status": 200,
  "message": "Your SMS message has been successfully added to the queue and will be processed shortly.",
  "message_id": "iSms-XHYBk"
}
```

**Key Indicators:**
- ✅ `status: 200` - SMS was accepted and queued
- ✅ `message_id` - Unique identifier for tracking
- ✅ `message` - Confirmation message

### Failed Response Format

If SMS fails, the API may return:

```json
{
  "status": 400,
  "error": "Invalid phone number format"
}
```

Or:

```json
{
  "status": 401,
  "error": "Invalid API token"
}
```

## How to Check SMS Status

### Method 1: Check PHP Error Logs (Recommended)

The system automatically logs all SMS requests and responses. Check your PHP error logs:

**On Railway:**
1. Go to your Railway project dashboard
2. Click on your service
3. Go to the "Logs" tab
4. Look for entries containing "SMS API"

**On Local Server:**
- Check your PHP error log file (usually in `/var/log/php/error.log` or check `php.ini` for `error_log` setting)
- Or check Apache/Nginx error logs

**What to Look For:**

```
SMS API Request - URL: https://www.iprogsms.com/api/v1/sms_messages
SMS API Request - Data: {"api_token":"...","message":"...","phone_number":"..."}
SMS API Response - HTTP Code: 200
SMS API Response - Body: {"status":200,"message":"...","message_id":"..."}
SMS notification sent successfully to +639123456789 for appointment ID 123 - Message ID: iSms-XHYBk
```

**Success Indicators:**
- ✅ `HTTP Code: 200`
- ✅ `"status":200` in response body
- ✅ `Message ID: iSms-...` logged
- ✅ "SMS notification sent successfully" message

**Failure Indicators:**
- ❌ `HTTP Code: 400, 401, 500, etc.`
- ❌ `"status":400` or other non-200 status
- ❌ `Invalid JSON response`
- ❌ `cURL error: ...`

### Method 2: Check Browser Console (For Frontend)

1. Open your browser's Developer Tools (F12)
2. Go to the "Console" tab
3. Accept an appointment as staff
4. Look for any error messages related to SMS

### Method 3: Check Network Tab

1. Open Developer Tools (F12)
2. Go to the "Network" tab
3. Accept an appointment
4. Find the request to `staff_update_appointment.php`
5. Click on it and check the "Response" tab
6. Look for `sms_sent: true` or `sms_sent: false`

**Expected Response:**
```json
{
  "success": true,
  "status": "approved",
  "sms_sent": true
}
```

### Method 4: Check iprogsms.com Dashboard

1. Log in to your iprogsms.com account
2. Go to the "SMS Status" or "Message History" section
3. Look for your sent messages
4. Check the status column:
   - ✅ **Completed** - Message was delivered
   - ⏳ **Pending** - Message is queued
   - ❌ **Failed** - Message failed to send

## Troubleshooting Status: 200 Issues

### Issue: HTTP 200 but status is not 200

**Symptom:**
```
HTTP Code: 200
Response: {"status": 400, "error": "Invalid phone number"}
```

**Solution:**
- Check phone number format (should be `+639123456789` or `639123456789`)
- Ensure phone number is valid

### Issue: HTTP 200 but no status field

**Symptom:**
```
HTTP Code: 200
Response: {"message": "Success"}
```

**Solution:**
- The API response format may have changed
- Check iprogsms.com API documentation for latest format
- Update the code to handle different response formats

### Issue: HTTP 200 but Invalid JSON

**Symptom:**
```
HTTP Code: 200
Response: HTML page or plain text
```

**Solution:**
- API endpoint might be incorrect
- Check `SMS_API_URL` in `config/sms.php`
- Verify the URL is: `https://www.iprogsms.com/api/v1/sms_messages`

### Issue: HTTP 401 Unauthorized

**Symptom:**
```
HTTP Code: 401
Response: {"error": "Invalid API token"}
```

**Solution:**
1. Check your API token in `config/sms.php` or environment variables
2. Verify the token is correct in your iprogsms.com account
3. Ensure the token hasn't expired

### Issue: HTTP 400 Bad Request

**Symptom:**
```
HTTP Code: 400
Response: {"error": "Missing required parameter"}
```

**Solution:**
- Check that all required parameters are sent:
  - `api_token`
  - `phone_number`
  - `message`
- Verify the request is sent as JSON (not form data)

## Code Verification Checklist

### ✅ Request Format

The request should be:
- **Method:** POST
- **Content-Type:** `application/json`
- **Body:** JSON format
- **URL:** `https://www.iprogsms.com/api/v1/sms_messages`

### ✅ Response Validation

The code checks:
1. ✅ HTTP status code is 200
2. ✅ Response is valid JSON
3. ✅ Response contains `status` field
4. ✅ `status` field equals 200
5. ✅ Optional: `message_id` field exists

### ✅ Error Handling

The code handles:
- ✅ cURL errors (network issues)
- ✅ HTTP errors (400, 401, 500, etc.)
- ✅ Invalid JSON responses
- ✅ Missing status field
- ✅ Non-200 status values

## Testing SMS Status

### Test Script

Create a test file `test_sms.php`:

```php
<?php
require_once __DIR__ . '/config/sms.php';

$testData = [
    'api_token' => SMS_API_TOKEN,
    'message' => 'Test message from SFS system',
    'phone_number' => '+639123456789' // Replace with your test number
];

echo "Sending test SMS...\n";
echo "URL: " . SMS_API_URL . "\n";
echo "Data: " . json_encode($testData) . "\n\n";

$ch = curl_init(SMS_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($curlError) {
    echo "❌ cURL Error: $curlError\n";
    exit(1);
}

if ($httpCode !== 200) {
    echo "❌ HTTP Error: $httpCode\n";
    exit(1);
}

$result = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Invalid JSON response\n";
    exit(1);
}

if (isset($result['status']) && $result['status'] == 200) {
    echo "✅ SUCCESS! Status: 200\n";
    echo "Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
    echo "Message: " . ($result['message'] ?? 'N/A') . "\n";
} else {
    echo "❌ FAILED! Status: " . ($result['status'] ?? 'N/A') . "\n";
    echo "Error: " . ($result['error'] ?? $result['message'] ?? 'Unknown error') . "\n";
    exit(1);
}
?>
```

**Run the test:**
```bash
php test_sms.php
```

## Common Status Codes

| Status | Meaning | Action |
|--------|---------|--------|
| 200 | Success | ✅ SMS queued successfully |
| 400 | Bad Request | ❌ Check request parameters |
| 401 | Unauthorized | ❌ Check API token |
| 402 | Payment Required | ❌ Add SMS credits |
| 500 | Server Error | ❌ API server issue, try again later |

## Quick Status Check Commands

### Check Recent SMS Logs (Railway)

```bash
# View recent logs
railway logs --tail 100 | grep -i "SMS"
```

### Check Recent SMS Logs (Local)

```bash
# View PHP error log
tail -n 100 /var/log/php/error.log | grep -i "SMS"

# Or check Apache error log
tail -n 100 /var/log/apache2/error.log | grep -i "SMS"
```

## Summary

✅ **Success Criteria:**
- HTTP status code: 200
- Response JSON contains `"status": 200`
- `message_id` is present in response
- Log shows "SMS notification sent successfully"

❌ **Failure Indicators:**
- HTTP status code is not 200
- Response JSON has `"status"` other than 200
- No `message_id` in response
- Error message in logs

If you see `status: 200` in the response, your SMS was successfully queued and should be delivered shortly!
