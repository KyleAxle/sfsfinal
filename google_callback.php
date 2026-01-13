<?php
require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';

loadEnv(__DIR__ . '/.env');

$client = new Google_Client();
$clientId = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$redirectUri = getenv('GOOGLE_REDIRECT_URI');

if (!$clientId || !$clientSecret) {
    die('Google OAuth credentials not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env file.');
}

$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri ?: 'http://localhost:8000/google_callback.php');

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        echo "Error fetching access token: " . htmlspecialchars($token['error_description'] ?? $token['error']);
        exit();
    }

    $client->setAccessToken($token['access_token']);

    // Get user profile info
    $oauth = new Google_Service_Oauth2($client);
    $userInfo = $oauth->userinfo->get();

    $email = $userInfo->email;
    $name = $userInfo->name;
    $nameParts = explode(' ', $name, 2);
    $first_name = $nameParts[0] ?? '';
    $last_name = $nameParts[1] ?? '';

    // Connect to Supabase database
    $pdo = require __DIR__ . '/config/db.php';

    // Check if user already exists
    $stmt = $pdo->prepare("SELECT user_id FROM public.users WHERE email = ?");
    $stmt->execute([$email]);
    $existingUser = $stmt->fetch();

    if (!$existingUser) {
        // Register new user with a random password
        // Schema uses password_hash column (not password)
        $random_password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO public.users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$first_name, $last_name, $email, $hashed_password]);
    }

    // Log in the user (fetch their info)
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name FROM public.users WHERE email = ?");
    $stmt->execute([$email]);
    if ($row = $stmt->fetch()) {
        session_start();
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['last_name'] = $row['last_name'];
        $_SESSION['email'] = $email;
        $_SESSION['user_name'] = $name;
        header('Location: proto2.html');
        exit();
    } else {
        echo "Login failed.";
    }
} else {
    echo "Google login failed.";
}
?>