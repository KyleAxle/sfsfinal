<?php
// Suppress error display to ensure clean JSON output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering to catch any accidental output
ob_start();

require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['success' => false, 'error' => 'Invalid request']);
	exit;
}

if (!isset($_SESSION['staff_id'], $_SESSION['office_id'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'error' => 'Unauthorized']);
	exit;
}

$appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$rawStatus = strtolower(trim($_POST['status'] ?? ''));
$staffMessage = ''; // No longer used, but kept for backward compatibility
$statusMap = [
	'pending' => 'pending',
	'approved' => 'accepted',
	'accepted' => 'accepted',
	'declined' => 'declined',
	'rejected' => 'declined',
	'completed' => 'completed'
];

if ($appointmentId <= 0 || !isset($statusMap[$rawStatus])) {
	echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
	exit;
}

$dbStatus = $statusMap[$rawStatus];

try {
	$pdo = require __DIR__ . '/config/db.php';
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Database connection failed']);
	exit;
}

// Load SMS configuration
require_once __DIR__ . '/config/sms.php';

$officeId = (int)$_SESSION['office_id'];

/**
 * Send SMS notification when appointment is accepted
 * Uses the same SMS API as send_sms.php
 */
function sendAppointmentAcceptanceSMS($appointmentData, $staffMessage = '', $pdo = null) {
	// Debug: Log what we received
	error_log('SMS function called for appointment ID: ' . $appointmentData['appointment_id']);
	error_log('Appointment data keys: ' . implode(', ', array_keys($appointmentData)));
	
	// Check if phone key exists in the data
	if (!isset($appointmentData['phone'])) {
		error_log('SMS notification skipped: Phone key not found in appointment data for appointment ID ' . $appointmentData['appointment_id']);
		error_log('Available keys: ' . implode(', ', array_keys($appointmentData)));
		return false;
	}
	
	// Check if user has a phone number
	$phoneNumber = trim($appointmentData['phone'] ?? '');
	if (empty($phoneNumber)) {
		error_log('SMS notification skipped: No phone number (empty or NULL) for appointment ID ' . $appointmentData['appointment_id'] . ', user: ' . ($appointmentData['first_name'] ?? 'unknown'));
		return false;
	}
	
	// Format phone number to international format (+63) for Philippines
	// Remove any spaces, dashes, or other characters
	$phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
	
	// If it starts with 0, replace with +63
	if (preg_match('/^0/', $phoneNumber)) {
		$phoneNumber = '+63' . substr($phoneNumber, 1);
	}
	// If it starts with 63 but no +, add +
	elseif (preg_match('/^63/', $phoneNumber)) {
		$phoneNumber = '+' . $phoneNumber;
	}
	// If it starts with 9 (local format), add +63
	elseif (preg_match('/^9/', $phoneNumber)) {
		$phoneNumber = '+63' . $phoneNumber;
	}
	// If it doesn't start with +, assume it's a local number and add +63
	elseif (!preg_match('/^\+/', $phoneNumber)) {
		$phoneNumber = '+63' . $phoneNumber;
	}
	
	error_log('SMS will be sent to phone: ' . $phoneNumber . ' (formatted) for appointment ID ' . $appointmentData['appointment_id']);
	
	// Format appointment date and time
	$appointmentDate = $appointmentData['appointment_date'];
	$appointmentTime = $appointmentData['appointment_time'];
	$officeName = $appointmentData['office_name'] ?? 'our office';
	$userName = trim(($appointmentData['first_name'] ?? '') . ' ' . ($appointmentData['last_name'] ?? ''));
	
	// Format date (e.g., "January 15, 2025")
	$dateObj = DateTime::createFromFormat('Y-m-d', $appointmentDate);
	$formattedDate = $dateObj ? $dateObj->format('F j, Y') : $appointmentDate;
	
	// Format time (e.g., "2:30 PM")
	$timeObj = DateTime::createFromFormat('H:i:s', $appointmentTime);
	$formattedTime = $timeObj ? $timeObj->format('g:i A') : $appointmentTime;
	
	// Generate SMS message content (without header/footer - will be added by formatSMSMessage)
	// Shortened version to stay under 160 characters and avoid 2-credit charge
	$messageContent = "Hi" . ($userName ? " {$userName}" : "") . "! Your appt at {$officeName} is ACCEPTED. ";
	$messageContent .= "{$formattedDate} at {$formattedTime}. ";
	
	// Add staff message if provided (shortened)
	if (!empty($staffMessage)) {
		$messageContent .= "Note: {$staffMessage} ";
	}
	
	$messageContent .= "Arrive on time. Thanks!";
	
	// Format message with header and footer template
	$formattedMessage = formatSMSMessage($messageContent);
	
	// Use the centralized SMS API configuration
	// Try form-urlencoded format first (as per iprogsms.com API requirements)
	$data = [
		'api_token' => SMS_API_TOKEN,
		'message' => $formattedMessage,
		'phone_number' => $phoneNumber
	];
	
	error_log('SMS API Request - URL: ' . SMS_API_URL);
	error_log('SMS API Request - Data: ' . print_r($data, true));
	error_log('SMS API Request - Phone Number: ' . $phoneNumber);
	error_log('SMS API Request - Token: ' . substr(SMS_API_TOKEN, 0, 10) . '...');
	
	$ch = curl_init(SMS_API_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Use form-urlencoded format
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/x-www-form-urlencoded'
	]);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	$curlErrno = curl_errno($ch);
	curl_close($ch);
	
	error_log('SMS API Response - HTTP Code: ' . $httpCode);
	error_log('SMS API Response - cURL Error: ' . ($curlError ?: 'None'));
	error_log('SMS API Response - cURL Errno: ' . ($curlErrno ?: 'None'));
	error_log('SMS API Response - Body: ' . $response);
	
	if ($curlError) {
		error_log('SMS sending error for appointment ID ' . $appointmentData['appointment_id'] . ': cURL Error #' . $curlErrno . ' - ' . $curlError);
		return false;
	}
	
	// Check if we got a valid HTTP response
	if ($httpCode !== 200) {
		error_log('SMS sending failed for appointment ID ' . $appointmentData['appointment_id'] . ': HTTP ' . $httpCode . ' - ' . $response);
		return false;
	}
	
	// Parse JSON response
	$result = json_decode($response, true);
	
	if (json_last_error() !== JSON_ERROR_NONE || !is_array($result)) {
		error_log('SMS sending failed for appointment ID ' . $appointmentData['appointment_id'] . ': Invalid JSON response - ' . substr($response, 0, 200));
		error_log('JSON Error: ' . json_last_error_msg());
		return false;
	}
	
	// Check for success - API returns status: 200 on success
	if (isset($result['status']) && $result['status'] == 200) {
		error_log('SMS notification sent successfully to ' . $phoneNumber . ' for appointment ID ' . $appointmentData['appointment_id'] . ' - Message ID: ' . ($result['message_id'] ?? 'N/A'));
		return true;
	} else {
		$errorMsg = $result['message'] ?? $result['error'] ?? 'API returned error';
		$errorStatus = $result['status'] ?? 'unknown';
		$errorDetails = 'HTTP Code: ' . $httpCode . ', API Status: ' . $errorStatus . ', Response: ' . substr($response, 0, 500);
		error_log('SMS sending failed for appointment ID ' . $appointmentData['appointment_id'] . ': ' . $errorMsg . ' | ' . $errorDetails);
		return false;
	}
}

