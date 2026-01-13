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

    // Get distinct conversations with latest message info using a simpler approach
    $stmt = $pdo->prepare("
        WITH conversation_messages AS (
            SELECT 
                CASE 
                    WHEN m.sender_type = ? THEN 
                        CASE WHEN m.sender_type = 'user' THEN m.sender_user_id ELSE m.sender_staff_id END
                    ELSE 
                        CASE WHEN m.recipient_type = 'user' THEN m.recipient_user_id ELSE m.recipient_staff_id END
                END as other_id,
                CASE 
                    WHEN m.sender_type = ? THEN m.sender_type
                    ELSE m.recipient_type
                END as other_type,
                m.message,
                m.created_at,
                m.is_read,
                ROW_NUMBER() OVER (
                    PARTITION BY 
                        CASE 
                            WHEN m.sender_type = ? THEN 
                                CASE WHEN m.sender_type = 'user' THEN m.sender_user_id ELSE m.sender_staff_id END
                            ELSE 
                                CASE WHEN m.recipient_type = 'user' THEN m.recipient_user_id ELSE m.recipient_staff_id END
                        END
                    ORDER BY m.created_at DESC
                ) as rn
            FROM public.messages m
            WHERE (
                (m.sender_type = ? AND 
                 CASE WHEN m.sender_type = 'user' THEN m.sender_user_id ELSE m.sender_staff_id END = ?)
                OR
                (m.recipient_type = ? AND 
                 CASE WHEN m.recipient_type = 'user' THEN m.recipient_user_id ELSE m.recipient_staff_id END = ?)
            )
        )
        SELECT 
            cm.other_id,
            cm.other_type,
            cm.message as last_message,
            cm.created_at as last_message_time,
            cm.is_read,
            CASE 
                WHEN cm.other_type = 'user' THEN u.first_name || ' ' || u.last_name
                WHEN cm.other_type = 'staff' THEN s.email
                ELSE 'Unknown'
            END as other_name,
            (SELECT COUNT(*) FROM public.messages m2 
             WHERE m2.recipient_type = ? 
             AND CASE WHEN m2.recipient_type = 'user' THEN m2.recipient_user_id ELSE m2.recipient_staff_id END = ?
             AND m2.sender_type = cm.other_type
             AND CASE WHEN m2.sender_type = 'user' THEN m2.sender_user_id ELSE m2.sender_staff_id END = cm.other_id
             AND m2.is_read = FALSE) as unread_count
        FROM conversation_messages cm
        LEFT JOIN public.users u ON cm.other_type = 'user' AND cm.other_id = u.user_id
        LEFT JOIN public.staff s ON cm.other_type = 'staff' AND cm.other_id = s.staff_id
        WHERE cm.rn = 1
        ORDER BY cm.created_at DESC
    ");

    $stmt->execute([
        $currentType, $currentType, $currentType,
        $currentType, $currentId,
        $currentType, $currentId,
        $currentType, $currentId
    ]);

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
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

