<?php
/**
 * Audit Logging System
 * Logs sensitive operations for security monitoring
 */

require_once __DIR__ . '/db.php';

/**
 * Log an audit event
 * 
 * @param string $action Action performed (e.g., 'login', 'appointment_created', 'password_changed')
 * @param string $entityType Type of entity (e.g., 'user', 'appointment', 'office')
 * @param int|null $entityId ID of the entity
 * @param array $details Additional details about the action
 * @param string|null $userId User ID who performed the action
 * @param string|null $userRole Role of user (user, staff, admin)
 * @return bool True if logged successfully
 */
function logAuditEvent($action, $entityType, $entityId = null, $details = [], $userId = null, $userRole = null) {
    try {
        $pdo = require __DIR__ . '/db.php';
        
        // Get current user info if not provided
        if ($userId === null) {
            $userId = getCurrentUserId();
        }
        
        if ($userRole === null) {
            $userRole = getCurrentUserRole();
        }
        
        // Get IP address
        $ipAddress = getClientIP();
        
        // Get user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Check if audit_log table exists
        $tableExists = $pdo->query("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_name = 'audit_log'
            )
        ")->fetchColumn();
        
        if (!$tableExists) {
            // Create audit_log table if it doesn't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS public.audit_log (
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
                )
            ");
            
            // Create indexes
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_log_action ON public.audit_log(action)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_log_user ON public.audit_log(user_id)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_audit_log_created ON public.audit_log(created_at)");
        }
        
        // Insert audit log entry
        $stmt = $pdo->prepare("
            INSERT INTO public.audit_log 
            (action, entity_type, entity_id, user_id, user_role, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?::jsonb)
        ");
        
        $detailsJson = !empty($details) ? json_encode($details) : null;
        
        $stmt->execute([
            $action,
            $entityType,
            $entityId,
            $userId,
            $userRole,
            $ipAddress,
            $userAgent,
            $detailsJson
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Don't fail the main operation if audit logging fails
        error_log("Audit logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get client IP address
 * 
 * @return string IP address
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

// Note: getCurrentUserId() and getCurrentUserRole() are defined in config/authorization.php
// If authorization.php is not loaded, define them here as fallback
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        if (isset($_SESSION['admin_id'])) {
            return (int)$_SESSION['admin_id'];
        } elseif (isset($_SESSION['staff_id'])) {
            return (int)$_SESSION['staff_id'];
        } elseif (isset($_SESSION['user_id'])) {
            return (int)$_SESSION['user_id'];
        }
        return null;
    }
}

if (!function_exists('getCurrentUserRole')) {
    function getCurrentUserRole() {
        if (isset($_SESSION['admin_id'])) {
            return 'admin';
        } elseif (isset($_SESSION['staff_id'])) {
            return 'staff';
        } elseif (isset($_SESSION['user_id'])) {
            return 'user';
        }
        return null;
    }
}

// Common audit actions
define('AUDIT_LOGIN', 'login');
define('AUDIT_LOGIN_FAILED', 'login_failed');
define('AUDIT_LOGOUT', 'logout');
define('AUDIT_APPOINTMENT_CREATED', 'appointment_created');
define('AUDIT_APPOINTMENT_UPDATED', 'appointment_updated');
define('AUDIT_APPOINTMENT_DELETED', 'appointment_deleted');
define('AUDIT_PASSWORD_CHANGED', 'password_changed');
define('AUDIT_PROFILE_UPDATED', 'profile_updated');
define('AUDIT_OFFICE_CREATED', 'office_created');
define('AUDIT_OFFICE_DELETED', 'office_deleted');
define('AUDIT_ACCOUNT_LOCKED', 'account_locked');
define('AUDIT_EMAIL_VERIFIED', 'email_verified');

?>
