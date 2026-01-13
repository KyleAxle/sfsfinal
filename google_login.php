<?php
require_once __DIR__ . '/google-api-php-client/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';

loadEnv(__DIR__ . '/.env');

$client = new Google_Client();
$clientId = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$redirectUri = getenv('GOOGLE_REDIRECT_URI');

if (!$clientId || !$clientSecret) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Google Sign-in Not Configured</title>
        <style>
            body {
                font-family: "Segoe UI", Arial, sans-serif;
                background: #f8fafc;
                margin: 0;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
            }
            .card {
                background: #fff;
                padding: 32px;
                border-radius: 18px;
                box-shadow: 0 20px 45px rgba(15,23,42,0.15);
                max-width: 640px;
                width: 90%;
                color: #0f172a;
            }
            h2 { margin-top: 0; color: #7d0000; }
            code {
                background: #f1f5f9;
                padding: 3px 6px;
                border-radius: 6px;
                font-size: 0.95rem;
            }
            ol { margin-left: 20px; }
        </style>
    </head>
    <body>
        <section class="card">
            <h2>Google Sign-in Not Configured</h2>
            <p>To enable <strong>Sign in with Google</strong>, add your OAuth credentials to the <code>.env</code> file:</p>
            <ol>
                <li>Create OAuth Client credentials in the <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console</a>.</li>
                <li>Use the callback/redirect URL: <code>http://localhost:8000/google_callback.php</code> (or <code>http://localhost/sfs/google_callback.php</code> if using Apache).</li>
                <li>Add the following entries to your <code>.env</code> file:
                    <pre>
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/google_callback.php
                    </pre>
                </li>
                <li>Restart the PHP server so the new environment variables are loaded.</li>
            </ol>
            <p>Once the credentials are in place, click “Sign in with Google” again.</p>
        </section>
    </body>
    </html>
    <?php
    exit;
}

$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri ?: 'http://localhost:8000/google_callback.php');
$client->addScope('email');
$client->addScope('profile');

$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit();
?>