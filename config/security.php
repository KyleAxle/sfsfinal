<?php
/**
 * Security Configuration and Helpers
 * Provides CSRF protection, input validation, rate limiting, and security headers
 */

// Security Headers
function setSecurityHeaders() {
    // Prevent XSS attacks
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (adjust based on your needs)
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://www.gstatic.com https://apis.google.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self' https://api.groq.com https://www.iprogsms.com https://sms.iprogtech.com; " .
           "frame-src 'self' https://accounts.google.com;";
    header("Content-Security-Policy: $csp");
    
    // HSTS (only if HTTPS is available)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// CSRF Token Management
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Input Sanitization
function sanitizeInput($input, $type = 'string') {
    if (is_array($input)) {
        return array_map(function($item) use ($type) {
            return sanitizeInput($item, $type);
        }, $input);
    }
    
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        case 'html':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Input Validation
function validateInput($input, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $ruleSet) {
        $value = $input[$field] ?? null;
        $required = $ruleSet['required'] ?? false;
        
        // Check required
        if ($required && (is_null($value) || $value === '')) {
            $errors[$field] = ($ruleSet['message'] ?? "{$field} is required");
            continue;
        }
        
        // Skip other validations if field is empty and not required
        if (empty($value) && !$required) {
            continue;
        }
        
        // Type validation
        if (isset($ruleSet['type'])) {
            switch ($ruleSet['type']) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = ($ruleSet['message'] ?? "Invalid email format");
                    }
                    break;
                case 'int':
                    if (!is_numeric($value) || (int)$value != $value) {
                        $errors[$field] = ($ruleSet['message'] ?? "{$field} must be an integer");
                    }
                    break;
                case 'date':
                    $date = DateTime::createFromFormat('Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        $errors[$field] = ($ruleSet['message'] ?? "Invalid date format");
                    }
                    break;
                case 'time':
                    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:00)?$/', $value)) {
                        $errors[$field] = ($ruleSet['message'] ?? "Invalid time format");
                    }
                    break;
            }
        }
        
        // Length validation
        if (isset($ruleSet['min_length']) && strlen($value) < $ruleSet['min_length']) {
            $errors[$field] = ($ruleSet['message'] ?? "{$field} must be at least {$ruleSet['min_length']} characters");
        }
        if (isset($ruleSet['max_length']) && strlen($value) > $ruleSet['max_length']) {
            $errors[$field] = ($ruleSet['message'] ?? "{$field} must be no more than {$ruleSet['max_length']} characters");
        }
        
        // Range validation
        if (isset($ruleSet['min']) && is_numeric($value) && $value < $ruleSet['min']) {
            $errors[$field] = ($ruleSet['message'] ?? "{$field} must be at least {$ruleSet['min']}");
        }
        if (isset($ruleSet['max']) && is_numeric($value) && $value > $ruleSet['max']) {
            $errors[$field] = ($ruleSet['message'] ?? "{$field} must be no more than {$ruleSet['max']}");
        }
        
        // Pattern validation
        if (isset($ruleSet['pattern']) && !preg_match($ruleSet['pattern'], $value)) {
            $errors[$field] = ($ruleSet['message'] ?? "Invalid {$field} format");
        }
    }
    
    return $errors;
}

// Rate Limiting
function checkRateLimit($key, $maxAttempts = 5, $windowSeconds = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $attempts = $_SESSION['rate_limit'][$key] ?? [];
    
    // Remove old attempts outside the time window
    $attempts = array_filter($attempts, function($timestamp) use ($now, $windowSeconds) {
        return ($now - $timestamp) < $windowSeconds;
    });
    
    // Check if limit exceeded
    if (count($attempts) >= $maxAttempts) {
        $oldestAttempt = min($attempts);
        $waitTime = $windowSeconds - ($now - $oldestAttempt);
        return [
            'allowed' => false,
            'wait_time' => $waitTime,
            'message' => "Too many attempts. Please wait " . ceil($waitTime / 60) . " minutes."
        ];
    }
    
    // Record this attempt
    $attempts[] = $now;
    $_SESSION['rate_limit'][$key] = $attempts;
    
    return ['allowed' => true];
}

// SQL Injection Prevention Helper (ensure PDO is used)
function safeQuery($pdo, $sql, $params = []) {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQL Error: " . $e->getMessage());
        throw new Exception("Database error occurred");
    }
}

// XSS Prevention - Output Escaping
function escapeOutput($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// File Upload Security
function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'], $maxSize = 5242880) {
    $errors = [];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = "Invalid file upload";
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds maximum allowed size";
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        $errors[] = "File type not allowed";
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $allowedExtensions)) {
        $errors[] = "File extension not allowed";
    }
    
    return $errors;
}

// Password Strength Validation
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    return $errors;
}

// Initialize security on page load
if (session_status() === PHP_SESSION_ACTIVE) {
    setSecurityHeaders();
}

?>
