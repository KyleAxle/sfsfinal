# 🔒 Security Features Implementation Documentation

## Overview

This document describes the critical security features that have been implemented in the CJC School Frontline Services (SFS) appointment management system to protect against common web vulnerabilities and attacks.

**Last Updated:** January 2026  
**Version:** 2.0

---

## 🎯 Implemented Security Features

### 1. ✅ Account Lockout System

**Threat Addressed:** Brute force attacks, credential stuffing, automated password guessing

**Implementation:**
- **Location:** `config/account_lockout.php`
- **Database Migration:** `add_account_lockout_columns.php`

**How It Works:**
1. Tracks failed login attempts for each user (users, staff, admins)
2. Locks account after **5 failed attempts**
3. Lockout duration: **30 minutes**
4. Automatically resets failed attempts counter on successful login
5. Provides clear feedback to users about remaining attempts

**Database Schema:**
```sql
-- Added to users, staff, and admins tables:
- failed_login_attempts (INTEGER) - Count of failed attempts
- account_locked_until (TIMESTAMPTZ) - When lockout expires
- last_failed_login_at (TIMESTAMPTZ) - Timestamp of last failed attempt
```

**Key Functions:**
- `checkAccountLockout()` - Checks if account is currently locked
- `recordFailedAttempt()` - Records failed login and locks if threshold reached
- `resetFailedAttempts()` - Resets counter on successful login
- `getRemainingAttempts()` - Returns remaining attempts before lockout

**Configuration:**
```php
define('MAX_FAILED_ATTEMPTS', 5);      // Lock after 5 attempts
define('LOCKOUT_DURATION', 1800);      // 30 minutes lockout
define('RESET_ATTEMPTS_AFTER', 900);   // 15 minutes reset window
```

**User Experience:**
- Users see remaining attempts: "Invalid credentials. 3 attempt(s) remaining before account lockout."
- When locked: "Account is locked due to too many failed login attempts. Please try again in 30 minutes."

**Files Modified:**
- `login_process.php` - User login with lockout
- `staff_login.php` - Staff login with lockout
- `admin/admin_login_process.php` - Admin login with lockout

---

### 2. ✅ Role-Based Access Control (RBAC)

**Threat Addressed:** Privilege escalation, unauthorized access, horizontal/vertical privilege escalation

**Implementation:**
- **Location:** `config/authorization.php`

**How It Works:**
1. Centralized authorization functions for all user roles
2. Automatic role detection from session
3. Resource ownership verification
4. Office-based access control for staff

**Key Functions:**

#### `requireAuth()`
- Ensures user is authenticated
- Redirects to login if not authenticated
- Returns JSON error for API endpoints

#### `requireRole($requiredRoles, $redirect = true)`
- Checks if user has required role(s)
- Supports single role or array of roles
- Returns 403 Forbidden if unauthorized

#### `requireAdmin()`, `requireStaff()`, `requireUser()`
- Convenience functions for specific roles
- Staff can access user functions, admins can access all

#### `checkResourceOwnership($table, $idColumn, $resourceId, $ownerColumn)`
- Verifies user owns the resource they're accessing
- Prevents users from accessing other users' data
- Admins bypass ownership checks

#### `verifyStaffOffice($officeId)`
- Ensures staff can only access their own office's data
- Prevents staff from viewing other offices' appointments

**Usage Example:**
```php
// At the top of admin pages
require_once __DIR__ . '/config/authorization.php';
requireAdmin(); // Blocks non-admin users

// At the top of staff pages
requireStaff(); // Blocks non-staff users

// Verify resource ownership
if (!checkResourceOwnership('appointments', 'appointment_id', $appointmentId, 'user_id')) {
    die('Access denied');
}
```

**Files Modified:**
- `save_appointment.php` - Requires user authentication
- `staff_update_appointment.php` - Requires staff authentication
- `admin/admin_dashboard.php` - Requires admin authentication

---

### 3. ✅ Audit Logging System

**Threat Addressed:** Security monitoring, forensic analysis, compliance, unauthorized activity detection

**Implementation:**
- **Location:** `config/audit_log.php`
- **Database Table:** `audit_log` (auto-created on first use)

**How It Works:**
1. Logs all sensitive operations automatically
2. Stores user ID, role, IP address, user agent, and action details
3. JSONB storage for flexible detail tracking
4. Indexed for fast queries

