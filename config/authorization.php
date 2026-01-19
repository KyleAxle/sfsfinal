<?php
/**
 * Authorization and Access Control
 * Provides role-based access control and authorization checks
 */

/**
 * Require user to be authenticated
 * Redirects to login if not authenticated
 */
function requireAuth() {
    if (!isset($_SESSION['user_id']) && !isset($_SESSION['staff_id']) && !isset($_SESSION['admin_id'])) {
        if (strpos($_SERVER['REQUEST_URI'], 'api') !== false || 
            strpos($_SERVER['REQUEST_URI'], '.php') !== false && 
            strpos($_SERVER['REQUEST_URI'], 'login') === false) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Please log in to continue']);
            exit;
        } else {
            header('Location: login.html');
            exit;
        }
    }
}

/**
 * Require user role (user, staff, or admin)
 * 
 * @param string|array $requiredRoles Role(s) required to access
 * @param bool $redirect Whether to redirect or return false
 * @return bool True if authorized, false otherwise
 */
function requireRole($requiredRoles, $redirect = true) {
    requireAuth();
    
    $userRole = null;
    
    if (isset($_SESSION['admin_id'])) {
        $userRole = 'admin';
    } elseif (isset($_SESSION['staff_id'])) {
        $userRole = 'staff';
    } elseif (isset($_SESSION['user_id'])) {
        $userRole = 'user';
    }
    
    if ($userRole === null) {
        if ($redirect) {
            http_response_code(403);
            die('Access denied: Not authenticated');
        }
        return false;
    }
    
    $roles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];
    
    if (!in_array($userRole, $roles)) {
        if ($redirect) {
            http_response_code(403);
            die('Access denied: Insufficient privileges');
        }
        return false;
    }
    
    return true;
}

/**
 * Require admin role
 */
function requireAdmin() {
    requireRole('admin');
}

/**
 * Require staff role
 */
function requireStaff() {
    requireRole(['staff', 'admin']); // Admins can access staff functions
}

/**
 * Require user role
 */
function requireUser() {
    requireRole(['user', 'staff', 'admin']); // All authenticated users
}

/**
 * Check if current user owns a resource
 * 
 * @param string $table Table name
 * @param string $idColumn Column name for ID
 * @param int $resourceId Resource ID to check
 * @param string $ownerColumn Column name for owner (user_id, staff_id, etc.)
 * @return bool True if user owns the resource
 */
function checkResourceOwnership($table, $idColumn, $resourceId, $ownerColumn = 'user_id') {
    requireAuth();
    
    $pdo = require __DIR__ . '/db.php';
    
    // Get current user ID based on role
    $currentUserId = null;
    if (isset($_SESSION['user_id'])) {
        $currentUserId = (int)$_SESSION['user_id'];
    } elseif (isset($_SESSION['staff_id'])) {
        $currentUserId = (int)$_SESSION['staff_id'];
        $ownerColumn = 'staff_id';
    } elseif (isset($_SESSION['admin_id'])) {
        // Admins can access all resources
        return true;
    }
    
    if ($currentUserId === null) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM public.{$table} WHERE {$idColumn} = ? AND {$ownerColumn} = ?");
    $stmt->execute([$resourceId, $currentUserId]);
    
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Verify staff belongs to specific office
 * 
 * @param int $officeId Office ID to verify
 * @return bool True if staff belongs to office
 */
function verifyStaffOffice($officeId) {
    if (!isset($_SESSION['staff_id'])) {
        return false;
    }
    
    // Admins can access all offices
    if (isset($_SESSION['admin_id'])) {
        return true;
    }
    
    $pdo = require __DIR__ . '/db.php';
    $stmt = $pdo->prepare("SELECT office_id FROM public.staff WHERE staff_id = ?");
    $stmt->execute([$_SESSION['staff_id']]);
    $staffOfficeId = $stmt->fetchColumn();
    
    return (int)$staffOfficeId === (int)$officeId;
}

/**
 * Get current user role
 * 
 * @return string|null Role name or null if not authenticated
 */
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

/**
 * Get current user ID
 * 
 * @return int|null User ID or null if not authenticated
 */
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

?>
