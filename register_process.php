<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to HTML so errors display properly
header('Content-Type: text/html; charset=UTF-8');

session_start();

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Invalid Request</h2>
            <p>This page only accepts POST requests. Please use the registration form.</p>
            <p><a href="register.html">← Back to Registration</a></p>
        </div>
    </body>
    </html>');
}

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    die('<!DOCTYPE html>
    <html>
    <head>
        <title>Database Connection Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
            .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Database Connection Failed</h2>
            <p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
            <div class="info">
                <h3>How to Fix:</h3>
                <ol>
                    <li>Check your .env file has correct Supabase credentials</li>
                    <li>Ensure PHP PostgreSQL extension is enabled</li>
                    <li>Test connection: <a href="test_connection.php">test_connection.php</a></li>
                    <li>Check diagnostic: <a href="check_driver_web.php">check_driver_web.php</a></li>
                </ol>
            </div>
            <p><a href="register.html">← Back to Registration</a></p>
        </div>
    </body>
    </html>');
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Validation Error</title>
        <meta http-equiv="refresh" content="3;url=register.html">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Validation Error</h2>
            <p>All fields are required. Redirecting back to registration form...</p>
            <p><a href="register.html">Click here if not redirected</a></p>
        </div>
        <script>setTimeout(function(){window.location.href="register.html";}, 2000);</script>
    </body>
    </html>';
    exit();
}

if ($password !== $confirm_password) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Password Mismatch</title>
        <meta http-equiv="refresh" content="3;url=register.html">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Password Mismatch</h2>
            <p>Passwords do not match. Redirecting back to registration form...</p>
            <p><a href="register.html">Click here if not redirected</a></p>
        </div>
        <script>setTimeout(function(){window.location.href="register.html";}, 2000);</script>
    </body>
    </html>';
    exit();
}

try {
    // Check if email already exists (explicitly use public.users schema)
    $stmt = $pdo->prepare("SELECT user_id FROM public.users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Email Already Exists</title>
            <meta http-equiv="refresh" content="3;url=register.html">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .error { color: #d32f2f; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h2 class="error">Email Already Exists</h2>
                <p>This email is already registered. Please use a different email or <a href="login.html">log in</a>.</p>
                <p>Redirecting back to registration form...</p>
                <p><a href="register.html">Click here if not redirected</a></p>
            </div>
            <script>setTimeout(function(){window.location.href="register.html";}, 2000);</script>
        </body>
        </html>';
        exit();
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into Supabase (explicitly use public.users schema)
    // Schema uses password_hash column (not password) and includes phone column
    // Check if phone column exists before including it
    $checkPhoneColumn = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'phone'
    ")->fetch();
    
    if ($checkPhoneColumn) {
        // Phone column exists, include it in INSERT
        $stmt = $pdo->prepare("INSERT INTO public.users (first_name, last_name, email, password_hash, phone) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$first_name, $last_name, $email, $hashed_password, $phone ?: null]);
        error_log("Registration attempt: email=$email, first_name=$first_name, last_name=$last_name, phone=" . ($phone ?: 'none'));
    } else {
        // Phone column doesn't exist, insert without it
        $stmt = $pdo->prepare("INSERT INTO public.users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$first_name, $last_name, $email, $hashed_password]);
        error_log("Registration attempt: email=$email, first_name=$first_name, last_name=$last_name (phone column not available)");
    }
    $rowCount = $stmt->rowCount();
    
    error_log("Registration INSERT result: " . ($result ? 'true' : 'false') . ", rowCount: $rowCount");
    
    // Verify the insert was successful by checking row count
    if ($result && $rowCount > 0) {
        // Double-check: verify user was actually inserted into Supabase
        $verifyStmt = $pdo->prepare("SELECT user_id FROM public.users WHERE email = ?");
        $verifyStmt->execute([$email]);
        $insertedUser = $verifyStmt->fetch();
        
        error_log("Registration verification: " . ($insertedUser ? "User found with ID: " . $insertedUser['user_id'] : "User NOT found"));
        
        if ($insertedUser) {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Registration Successful</title>
            <meta http-equiv="refresh" content="3;url=login.html">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .success-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .success { color: #2e7d32; }
                .btn { display: inline-block; padding: 10px 20px; background: #212529; color: white; text-decoration: none; border-radius: 4px; margin-top: 15px; }
                .btn:hover { background: #343a40; }
            </style>
        </head>
        <body>
            <div class="success-box">
                <h2 class="success">✓ Registration Successful!</h2>
                <p>Your account has been created successfully. You can now log in.</p>
                <p>Redirecting to login page...</p>
                <a href="login.html" class="btn">Go to Login</a>
            </div>
            <script>setTimeout(function(){window.location.href="login.html";}, 2000);</script>
        </body>
        </html>';
        } else {
            // User was not found after insert - this should not happen
            throw new Exception("User was not saved to database. Please try again.");
        }
    } else {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Registration Failed</title>
            <meta http-equiv="refresh" content="3;url=register.html">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .error { color: #d32f2f; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h2 class="error">Registration Failed</h2>
                <p>An error occurred during registration. Please try again.</p>
                <p>Redirecting back to registration form...</p>
                <p><a href="register.html">Click here if not redirected</a></p>
            </div>
            <script>setTimeout(function(){window.location.href="register.html";}, 2000);</script>
        </body>
        </html>';
    }
} catch (PDOException $e) {
    // Show detailed error for debugging
    $errorMsg = htmlspecialchars($e->getMessage());
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Database Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
            .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Database Error</h2>
            <p><strong>Error Message:</strong></p>
            <div class="code">' . $errorMsg . '</div>
            <p>This might be due to:</p>
            <ul>
                <li>Database connection issue</li>
                <li>Table structure mismatch</li>
                <li>Missing required fields in database</li>
            </ul>
            <p><a href="register.html">← Back to Registration</a> | <a href="test_connection.php">Test Connection</a></p>
        </div>
    </body>
    </html>';
    // Log error for debugging
    error_log("Registration error: " . $e->getMessage());
}
?>