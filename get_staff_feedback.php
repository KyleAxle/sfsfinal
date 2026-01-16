<?php
/**
 * Get feedback for staff's office
 * Returns all feedback for appointments in the staff's office
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
    $stmt = $pdo->prepare("
        SELECT 
            f.rating,
            f.comment as feedback_comment,
            f.submitted_at as feedback_submitted_at,
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.concern,
            a.status,
            u.first_name,
            u.last_name,
            u.email
        FROM public.feedback f
        INNER JOIN public.appointments a ON f.appointment_id = a.appointment_id
        INNER JOIN public.appointment_offices ao ON a.appointment_id = ao.appointment_id
        INNER JOIN public.users u ON a.user_id = u.user_id
        WHERE ao.office_id = ?
        ORDER BY f.submitted_at DESC, a.appointment_date DESC
    ");
    
    $stmt->execute([$officeId]);
    $feedback = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'feedback' => $feedback
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load feedback: ' . $e->getMessage()
    ]);
}
