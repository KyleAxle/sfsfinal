<?php
/**
 * Get list of conversations for current user/staff
 * Returns list of people they've chatted with
 */

session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/config/db.php';

try {
    $currentType = '';
    $currentId = 0;

    // Determine current user type
    if (isset($_SESSION['user_id'])) {
        $currentType = 'user';
        $currentId = (int)$_SESSION['user_id'];
    } elseif (isset($_SESSION['staff_id'])) {
        $currentType = 'staff';
        $currentId = (int)$_SESSION['staff_id'];
    } else {
        throw new Exception('Not authenticated');
    }

    $otherType = $currentType === 'user' ? 'staff' : 'user';

    // Get distinct conversations grouped by OFFICE (not by staff)
    // This ensures all messages from the same office appear as one conversation
    if ($currentType === 'user') {
        $stmt = $pdo->prepare("
            WITH office_messages AS (
                SELECT 
                    COALESCE(o.office_id, s.office_id) as office_id,
                    COALESCE(o.office_name, s.office_name, 'Staff Office') as office_name,
                    m.message,
                    m.created_at,
                    m.is_read,
                    m.sender_staff_id,
                    ROW_NUMBER() OVER (
                        PARTITION BY COALESCE(o.office_id, s.office_id)
                        ORDER BY m.created_at DESC
                    ) as rn
                FROM public.messages m
                LEFT JOIN public.staff s ON m.sender_staff_id = s.staff_id OR m.recipient_staff_id = s.staff_id
                LEFT JOIN public.offices o ON s.office_id = o.office_id
                WHERE (
                    (m.sender_type = 'staff' AND m.recipient_type = 'user' AND m.recipient_user_id = ?)
                    OR
                    (m.sender_type = 'user' AND m.sender_user_id = ? AND m.recipient_type = 'staff')
                )
                AND COALESCE(o.office_id, s.office_id) IS NOT NULL
            )
            SELECT 
                om.office_id as other_id,
                'staff' as other_type,
                om.message as last_message,
                om.created_at as last_message_time,
                om.is_read,
                om.office_name as other_name,
                (SELECT COUNT(*) FROM public.messages m2
                 LEFT JOIN public.staff s2 ON m2.sender_staff_id = s2.staff_id
                 LEFT JOIN public.offices o2 ON s2.office_id = o2.office_id
                 WHERE m2.recipient_type = 'user' 
                 AND m2.recipient_user_id = ?
                 AND m2.sender_type = 'staff'
                 AND COALESCE(o2.office_id, s2.office_id) = om.office_id
                 AND m2.is_read = FALSE) as unread_count
            FROM office_messages om
            WHERE om.rn = 1
            ORDER BY om.created_at DESC
        ");
        $params = [$currentId, $currentId, $currentId];
    } else {
        // Staff viewing conversations with users (keep original logic)
        $stmt = $pdo->prepare("
            WITH conversation_messages AS (
                SELECT 
                    CASE 
                        WHEN m.sender_type = 'staff' THEN m.recipient_user_id
                        ELSE m.sender_user_id
                    END as other_id,
                    'user' as other_type,
                    m.message,
                    m.created_at,
                    m.is_read,
                    ROW_NUMBER() OVER (
                        PARTITION BY 
                            CASE 
                                WHEN m.sender_type = 'staff' THEN m.recipient_user_id
                                ELSE m.sender_user_id
                            END
                        ORDER BY m.created_at DESC
                    ) as rn
                FROM public.messages m
                WHERE (
                    (m.sender_type = 'staff' AND m.sender_staff_id = ?)
                    OR
                    (m.recipient_type = 'staff' AND m.recipient_staff_id = ?)
                )
            )
            SELECT 
                cm.other_id,
                cm.other_type,
                cm.message as last_message,
                cm.created_at as last_message_time,
                cm.is_read,
                u.first_name || ' ' || u.last_name as other_name,
                (SELECT COUNT(*) FROM public.messages m2 
                 WHERE m2.recipient_type = 'staff' 
                 AND m2.recipient_staff_id = ?
                 AND m2.sender_type = 'user'
                 AND m2.sender_user_id = cm.other_id
                 AND m2.is_read = FALSE) as unread_count
            FROM conversation_messages cm
            LEFT JOIN public.users u ON cm.other_id = u.user_id
            WHERE cm.rn = 1
            ORDER BY cm.created_at DESC
        ");
        $params = [$currentId, $currentId, $currentId];
    }

    $stmt->execute($params);

    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If user, get all staff for starting new conversations
    $availableStaff = [];
    if ($currentType === 'user') {
        $staffStmt = $pdo->query("
            SELECT staff_id, email, office_name
            FROM public.staff
            ORDER BY office_name, email
        ");
        $availableStaff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // If staff, get all users for starting new conversations
    $availableUsers = [];
    if ($currentType === 'staff') {
        $usersStmt = $pdo->query("
            SELECT user_id, first_name, last_name, email
            FROM public.users
            ORDER BY first_name, last_name
        ");
        $availableUsers = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'available_staff' => $availableStaff,
        'available_users' => $availableUsers
    ]);
} catch (Exception $e) {
    error_log('get_conversations.php error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log('get_conversations.php PDO error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

