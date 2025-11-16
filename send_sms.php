<?php
// send_sms.php
session_start();


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Insert your API token here
$api_token = 'dadd747dcc588f49217a6d239d9ddf6a81a6e91b';

$phone_number = $_POST['phone_number'] ?? '';
$message = $_POST['message'] ?? '';

if (!$phone_number || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing phone number or message']);
    exit;
}

$url = 'https://sms.iprogtech.com/api/v1/sms_messages';

$data = [
    'api_token' => $api_token,
    'message' => $message,
    'phone_number' => $phone_number
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
    echo json_encode([
        'success' => false,
        'error' => 'cURL error: ' . curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

// Try to decode the response as JSON
$json = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
    $json['http_code'] = $httpcode;
    // Treat status 200 as success
    $json['success'] = (isset($json['status']) && $json['status'] == 200);
    echo json_encode($json);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid response from SMS API',
        'raw_response' => $response,
        'http_code' => $httpcode
    ]);
}
?>