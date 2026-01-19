<?php
/**
 * Account Lockout Management
 * Handles account lockout after failed login attempts
 */

require_once __DIR__ . '/db.php';

// Lockout configuration
define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 1800); // 30 minutes in seconds
define('RESET_ATTEMPTS_AFTER', 900); // 15 minutes - reset counter after successful wait

/**
 * Check if account is locked
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name (users, staff, admins)
 * @param string $identifierColumn Column to identify user (email, user_id, etc.)
 * @param string $identifierValue Value to identify user
 * @return array ['locked' => bool, 'locked_until' => timestamp|null, 'message' => string]
 */
function checkAccountLockout($pdo, $table, $identifierColumn, $identifierValue) {
    $stmt = $pdo->prepare("
        SELECT 
            failed_login_attempts,
            account_locked_until,
            last_failed_login_at
        FROM public.{$table}
        WHERE {$identifierColumn} = ?
        LIMIT 1
    ");
    $stmt->execute([$identifierValue]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['locked' => false, 'locked_until' => null, 'message' => ''];
    }
    
    $failedAttempts = (int)($user['failed_login_attempts'] ?? 0);
    $lockedUntil = $user['account_locked_until'] ?? null;
    
    // Check if account is currently locked
    if ($lockedUntil) {
        $lockedTime = new DateTime($lockedUntil);
        $now = new DateTime();
        
        if ($lockedTime > $now) {
            $minutesRemaining = ceil(($lockedTime->getTimestamp() - $now->getTimestamp()) / 60);
            return [
                'locked' => true,
                'locked_until' => $lockedUntil,
                'message' => "Account is locked due to too many failed login attempts. Please try again in {$minutesRemaining} minutes."
            ];
        } else {
            // Lockout expired, reset attempts
            resetFailedAttempts($pdo, $table, $identifierColumn, $identifierValue);
            return ['locked' => false, 'locked_until' => null, 'message' => ''];
        }
    }
    
    return ['locked' => false, 'locked_until' => null, 'message' => ''];
}

/**
 * Record failed login attempt
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param string $identifierColumn Column to identify user
 * @param string $identifierValue Value to identify user
 * @return array ['locked' => bool, 'attempts' => int, 'message' => string]
 */
function recordFailedAttempt($pdo, $table, $identifierColumn, $identifierValue) {
    // Get current failed attempts
    $stmt = $pdo->prepare("
        SELECT failed_login_attempts, account_locked_until
        FROM public.{$table}
        WHERE {$identifierColumn} = ?
        LIMIT 1
    ");
    $stmt->execute([$identifierValue]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return ['locked' => false, 'attempts' => 0, 'message' => ''];
    }
    
    $failedAttempts = (int)($user['failed_login_attempts'] ?? 0) + 1;
    $lockedUntil = $user['account_locked_until'] ?? null;
    
    // Check if lockout expired
    if ($lockedUntil) {
        $lockedTime = new DateTime($lockedUntil);
        $now = new DateTime();
        if ($lockedTime <= $now) {
            // Lockout expired, reset
            $failedAttempts = 1;
        }
    }
    
    // Lock account if max attempts reached
    if ($failedAttempts >= MAX_FAILED_ATTEMPTS) {
        $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
        
        $updateStmt = $pdo->prepare("
            UPDATE public.{$table}
            SET failed_login_attempts = ?,
                account_locked_until = ?,
                last_failed_login_at = NOW()
            WHERE {$identifierColumn} = ?
        ");
        $updateStmt->execute([$failedAttempts, $lockUntil, $identifierValue]);
        
        error_log("Account locked: {$table} - {$identifierColumn} = {$identifierValue} after {$failedAttempts} failed attempts");
        
        return [
            'locked' => true,
            'attempts' => $failedAttempts,
            'message' => "Account has been locked due to too many failed login attempts. Please try again in " . ceil(LOCKOUT_DURATION / 60) . " minutes."
        ];
    } else {
        // Update failed attempts count
        $updateStmt = $pdo->prepare("
            UPDATE public.{$table}
            SET failed_login_attempts = ?,
                last_failed_login_at = NOW()
            WHERE {$identifierColumn} = ?
        ");
        $updateStmt->execute([$failedAttempts, $identifierValue]);
        
        $remainingAttempts = MAX_FAILED_ATTEMPTS - $failedAttempts;
        return [
            'locked' => false,
            'attempts' => $failedAttempts,
            'message' => "Invalid credentials. {$remainingAttempts} attempt(s) remaining before account lockout."
        ];
    }
}

/**
 * Reset failed login attempts on successful login
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param string $identifierColumn Column to identify user
 * @param string $identifierValue Value to identify user
 */
function resetFailedAttempts($pdo, $table, $identifierColumn, $identifierValue) {
    $updateStmt = $pdo->prepare("
        UPDATE public.{$table}
        SET failed_login_attempts = 0,
            account_locked_until = NULL,
            last_failed_login_at = NULL
        WHERE {$identifierColumn} = ?
    ");
    $updateStmt->execute([$identifierValue]);
    
    error_log("Failed attempts reset: {$table} - {$identifierColumn} = {$identifierValue}");
}

/**
 * Get remaining login attempts
 * 
 * @param PDO $pdo Database connection
 * @param string $table Table name
 * @param string $identifierColumn Column to identify user
 * @param string $identifierValue Value to identify user
 * @return int Remaining attempts
 */
function getRemainingAttempts($pdo, $table, $identifierColumn, $identifierValue) {
    $stmt = $pdo->prepare("
        SELECT failed_login_attempts
        FROM public.{$table}
        WHERE {$identifierColumn} = ?
        LIMIT 1
    ");
    $stmt->execute([$identifierValue]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        return MAX_FAILED_ATTEMPTS;
    }
    
    $failedAttempts = (int)($user['failed_login_attempts'] ?? 0);
    return max(0, MAX_FAILED_ATTEMPTS - $failedAttempts);
}

?>
