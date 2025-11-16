<?php

$url = 'https://sms.iprogtech.com/api/v1/sms_messages';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
// Optionally, set POST to true and send dummy data to mimic a real request
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'api_token' => 'dadd747dcc588f49217a6d239d9ddf6a81a6e91b',
    'message' => 'Test message',
    'phone_number' => '09618836850'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false) {
    echo "cURL error: " . curl_error($ch);
} else {
    echo "HTTP code: $httpcode<br>";
    echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
}
curl_close($ch);
?>