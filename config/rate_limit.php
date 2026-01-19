<?php
/**
 * Rate Limiting for Login and Registration
 * Prevents brute force attacks
 */

require_once __DIR__ . '/security.php';

// Rate limit configuration
define('RATE_LIMIT_LOGIN_ATTEMPTS', 5);
define('RATE_LIMIT_LOGIN_WINDOW', 900); // 15 minutes

define('RATE_LIMIT_REGISTER_ATTEMPTS', 3);
define('RATE_LIMIT_REGISTER_WINDOW', 3600); // 1 hour

define('RATE_LIMIT_PASSWORD_RESET_ATTEMPTS', 3);
define('RATE_LIMIT_PASSWORD_RESET_WINDOW', 3600); // 1 hour

/**
 * Check login rate limit
 */
function checkLoginRateLimit($identifier) {
    $key = 'login_' . md5($identifier);
    return checkRateLimit($key, RATE_LIMIT_LOGIN_ATTEMPTS, RATE_LIMIT_LOGIN_WINDOW);
}

/**
 * Check registration rate limit
 */
function checkRegisterRateLimit($ip) {
    $key = 'register_' . md5($ip);
    return checkRateLimit($key, RATE_LIMIT_REGISTER_ATTEMPTS, RATE_LIMIT_REGISTER_WINDOW);
}

/**
 * Check password reset rate limit
 */
function checkPasswordResetRateLimit($email) {
    $key = 'password_reset_' . md5($email);
    return checkRateLimit($key, RATE_LIMIT_PASSWORD_RESET_ATTEMPTS, RATE_LIMIT_PASSWORD_RESET_WINDOW);
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

?>
