<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/authorization.php';
require_once __DIR__ . '/config/audit_log.php';

// Require user authentication
requireUser();

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    echo "error: Database connection failed: " . $e->getMessage();
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Get and validate POST data
$appointment_date = $_POST['appointment_date'] ?? '';
$appointment_time_raw = $_POST['appointment_time'] ?? '';
$office_id = isset($_POST['office_id']) ? intval($_POST['office_id']) : 0;
$concern = trim($_POST['concern'] ?? '');
$paper_type = trim($_POST['paperType'] ?? '');
$processing_days = isset($_POST['processingDays']) ? intval($_POST['processingDays']) : null;
$release_date = trim($_POST['releaseDate'] ?? '');

// Parse time - handle formats like "09:00 AM - 09:30 AM" or "09:00 AM"
// Extract just the start time and convert to 24-hour format (HH:MM:SS)
$appointment_time = '';
if (!empty($appointment_time_raw)) {
    // Remove extra spaces and split by dash if it's a range
    $time_parts = explode('-', trim($appointment_time_raw));
    $start_time = trim($time_parts[0]); // Get first time (e.g., "09:00 AM")
    
    // Convert "09:00 AM" to "09:00:00" format
    // Try to parse common time formats
    if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $start_time, $matches)) {
        $hour = intval($matches[1]);
        $minute = intval($matches[2]);
        $ampm = strtoupper($matches[3]);
        
        // Convert to 24-hour format
        if ($ampm === 'PM' && $hour != 12) {
            $hour += 12;
        } elseif ($ampm === 'AM' && $hour == 12) {
            $hour = 0;
        }
        
        // Format as HH:MM:SS
        $appointment_time = sprintf('%02d:%02d:00', $hour, $minute);
    } else {
        // If it's already in HH:MM format, add seconds
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $start_time, $matches)) {
            $appointment_time = sprintf('%02d:%02d:00', intval($matches[1]), intval($matches[2]));
        } else {
            // Try to use as-is if it's already in correct format
            $appointment_time = $start_time;
        }
    }
}

// Validate required fields
if (empty($appointment_date) || empty($appointment_time) || $office_id <= 0) {
    echo "error: Missing required fields. Date: '$appointment_date', Time: '$appointment_time_raw' (parsed: '$appointment_time'), Office ID: $office_id";
    exit;
}

// Status is ENUM, use lowercase 'pending'
$status = "pending";

try {
    // Check if slot is blocked for this office/date
    $blockedCheck = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM office_blocked_slots
        WHERE office_id = ?
          AND block_date = ?
          AND start_time <= ?::time
          AND end_time > ?::time
    ");
    $blockedCheck->execute([$office_id, $appointment_date, $appointment_time, $appointment_time]);
    if ((int)($blockedCheck->fetch()['c'] ?? 0) > 0) {
        echo "error: This time slot is unavailable due to an office event.";
        exit;
    }

    // Prevent duplicate non-completed appointments for same slot
    // Simplified check - just check appointments table directly first
    $check_sql = $pdo->prepare("
        SELECT COUNT(*) AS c 
        FROM appointments a
        WHERE a.user_id = ? 
        AND a.appointment_date = ? 
        AND a.appointment_time = ?
        AND a.status::text NOT IN ('completed', 'cancelled')
    ");
    $check_sql->execute([$user_id, $appointment_date, $appointment_time]);
    $count = (int)($check_sql->fetch()['c'] ?? 0);

    if ($count > 0) {
        // Also check if this specific office is already booked
        $check_office = $pdo->prepare("
            SELECT COUNT(*) AS c 
            FROM appointments a
            INNER JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
            WHERE a.user_id = ? 
            AND ao.office_id = ? 
            AND a.appointment_date = ? 
            AND a.appointment_time = ? 
            AND a.status::text NOT IN ('completed', 'cancelled')
        ");
        $check_office->execute([$user_id, $office_id, $appointment_date, $appointment_time]);
        $office_count = (int)($check_office->fetch()['c'] ?? 0);
        
        if ($office_count > 0) {
            echo "duplicate";
            exit;
        }
    }

    // Insert new appointment
    // PostgreSQL ENUM casting: cast the value in the SQL
    // Build INSERT query with optional fields for Registrar Office
    $fields = ['user_id', 'appointment_date', 'appointment_time', 'concern', 'status'];
    $values = [$user_id, $appointment_date, $appointment_time, $concern, $status];
    $placeholders = ['?', '?', '?', '?', '?::appointment_status'];
    
    if (!empty($paper_type)) {
        $fields[] = 'paper_type';
        $values[] = $paper_type;
        $placeholders[] = '?';
    }
    
    if ($processing_days !== null && $processing_days > 0) {
        $fields[] = 'processing_days';
        $values[] = $processing_days;
        $placeholders[] = '?';
    }
    
    if (!empty($release_date)) {
        $fields[] = 'release_date';
        $values[] = $release_date;
        $placeholders[] = '?';
    }
    
    $fieldsStr = implode(', ', $fields);
    $placeholdersStr = implode(', ', $placeholders);
    
    $stmt = $pdo->prepare("
        INSERT INTO appointments ({$fieldsStr}) 
        VALUES ({$placeholdersStr})
    ");
    
    $result = $stmt->execute($values);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        echo "error: Failed to insert appointment: " . ($errorInfo[2] ?? 'Unknown error');
        exit;
    }

    // Get the last inserted appointment_id
    $appointment_id = (int)$pdo->lastInsertId('appointments_appointment_id_seq');
    
    if ($appointment_id <= 0) {
        // Try alternative method
        $stmt_id = $pdo->query("SELECT lastval()");
        $appointment_id = (int)($stmt_id->fetchColumn() ?? 0);
    }

    if ($appointment_id <= 0) {
        echo "error: Failed to get appointment ID";
        exit;
    }

    // Verify office exists before inserting
    $check_office = $pdo->prepare("SELECT office_id FROM offices WHERE office_id = ?");
    $check_office->execute([$office_id]);
    if (!$check_office->fetch()) {
        // Rollback the appointment
        $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?")->execute([$appointment_id]);
        echo "error: Office ID $office_id does not exist. Please run create_default_offices.php to create offices.";
        exit;
    }
    
    // Insert into appointment_offices table
    $stmt2 = $pdo->prepare("
        INSERT INTO appointment_offices (appointment_id, office_id, status) 
        VALUES (?, ?, ?::office_assignment_status)
    ");
    
    $result2 = $stmt2->execute([$appointment_id, $office_id, $status]);
    
    if (!$result2) {
        $errorInfo2 = $stmt2->errorInfo();
        // Rollback the appointment if office assignment fails
        $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?")->execute([$appointment_id]);
        echo "error: Failed to assign office: " . ($errorInfo2[2] ?? 'Unknown error');
        exit;
    }

    // Log appointment creation
    logAuditEvent(AUDIT_APPOINTMENT_CREATED, 'appointment', $appointment_id, [
        'office_id' => $office_id,
        'appointment_date' => $appointment_date,
        'appointment_time' => $appointment_time
    ], $user_id, 'user');
    
    echo "success";
    
} catch (PDOException $e) {
    echo "error: Database error: " . $e->getMessage();
    error_log("Appointment save error: " . $e->getMessage());
} catch (Exception $e) {
    echo "error: " . $e->getMessage();
    error_log("Appointment save error: " . $e->getMessage());
}
?>