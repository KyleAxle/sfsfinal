<?php
/**
 * Get messages for a conversation between user and staff
 * GET: other_type, other_id
 */

session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/config/db.php';

try {
    $currentType = '';
    $currentId = 0;
    $otherType = $_GET['other_type'] ?? '';
    $otherId = isset($_GET['other_id']) ? (int)$_GET['other_id'] : 0;

    // Determine current user type
    // If both user_id and staff_id exist in session, check the referer to determine context
    $hasUserId = isset($_SESSION['user_id']);
    $hasStaffId = isset($_SESSION['staff_id']);
    
    if ($hasStaffId && $hasUserId) {
        // Both exist - determine based on where the request came from
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, 'staff_dashboard') !== false || strpos($referer, 'staff_login') !== false || strpos($referer, 'office_dashboard') !== false) {
            // Coming from staff dashboard - they're staff
            $currentType = 'staff';
            $currentId = (int)$_SESSION['staff_id'];
        } else {
            // Coming from client dashboard, proto2.html, or other user pages - they're a user
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
        throw new Exception('Not authenticated - no user_id or staff_id in session');
    }

    if (empty($otherType)) {
        throw new Exception('Missing other_type parameter');
    }

    if (!in_array($otherType, ['user', 'staff'])) {
        throw new Exception('Invalid other type: ' . $otherType);
    }

    if ($otherId <= 0) {
        throw new Exception('Invalid other ID: ' . $otherId);
    }

    // Debug: Log the types for troubleshooting
    error_log("get_messages.php - currentType: $currentType, currentId: $currentId, otherType: $otherType, otherId: $otherId");

    if ($currentType === $otherType) {
        throw new Exception("Cannot get messages with same type (current: $currentType, other: $otherType). Current ID: $currentId, Other ID: $otherId");
    }

    // Build query based on who is the sender/recipient
    $stmt = $pdo->prepare("
        SELECT 
            m.message_id,
            m.sender_type,
            m.sender_user_id,
            m.sender_staff_id,
            m.recipient_type,
            m.recipient_user_id,
            m.recipient_staff_id,
            m.message,
            m.is_read,
            m.created_at,
            CASE 
                WHEN m.sender_type = 'user' THEN u.first_name || ' ' || u.last_name
                WHEN m.sender_type = 'staff' THEN COALESCE(o.office_name, s.office_name, 'Staff Office')
            END as sender_name
        FROM public.messages m
        LEFT JOIN public.users u ON m.sender_user_id = u.user_id
        LEFT JOIN public.staff s ON m.sender_staff_id = s.staff_id
        LEFT JOIN public.offices o ON s.office_id = o.office_id
        WHERE (
            (m.sender_type = ? AND 
             CASE WHEN m.sender_type = 'user' THEN m.sender_user_id ELSE m.sender_staff_id END = ? 
             AND m.recipient_type = ? AND 
             CASE WHEN m.recipient_type = 'user' THEN m.recipient_user_id ELSE m.recipient_staff_id END = ?)
            OR
            (m.recipient_type = ? AND 
             CASE WHEN m.recipient_type = 'user' THEN m.recipient_user_id ELSE m.recipient_staff_id END = ? 
             AND m.sender_type = ? AND 
             CASE WHEN m.sender_type = 'user' THEN m.sender_user_id ELSE m.sender_staff_id END = ?)
        )
        ORDER BY m.created_at ASC
    ");

    $stmt->execute([
        $currentType, $currentId, $otherType, $otherId,
        $currentType, $currentId, $otherType, $otherId
    ]);

    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mark messages as read if current user is recipient
    if (!empty($messages)) {
        $updateStmt = $pdo->prepare("
            UPDATE public.messages
            SET is_read = TRUE
            WHERE recipient_type = ? 
            AND CASE WHEN recipient_type = 'user' THEN recipient_user_id ELSE recipient_staff_id END = ?
            AND sender_type = ?
            AND CASE WHEN sender_type = 'user' THEN sender_user_id ELSE sender_staff_id END = ?
            AND is_read = FALSE
        ");
        $updateStmt->execute([$currentType, $currentId, $otherType, $otherId]);
    }

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