**Database Schema:**
```sql
CREATE TABLE public.audit_log (
    log_id BIGSERIAL PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id BIGINT,
    user_id BIGINT,
    user_role VARCHAR(20),
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSONB,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

**Key Function:**
```php
logAuditEvent($action, $entityType, $entityId, $details, $userId, $userRole)
```

**Logged Events:**
- `AUDIT_LOGIN` - Successful login
- `AUDIT_LOGIN_FAILED` - Failed login attempt
- `AUDIT_LOGOUT` - User logout
- `AUDIT_APPOINTMENT_CREATED` - New appointment created
- `AUDIT_APPOINTMENT_UPDATED` - Appointment status changed
- `AUDIT_APPOINTMENT_DELETED` - Appointment deleted
- `AUDIT_ACCOUNT_LOCKED` - Account locked due to failed attempts
- `AUDIT_PASSWORD_CHANGED` - Password changed
- `AUDIT_PROFILE_UPDATED` - Profile information updated
- `AUDIT_EMAIL_VERIFIED` - Email verification completed

**Example Log Entry:**
```json
{
    "action": "appointment_updated",
    "entity_type": "appointment",
    "entity_id": 123,
    "user_id": 45,
    "user_role": "staff",
    "ip_address": "192.168.1.100",
    "details": {
        "old_status": "pending",
        "new_status": "accepted",
        "office_id": 5,
        "sms_sent": true
    },
    "created_at": "2026-01-15 10:30:00"
}
```

**Files Modified:**
- `login_process.php` - Logs login attempts and account lockouts
- `save_appointment.php` - Logs appointment creation
- `staff_update_appointment.php` - Logs appointment updates

**Querying Audit Logs:**
```sql
-- View all failed login attempts
SELECT * FROM audit_log WHERE action = 'login_failed' ORDER BY created_at DESC;

-- View all actions by a specific user
SELECT * FROM audit_log WHERE user_id = 123 ORDER BY created_at DESC;

-- View account lockouts
SELECT * FROM audit_log WHERE action = 'account_locked' ORDER BY created_at DESC;

-- View all appointment updates in last 24 hours
SELECT * FROM audit_log 
WHERE action = 'appointment_updated' 
AND created_at > NOW() - INTERVAL '24 hours'
ORDER BY created_at DESC;
```

---

## 🔐 Security Configuration

### Account Lockout Settings

Located in `config/account_lockout.php`:
```php
define('MAX_FAILED_ATTEMPTS', 5);      // Lock after 5 failed attempts
define('LOCKOUT_DURATION', 1800);      // 30 minutes (in seconds)
define('RESET_ATTEMPTS_AFTER', 900);   // 15 minutes reset window
```

**To Customize:**
1. Edit the constants in `config/account_lockout.php`
2. Adjust based on your security requirements
3. Consider: Higher attempts = better UX, Lower attempts = better security

---

## 📋 Integration Guide

### Adding Authorization to New Pages

**For User Pages:**
```php
<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/authorization.php';
requireUser(); // Only authenticated users can access
// Your page code here
?>
```

**For Staff Pages:**
```php
<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/authorization.php';
requireStaff(); // Only staff and admins can access
// Your page code here
?>
```

**For Admin Pages:**
```php
<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/authorization.php';
requireAdmin(); // Only admins can access
// Your page code here
?>
```

### Adding Audit Logging

**Basic Usage:**
```php
require_once __DIR__ . '/config/audit_log.php';

