# AI Booked Appointments - Staff Dashboard Visibility Debug Guide

## Issue
Appointments booked via AI are not showing on the staff dashboard.

## Debugging Steps

### 1. Check if Appointment Exists in Database

Run this SQL query to verify the appointment was created:

```sql
SELECT 
    a.appointment_id,
    a.user_id,
    a.appointment_date,
    a.appointment_time,
    a.concern,
    a.status,
    ao.office_id,
    o.office_name
FROM public.appointments a
LEFT JOIN public.appointment_offices ao ON ao.appointment_id = a.appointment_id
LEFT JOIN public.offices o ON o.office_id = ao.office_id
WHERE a.user_id = YOUR_USER_ID
ORDER BY a.appointment_date DESC, a.appointment_time DESC
LIMIT 10;
```

**What to check:**
- Does the appointment exist? ✅
- Does it have a matching `appointment_offices` record? ✅
- Does the `office_id` match the staff's office? ✅

### 2. Check Staff's Office ID

Check what office the staff member is logged into:

```sql
SELECT office_id, office_name FROM public.offices WHERE office_id = STAFF_OFFICE_ID;
```

**What to check:**
- Does the staff's `office_id` match the appointment's `office_id` in `appointment_offices`? ✅

### 3. Check Staff Dashboard Query

The staff dashboard uses this query:

```sql
SELECT
    a.appointment_id,
    ao.office_id,
    u.user_id,
    u.first_name,
    u.last_name,
    u.email,
    a.appointment_date,
    a.appointment_time,
    a.concern,
    a.status
FROM public.appointments a
JOIN public.appointment_offices ao ON ao.appointment_id = a.appointment_id
JOIN public.users u ON u.user_id = a.user_id
WHERE ao.office_id = ?
ORDER BY a.appointment_date ASC, a.appointment_time ASC
```

**What to check:**
- Does the query return results when you run it manually with the staff's `office_id`? ✅
- Are there any PHP errors in the error log? ✅

### 4. Check PHP Error Logs

Look for these log entries when booking:

```
createAppointmentDirectly called with: {...}
Booking params - Office ID: X, Date: ..., Time: ..., Concern: ...
Executing INSERT: user_id=..., date=..., time=..., concern=..., status=...
Appointment INSERT successful, getting appointment_id...
Successfully got appointment_id: X
Linking office: appointment_id=X, office_id=Y, status=pending
Office linked successfully!
Verification: Appointment ID X linked to Office ID Y (Office Name)
Booking completed successfully! Appointment ID: X, Office: ...
```

**What to check:**
- Are all these log entries present? ✅
- Does the `office_id` in the logs match the staff's `office_id`? ✅
- Are there any error messages? ✅

### 5. Common Issues & Solutions

#### Issue 1: Office ID Mismatch
**Symptom:** Appointment exists but staff can't see it
**Solution:** Verify the `office_id` in `appointment_offices` matches the staff's `office_id`

#### Issue 2: Missing appointment_offices Record
**Symptom:** Appointment exists but no `appointment_offices` record
**Solution:** Check error logs for "Failed to assign office" - the appointment should be rolled back

#### Issue 3: Schema Prefix Issue
**Symptom:** Query doesn't find appointments
**Solution:** Both `staff_dashboard.php` and `ai_chat.php` should use `public.` prefix (already fixed)

#### Issue 4: Status Filter
**Symptom:** Appointments exist but filtered out
**Solution:** Staff dashboard shows all statuses by default, but check if there's a status filter active

### 6. Manual Verification Query

Run this query to see all appointments for a specific office:

```sql
SELECT 
    a.appointment_id,
    a.appointment_date,
    a.appointment_time,
    a.concern,
    a.status,
    u.first_name || ' ' || u.last_name AS user_name,
    u.email,
    o.office_name
FROM public.appointments a
JOIN public.appointment_offices ao ON ao.appointment_id = a.appointment_id
JOIN public.users u ON u.user_id = a.user_id
JOIN public.offices o ON o.office_id = ao.office_id
WHERE ao.office_id = STAFF_OFFICE_ID
ORDER BY a.appointment_date DESC, a.appointment_time DESC;
```

Replace `STAFF_OFFICE_ID` with the actual office ID from the staff session.

### 7. Test Booking Flow

1. **Book via AI:**
   - User: "Book me with Registrar Office on January 15, 2026, 1 PM"
   - AI: "What is this appointment for?"
   - User: "I need to request a transcript"
   - AI: Should book successfully

2. **Check Database:**
   - Verify appointment exists
   - Verify `appointment_offices` record exists
   - Verify `office_id` matches

3. **Check Staff Dashboard:**
   - Log in as staff for that office
   - Should see the appointment

### 8. Recent Changes

✅ Added verification logging in `createAppointmentDirectly()`
✅ Added check to verify `appointment_offices` record after creation
✅ Staff dashboard already uses correct schema prefixes
✅ Appointment creation uses correct schema prefixes

## Next Steps

1. Check PHP error logs for booking attempts
2. Run the verification SQL queries above
3. Compare `office_id` values between appointment and staff session
4. Check if appointments are being created but with wrong `office_id`

---

*Use this guide to debug why appointments aren't showing on staff dashboard.*
