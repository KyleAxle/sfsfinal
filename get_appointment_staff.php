<?php
/**
 * Get staff ID for an appointment's office
 * Used to determine which staff member to chat with for a specific appointment
 */

require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

$pdo = require __DIR__ . '/config/db.php';

try {
    $appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
    
    if ($appointmentId <= 0) {
        throw new Exception('Invalid appointment ID');
    }

    // Get the office_id for this appointment
    $officeStmt = $pdo->prepare("
        SELECT ao.office_id, o.office_name
        FROM appointment_offices ao
        JOIN offices o ON ao.office_id = o.office_id
        WHERE ao.appointment_id = ?
        LIMIT 1
    ");
    $officeStmt->execute([$appointmentId]);
    $officeData = $officeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$officeData) {
        throw new Exception('Office not found for this appointment');
    }

    $officeId = (int)$officeData['office_id'];

    // Get staff member for this office
    $staffStmt = $pdo->prepare("
        SELECT staff_id, email, office_name
        FROM staff
        WHERE office_id = ?
        LIMIT 1
    ");
    $staffStmt->execute([$officeId]);
    $staffData = $staffStmt->fetch(PDO::FETCH_ASSOC);

    if (!$staffData) {
        throw new Exception('No staff member found for this office');
    }

    echo json_encode([
        'success' => true,
        'staff_id' => (int)$staffData['staff_id'],
        'staff_email' => $staffData['email'],
        'office_name' => $staffData['office_name'] ?? $officeData['office_name']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

