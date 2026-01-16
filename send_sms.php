<?php
// send_sms.php
require_once __DIR__ . '/config/session.php';

// Load SMS configuration
require_once __DIR__ . '/config/sms.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$phone_number = $_POST['phone_number'] ?? '';
$message = $_POST['message'] ?? '';

if (!$phone_number || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing phone number or message']);
    exit;
}

// Format message with header and footer template
$formattedMessage = formatSMSMessage($message);

$data = [
    'api_token' => SMS_API_TOKEN,
    'message' => $formattedMessage,
    'phone_number' => $phone_number
];

error_log('SMS API Request - URL: ' . SMS_API_URL);
error_log('SMS API Request - Data: ' . json_encode($data));

$ch = curl_init(SMS_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Send as JSON
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json' // Changed to JSON
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

error_log('SMS API Response - HTTP Code: ' . $httpcode);
error_log('SMS API Response - Body: ' . $response);

if ($curlError) {
    error_log('SMS sending error: ' . $curlError);
    echo json_encode([
        'success' => false,
        'error' => 'cURL error: ' . $curlError
    ]);
    exit;
}

// Check if we got a valid HTTP response
if ($httpcode !== 200) {
    error_log('SMS sending failed: HTTP ' . $httpcode . ' - ' . $response);
    echo json_encode([
        'success' => false,
        'error' => 'HTTP Error: ' . $httpcode,
        'raw_response' => $response,
        'http_code' => $httpcode
    ]);
    exit;
}

// Try to decode the response as JSON
$json = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
    $json['http_code'] = $httpcode;
    // Check for status: 200 in the response (iprogsms.com API format)
    $json['success'] = (isset($json['status']) && $json['status'] == 200);
    
    if ($json['success']) {
        error_log('SMS sent successfully - Message ID: ' . ($json['message_id'] ?? 'N/A'));
    } else {
        $errorMsg = $json['message'] ?? $json['error'] ?? 'API returned error';
        error_log('SMS sending failed: ' . $errorMsg);
    }
    
    echo json_encode($json);
} else {
    error_log('SMS sending failed: Invalid JSON response - ' . substr($response, 0, 200));
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response from SMS API',
        'raw_response' => $response,
        'http_code' => $httpcode
    ]);
}
?>