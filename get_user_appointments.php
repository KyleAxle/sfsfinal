<?php
/**
 * Get user's appointment history for AI context
 * Returns appointments for the logged-in user
 */

session_start();
header('Content-Type: application/json');

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Validate user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

try {
    // Get user's recent appointments with office information
    $stmt = $pdo->prepare("
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.concern,
            a.status,
            a.created_at,
            o.office_name,
            o.office_id
        FROM appointments a
        INNER JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
        INNER JOIN offices o ON ao.office_id = o.office_id
        WHERE a.user_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT ?
    ");
    
    $stmt->execute([$user_id, $limit]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates and times for easier use
    foreach ($appointments as &$apt) {
        $apt['appointment_date_formatted'] = date('F j, Y', strtotime($apt['appointment_date']));
        $apt['appointment_time_formatted'] = date('g:i A', strtotime($apt['appointment_time']));
    }
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments,
        'count' => count($appointments)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

