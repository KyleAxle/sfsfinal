<?php
/**
 * Get unread message count for current user or staff
 * Returns total count of unread messages
 * 
 * For users: counts unread messages from staff
 * For staff: counts unread messages from users
 */

require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

$pdo = require __DIR__ . '/config/db.php';

try {
    $currentType = '';
    $currentId = 0;
    
    // Determine if user or staff
    $hasUserId = isset($_SESSION['user_id']);
    $hasStaffId = isset($_SESSION['staff_id']);
    
    if ($hasStaffId && $hasUserId) {
        // Both exist - determine based on referer
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'staff_dashboard') !== false || strpos($referer, 'staff_login') !== false) {
            $currentType = 'staff';
            $currentId = (int)$_SESSION['staff_id'];
        } else {
            $currentType = 'user';
            $currentId = (int)$_SESSION['user_id'];
        }
    } elseif ($hasStaffId) {
        $currentType = 'staff';
        $currentId = (int)$_SESSION['staff_id'];
    } elseif ($hasUserId) {
        $currentType = 'user';
        $currentId = (int)$_SESSION['user_id'];
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Not authenticated - no user_id or staff_id in session',
            'count' => 0
        ]);
        exit;
    }
    
    // Count unread messages
    if ($currentType === 'user') {
        // Count unread messages from staff to this user
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count
            FROM public.messages
            WHERE recipient_type = 'user'
              AND recipient_user_id = ?
              AND sender_type = 'staff'
              AND is_read = FALSE
        ");
        $stmt->execute([$currentId]);
    } else {
        // Count unread messages from users to this staff
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count
            FROM public.messages
            WHERE recipient_type = 'staff'
              AND recipient_staff_id = ?
              AND sender_type = 'user'
              AND is_read = FALSE
        ");
        $stmt->execute([$currentId]);
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unreadCount = (int)($result['unread_count'] ?? 0);
    
    echo json_encode([
        'success' => true,
        'count' => $unreadCount,
        'user_type' => $currentType
    ]);
} catch (Exception $e) {
    error_log('Error getting unread count: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'count' => 0
    ]);
}
