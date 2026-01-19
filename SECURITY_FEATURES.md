# 🔒 Security Features Documentation

## Overview

This document describes the security features implemented in the SFS Appointment System to protect against common web vulnerabilities.

## Implemented Security Features

### 1. ✅ Password Security
- **Password Hashing**: All passwords are hashed using PHP's `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- **Password Verification**: Uses `password_verify()` for secure password checking
- **Password Strength**: Validation requires:
  - Minimum 8 characters
  - At least one uppercase letter
  - At least one lowercase letter
  - At least one number

### 2. ✅ SQL Injection Prevention
- **Prepared Statements**: All database queries use PDO prepared statements
- **Parameter Binding**: User input is always bound as parameters, never concatenated into SQL
- **Safe Query Helper**: `safeQuery()` function ensures proper parameter binding

### 3. ✅ XSS (Cross-Site Scripting) Protection
- **Output Escaping**: `htmlspecialchars()` used for all user-generated content
- **Input Sanitization**: `sanitizeInput()` function cleans all user input
- **Content Security Policy**: CSP headers restrict script execution

### 4. ✅ CSRF (Cross-Site Request Forgery) Protection
- **CSRF Tokens**: Token generation and validation functions available
- **Session-based Tokens**: Tokens stored in session, validated on form submission
- **SameSite Cookies**: Session cookies use SameSite=Lax attribute

### 5. ✅ Rate Limiting
- **Login Rate Limiting**: 
  - Maximum 5 attempts per 15 minutes per email
  - Prevents brute force attacks
- **Registration Rate Limiting**:
  - Maximum 3 attempts per hour per IP
  - Prevents spam registrations
- **Password Reset Rate Limiting**:
  - Maximum 3 attempts per hour per email
  - Prevents abuse

### 6. ✅ Security Headers
- **X-XSS-Protection**: Enables browser XSS filter
- **X-Frame-Options**: Prevents clickjacking (DENY)
- **X-Content-Type-Options**: Prevents MIME type sniffing
- **Content-Security-Policy**: Restricts resource loading
- **Strict-Transport-Security**: Enforces HTTPS (when available)
- **Referrer-Policy**: Controls referrer information

### 7. ✅ Input Validation
- **Type Validation**: Email, integer, date, time validation
- **Length Validation**: Min/max length checks
- **Range Validation**: Numeric range checks
- **Pattern Validation**: Regex pattern matching
- **Required Field Validation**: Ensures required fields are present

### 8. ✅ Session Security
- **Secure Cookies**: HttpOnly flag prevents JavaScript access
- **SameSite Cookies**: CSRF protection via SameSite attribute
- **Session Regeneration**: Session ID regenerated every 30 minutes
- **Strict Mode**: Prevents session fixation attacks
- **Cookie Lifetime**: 30-day expiration with secure parameters

### 9. ✅ File Upload Security
- **MIME Type Validation**: Checks actual file type, not just extension
- **File Size Limits**: Maximum 5MB default
- **Allowed Types**: Only image types (JPEG, PNG, GIF)
- **Extension Validation**: Checks file extension matches MIME type
- **Upload Verification**: Validates file is actually uploaded

### 10. ✅ Error Handling
- **Error Logging**: All errors logged, not displayed to users
- **Generic Error Messages**: Prevents information disclosure
- **Database Error Handling**: Catches and logs database errors safely

## Usage Examples

### Using CSRF Protection

**In Forms:**
```php
<?php
require_once __DIR__ . '/config/security.php';
$csrfToken = generateCSRFToken();
?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
    <!-- form fields -->
</form>
```

**In Processing Scripts:**
```php
<?php
require_once __DIR__ . '/config/security.php';

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    die('Invalid CSRF token');
}
// Process form
?>
```

### Using Input Sanitization

```php
<?php
require_once __DIR__ . '/config/security.php';

$email = sanitizeInput($_POST['email'], 'email');
$name = sanitizeInput($_POST['name'], 'string');
$age = sanitizeInput($_POST['age'], 'int');
?>
```

### Using Input Validation

```php
<?php
require_once __DIR__ . '/config/security.php';

$rules = [
    'email' => [
        'required' => true,
        'type' => 'email',
        'max_length' => 100
    ],
    'age' => [
        'required' => true,
        'type' => 'int',
        'min' => 1,
        'max' => 150
    ]
];

$errors = validateInput($_POST, $rules);
if (!empty($errors)) {
    // Handle validation errors
}
?>
```

### Using Rate Limiting

```php
<?php
require_once __DIR__ . '/config/rate_limit.php';

$rateLimit = checkLoginRateLimit($email);
if (!$rateLimit['allowed']) {
    die($rateLimit['message']);
}
// Process login
?>
```

## Security Best Practices

### For Developers

1. **Always use prepared statements** - Never concatenate user input into SQL
2. **Sanitize all input** - Use `sanitizeInput()` before processing
3. **Escape all output** - Use `escapeOutput()` or `htmlspecialchars()` when displaying
4. **Validate input** - Use `validateInput()` with appropriate rules
5. **Use CSRF tokens** - Include tokens in all forms that modify data
6. **Check rate limits** - Use rate limiting for sensitive operations
7. **Log security events** - Log failed login attempts, rate limit violations, etc.

### For Deployment

1. **Use HTTPS** - Always use HTTPS in production
2. **Keep PHP updated** - Use latest stable PHP version
3. **Update dependencies** - Keep all libraries updated
4. **Monitor logs** - Regularly check error logs for suspicious activity
5. **Backup regularly** - Maintain regular database backups
6. **Restrict file permissions** - Use appropriate file permissions (644 for files, 755 for directories)

## Security Checklist

- [x] Password hashing implemented
- [x] SQL injection prevention (prepared statements)
- [x] XSS protection (input sanitization, output escaping)
- [x] CSRF protection (tokens available)
- [x] Rate limiting (login, registration, password reset)
- [x] Security headers configured
- [x] Input validation implemented
- [x] Session security enhanced
- [x] File upload security
- [x] Error handling secured

## Future Enhancements

- [ ] Two-factor authentication (2FA)
- [ ] Account lockout after multiple failed attempts
- [ ] IP whitelisting for admin access
- [ ] Audit logging for sensitive operations
- [ ] Password expiration policy
- [ ] CAPTCHA for registration/login
- [ ] Email verification for new accounts

## Reporting Security Issues

If you discover a security vulnerability, please:
1. Do not disclose it publicly
2. Contact the development team immediately
3. Provide detailed information about the vulnerability
4. Allow time for the issue to be fixed before disclosure

---

**Last Updated**: January 2026
**Version**: 1.0
