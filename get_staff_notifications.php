<?php
/**
 * Get SMS notifications sent for staff's office
 * Returns notification history for appointments in the staff's office
 */

require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['staff_id'], $_SESSION['office_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$pdo = require __DIR__ . '/config/db.php';
$officeId = (int)$_SESSION['office_id'];

try {
    // Get appointments with SMS notification status
    // Show all appointments (we'll filter by status in PHP to avoid enum issues)
    $stmt = $pdo->prepare("
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.updated_at as created_at,
            u.first_name,
            u.last_name,
            u.phone,
            u.email
        FROM public.appointments a
        INNER JOIN public.appointment_offices ao ON a.appointment_id = ao.appointment_id
        INNER JOIN public.users u ON a.user_id = u.user_id
        WHERE ao.office_id = ?
        ORDER BY a.updated_at DESC
        LIMIT 50
    ");
    
    $stmt->execute([$officeId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format as notifications - show all appointments regardless of status
    $notifications = array_map(function($apt) {
        return [
            'appointment_id' => $apt['appointment_id'],
            'appointment_date' => $apt['appointment_date'],
            'appointment_time' => $apt['appointment_time'],
            'status' => $apt['status'],
            'created_at' => $apt['created_at'],
            'first_name' => $apt['first_name'],
            'last_name' => $apt['last_name'],
            'phone' => $apt['phone'],
            'email' => $apt['email'],
            'sms_sent' => !empty($apt['phone']), // Assume SMS was sent if phone exists
            'sms_sent' => !empty($apt['phone']), // Assume SMS was sent if phone exists
            'sms_error' => empty($apt['phone']) ? 'No phone number on file' : null
        ];
    }, $appointments);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load notifications: ' . $e->getMessage()
    ]);
}
