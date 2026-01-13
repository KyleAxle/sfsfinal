<?php
/**
 * Send a message from user to staff or staff to user
 * POST: sender_type, recipient_type, recipient_id, message
 */

session_start();
header('Content-Type: application/json');

$pdo = require __DIR__ . '/config/db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $senderType = $input['sender_type'] ?? '';
    $recipientType = $input['recipient_type'] ?? '';
    $recipientId = isset($input['recipient_id']) ? (int)$input['recipient_id'] : 0;
    $message = trim($input['message'] ?? '');

    if (empty($message)) {
        throw new Exception('Message cannot be empty');
    }

    if (!in_array($senderType, ['user', 'staff'])) {
        throw new Exception('Invalid sender type');
    }

    if (!in_array($recipientType, ['user', 'staff'])) {
        throw new Exception('Invalid recipient type');
    }

    if ($senderType === $recipientType) {
        throw new Exception('Cannot send message to same type');
    }

    // Verify sender authentication
    $senderUserId = null;
    $senderStaffId = null;

    if ($senderType === 'user') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('User not authenticated');
        }
        $senderUserId = (int)$_SESSION['user_id'];
    } else {
        if (!isset($_SESSION['staff_id'])) {
            throw new Exception('Staff not authenticated');
        }
        $senderStaffId = (int)$_SESSION['staff_id'];
    }

    // Set recipient IDs
    $recipientUserId = null;
    $recipientStaffId = null;

    if ($recipientType === 'user') {
        $recipientUserId = $recipientId;
    } else {
        // If user is sending to staff, recipient_id might be office_id
        // Find a staff member from that office
        if ($senderType === 'user') {
            // Check if recipientId is an office_id
            $officeCheck = $pdo->prepare("
                SELECT staff_id, office_id 
                FROM public.staff 
                WHERE office_id = ? 
                LIMIT 1
            ");
            $officeCheck->execute([$recipientId]);
            $staffFromOffice = $officeCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($staffFromOffice) {
                // recipientId is office_id, use the staff_id
                $recipientStaffId = (int)$staffFromOffice['staff_id'];
            } else {
                // recipientId is staff_id, use it directly
                $recipientStaffId = $recipientId;
            }
        } else {
            // Staff sending to user - use recipientId directly
            $recipientStaffId = $recipientId;
        }
    }

    if ($recipientId <= 0) {
        throw new Exception('Invalid recipient ID');
    }
    
    // Final validation
    if ($recipientType === 'user' && $recipientUserId <= 0) {
        throw new Exception('Invalid recipient user ID');
    }
    if ($recipientType === 'staff' && $recipientStaffId <= 0) {
        throw new Exception('Invalid recipient staff ID or office ID');
    }

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO public.messages (
            sender_type, sender_user_id, sender_staff_id,
            recipient_type, recipient_user_id, recipient_staff_id,
            message, is_read
        ) VALUES (?, ?, ?, ?, ?, ?, ?, FALSE)
        RETURNING message_id, created_at
    ");

    $stmt->execute([
        $senderType,
        $senderUserId,
        $senderStaffId,
        $recipientType,
        $recipientUserId,
        $recipientStaffId,
        $message
    ]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message_id' => $result['message_id'],
        'created_at' => $result['created_at']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