// Log an action
logAuditEvent(
    'password_changed',           // Action
    'user',                        // Entity type
    $_SESSION['user_id'],          // Entity ID
    ['method' => 'self_service'],   // Details
    $_SESSION['user_id'],          // User ID
    'user'                         // User role
);
```

**Using Constants:**
```php
logAuditEvent(AUDIT_PASSWORD_CHANGED, 'user', $userId, [
    'method' => 'self_service',
    'ip_address' => getClientIP()
], $userId, 'user');
```

---

## 🛡️ Security Benefits

### Account Lockout
- ✅ Prevents brute force attacks
- ✅ Reduces credential stuffing success rate
- ✅ Protects against automated attacks
- ✅ Provides clear feedback to legitimate users

### Role-Based Access Control
- ✅ Prevents privilege escalation
- ✅ Ensures users can only access authorized resources
- ✅ Centralized authorization logic
- ✅ Easy to maintain and update

### Audit Logging
- ✅ Security monitoring and alerting
- ✅ Forensic analysis capabilities
- ✅ Compliance requirements (if applicable)
- ✅ Detection of suspicious activity
- ✅ Accountability for all actions

---

## 🔍 Monitoring and Maintenance

### Regular Security Checks

1. **Review Audit Logs Weekly:**
   ```sql
   SELECT action, COUNT(*) as count, MAX(created_at) as last_occurrence
   FROM audit_log
   WHERE created_at > NOW() - INTERVAL '7 days'
   GROUP BY action
   ORDER BY count DESC;
   ```

2. **Check for Account Lockouts:**
   ```sql
   SELECT email, failed_login_attempts, account_locked_until
   FROM users
   WHERE account_locked_until IS NOT NULL
   AND account_locked_until > NOW();
   ```

3. **Monitor Failed Login Attempts:**
   ```sql
   SELECT * FROM audit_log
   WHERE action = 'login_failed'
   AND created_at > NOW() - INTERVAL '24 hours'
   ORDER BY created_at DESC
   LIMIT 50;
   ```

### Maintenance Tasks

- **Clean Old Audit Logs:** (Optional, based on retention policy)
  ```sql
  DELETE FROM audit_log WHERE created_at < NOW() - INTERVAL '90 days';
  ```

- **Unlock Accounts Manually:** (If needed)
  ```sql
  UPDATE users 
  SET failed_login_attempts = 0, 
      account_locked_until = NULL 
  WHERE email = 'user@example.com';
  ```

---

## 🚨 Security Incident Response

### If Account Lockout is Triggered

1. **Legitimate User:**
   - Wait 30 minutes for automatic unlock
   - Or contact administrator for manual unlock

2. **Administrator Response:**
   - Check audit logs for suspicious activity
   - Verify if legitimate user or attack
   - Unlock account if legitimate
   - Investigate if attack pattern detected

### If Unauthorized Access Detected

1. Check audit logs for the affected user
2. Review IP addresses and user agents
3. Check for privilege escalation attempts
4. Lock affected accounts immediately
5. Force password reset for affected users

---

## 📊 Security Metrics

Track these metrics regularly:

- **Failed Login Attempts:** Should be low for legitimate users
- **Account Lockouts:** Should be rare for legitimate users
- **Unauthorized Access Attempts:** Should be zero
- **Audit Log Size:** Monitor growth, implement retention policy
- **Average Time to Detect:** How quickly suspicious activity is detected

---

## 🔄 Future Enhancements

Potential improvements (not yet implemented):

- [ ] Two-factor authentication (2FA)
- [ ] IP-based lockout (lock IP after multiple failed attempts)
- [ ] Email notifications for account lockouts
- [ ] Admin dashboard for viewing audit logs
- [ ] Automated alerting for suspicious activity
- [ ] Session management (concurrent session limits)
- [ ] Password expiration policy
- [ ] CAPTCHA integration for additional protection

---

## 📝 Testing

### Test Account Lockout

1. Attempt to login with wrong password 5 times
2. Verify account is locked
3. Wait 30 minutes or manually unlock
4. Verify account can login again

### Test Authorization

1. Try accessing admin page as regular user → Should be blocked
2. Try accessing staff page as regular user → Should be blocked
3. Try accessing user page without login → Should redirect to login

### Test Audit Logging

1. Perform various actions (login, create appointment, etc.)
2. Check `audit_log` table for entries
3. Verify all details are captured correctly

---

## ⚠️ Important Notes

1. **Account Lockout:** Legitimate users may be temporarily locked out. Provide clear instructions and support contact.

2. **Audit Logs:** Can grow large over time. Implement a retention policy based on your requirements.

3. **Performance:** Audit logging is non-blocking. If logging fails, the main operation still proceeds.

4. **Database Migration:** Run `add_account_lockout_columns.php` once to add required columns.

5. **Backward Compatibility:** Existing accounts default to unlocked state (0 failed attempts).

---

## 📞 Support

For security-related questions or issues:
- Review this documentation
- Check audit logs for details
- Contact system administrator

---

**Document Version:** 2.0  
**Last Updated:** January 2026  
**Maintained By:** Development Team