// Fetch appointment details including user phone number for SMS notification
$appointmentQuery = $pdo->prepare("
	select 
		a.appointment_id,
		a.appointment_date,
		a.appointment_time,
		a.concern,
		u.phone,
		u.first_name,
		u.last_name,
		o.office_name
	from appointments a
	join appointment_offices ao on ao.appointment_id = a.appointment_id
	join public.users u on u.user_id = a.user_id
	join offices o on o.office_id = ao.office_id
	where a.appointment_id = ?
	  and ao.office_id = ?
	limit 1
");
$appointmentQuery->execute([$appointmentId, $officeId]);
$appointmentData = $appointmentQuery->fetch(PDO::FETCH_ASSOC);

if (!$appointmentData) {
	ob_clean();
	echo json_encode(['success' => false, 'error' => 'Appointment not found for this office']);
	ob_end_flush();
	exit;
}

// Debug: Log fetched data (remove in production if needed)
error_log('Fetched appointment data for ID ' . $appointmentId . ': phone=' . ($appointmentData['phone'] ?? 'NULL') . ', user=' . ($appointmentData['first_name'] ?? 'unknown'));

// Initialize SMS variables
$smsSent = false;
$smsError = null;

try {
	$pdo->beginTransaction();

	// Update appointments table status
	$update = $pdo->prepare("update appointments set status = ?::appointment_status, updated_at = now() where appointment_id = ?");
	$update->execute([$dbStatus, $appointmentId]);

	// For office_assignment_status, only update if status is not 'completed'
	// The office_assignment_status ENUM typically doesn't include 'completed'
	// So we keep the existing status for completed appointments
	if ($dbStatus === 'completed') {
		// When marking as completed, don't update appointment_offices status
		// Just update the updated_at timestamp
		$updateAo = $pdo->prepare("update appointment_offices set updated_at = now() where appointment_id = ? and office_id = ?");
		$updateAo->execute([$appointmentId, $officeId]);
	} else {
		// For other statuses, update the office_assignment_status
		$officeStatus = $dbStatus === 'accepted' ? 'approved' : $dbStatus;
		
		// Check if staff_message column exists before updating
		$checkColumn = $pdo->query("
			SELECT column_name 
			FROM information_schema.columns 
			WHERE table_schema = 'public' 
			AND table_name = 'appointment_offices' 
			AND column_name = 'staff_message'
		")->fetch();

		if ($checkColumn) {
			// Column exists, update with message
			$updateAo = $pdo->prepare("update appointment_offices set status = ?::office_assignment_status, staff_message = ?, updated_at = now() where appointment_id = ? and office_id = ?");
			$updateAo->execute([$officeStatus, $staffMessage ?: null, $appointmentId, $officeId]);
		} else {
			// Column doesn't exist, update without message
			$updateAo = $pdo->prepare("update appointment_offices set status = ?::office_assignment_status, updated_at = now() where appointment_id = ? and office_id = ?");
			$updateAo->execute([$officeStatus, $appointmentId, $officeId]);
		}
	}

	$pdo->commit();
	
	// Send SMS notification if appointment is accepted/approved
	if ($dbStatus === 'accepted' || $rawStatus === 'approved') {
		error_log('Attempting to send SMS for appointment ID ' . $appointmentId);
		// Send SMS in background (don't block the response)
		$smsSent = sendAppointmentAcceptanceSMS($appointmentData, $staffMessage, $pdo);
		if (!$smsSent) {
			$smsError = 'SMS could not be sent (user may not have phone number or API error occurred)';
			error_log('SMS sending failed for appointment ID ' . $appointmentId . ': ' . $smsError);
		} else {
			error_log('SMS sent successfully for appointment ID ' . $appointmentId);
		}
	}
	
} catch (PDOException $e) {
	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}
	error_log('Staff update appointment error: ' . $e->getMessage());
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Failed to update status.']);
	exit;
}

// Clear any accidental output
ob_clean();

// Output JSON response - ensure no extra output
$response = [
	'success' => true,
	'status' => $officeStatus
];

if ($dbStatus === 'accepted' || $rawStatus === 'approved') {
	$response['sms_sent'] = $smsSent ?? false;
	if (isset($smsError) && $smsError) {
		$response['sms_error'] = $smsError;
	}
}

echo json_encode($response);
ob_end_flush();
exit;