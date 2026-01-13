<?php
/**
 * AI Auto-Booking Endpoint
 * Allows AI to book appointments automatically for users
 */

session_start();
header('Content-Type: application/json');

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Validate user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Get JSON input (or from global if called internally)
if (isset($GLOBALS['__AI_BOOKING_DATA__'])) {
    $input = $GLOBALS['__AI_BOOKING_DATA__'];
} else {
    $input = json_decode(file_get_contents('php://input'), true);
}
$office_id = isset($input['office_id']) ? (int)$input['office_id'] : 0;
$appointment_date = trim($input['appointment_date'] ?? '');
$appointment_time = trim($input['appointment_time'] ?? '');
$concern = trim($input['concern'] ?? 'AI-assisted booking');

// Validate required fields
if ($office_id <= 0 || empty($appointment_date) || empty($appointment_time)) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: office_id, appointment_date, or appointment_time'
    ]);
    exit;
}

// Parse time to HH:MM:SS format
$timeFormatted = '';
if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $appointment_time, $matches)) {
    $hour = intval($matches[1]);
    $minute = intval($matches[2]);
    $ampm = strtoupper($matches[3]);
    
    if ($ampm === 'PM' && $hour != 12) {
        $hour += 12;
    } elseif ($ampm === 'AM' && $hour == 12) {
        $hour = 0;
    }
    
    $timeFormatted = sprintf('%02d:%02d:00', $hour, $minute);
} else {
    // Assume it's already in HH:MM format
    if (preg_match('/^(\d{1,2}):(\d{2})/', $appointment_time, $matches)) {
        $timeFormatted = sprintf('%02d:%02d:00', intval($matches[1]), intval($matches[2]));
    } else {
        $timeFormatted = $appointment_time;
    }
}

try {
    // Check if slot is blocked
    $blockedCheck = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM office_blocked_slots
        WHERE office_id = ?
          AND block_date = ?
          AND start_time <= ?::time
          AND end_time > ?::time
    ");
    $blockedCheck->execute([$office_id, $appointment_date, $timeFormatted, $timeFormatted]);
    if ((int)($blockedCheck->fetch()['c'] ?? 0) > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'This time slot is unavailable due to an office event'
        ]);
        exit;
    }
    
    // Check for duplicate appointments
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM appointments a
        INNER JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
        WHERE a.user_id = ?
          AND ao.office_id = ?
          AND a.appointment_date = ?
          AND a.appointment_time = ?
          AND a.status::text NOT IN ('completed', 'cancelled')
    ");
    $checkStmt->execute([$user_id, $office_id, $appointment_date, $timeFormatted]);
    if ((int)($checkStmt->fetch()['c'] ?? 0) > 0) {
        echo json_encode([
            'success' => false,
            'error' => 'You already have an appointment at this time'
        ]);
        exit;
    }
    
    // Create appointment
    $status = 'pending';
    $stmt = $pdo->prepare("
        INSERT INTO appointments (user_id, appointment_date, appointment_time, concern, status)
        VALUES (?, ?, ?, ?, ?::appointment_status)
    ");
    
    $result = $stmt->execute([$user_id, $appointment_date, $timeFormatted, $concern, $status]);
    
    if (!$result) {
        throw new Exception('Failed to create appointment');
    }
    
    $appointment_id = (int)$pdo->lastInsertId('appointments_appointment_id_seq');
    
    if ($appointment_id <= 0) {
        $stmt_id = $pdo->query("SELECT lastval()");
        $appointment_id = (int)($stmt_id->fetchColumn() ?? 0);
    }
    
    if ($appointment_id <= 0) {
        throw new Exception('Failed to get appointment ID');
    }
    
    // Link to office
    $stmt2 = $pdo->prepare("
        INSERT INTO appointment_offices (appointment_id, office_id, status)
        VALUES (?, ?, ?::office_assignment_status)
    ");
    
    $result2 = $stmt2->execute([$appointment_id, $office_id, $status]);
    
    if (!$result2) {
        // Rollback
        $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?")->execute([$appointment_id]);
        throw new Exception('Failed to assign office');
    }
    
    // Get office name for response
    $officeStmt = $pdo->prepare("SELECT office_name FROM offices WHERE office_id = ?");
    $officeStmt->execute([$office_id]);
    $office = $officeStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'appointment_id' => $appointment_id,
        'office_name' => $office['office_name'] ?? 'Office',
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time,
        'message' => 'Appointment booked successfully!'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

