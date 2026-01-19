<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to HTML so errors display properly
header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/rate_limit.php';
require_once __DIR__ . '/config/email.php';

// Set security headers
setSecurityHeaders();

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

// Rate limiting check
$clientIP = getClientIP();
$rateLimit = checkRegisterRateLimit($clientIP);
if (!$rateLimit['allowed']) {
    error_log("Registration rate limit exceeded for IP: " . $clientIP);
    die('<!DOCTYPE html>
    <html>
    <head>
        <title>Rate Limit Exceeded</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Too Many Registration Attempts</h2>
            <p>' . htmlspecialchars($rateLimit['message']) . '</p>
            <p><a href="register.html">← Back to Registration</a></p>
        </div>
    </body>
    </html>');
}

// Sanitize and validate input
$first_name = sanitizeInput($_POST['first_name'] ?? '');
$last_name = sanitizeInput($_POST['last_name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '', 'email');
$phone = sanitizeInput($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$date_of_birth = sanitizeInput($_POST['dob'] ?? ''); // Registration form uses 'dob' field name
$age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;

// Input validation rules
$validationRules = [
    'first_name' => ['required' => true, 'min_length' => 1, 'max_length' => 100],
    'last_name' => ['required' => true, 'min_length' => 1, 'max_length' => 100],
    'email' => ['required' => true, 'type' => 'email', 'max_length' => 100],
    'password' => ['required' => true, 'min_length' => 8],
];

$validationErrors = validateInput($_POST, $validationRules);

// Password strength validation
if (!empty($password)) {
    $passwordErrors = validatePasswordStrength($password);
    if (!empty($passwordErrors)) {
        $validationErrors['password'] = implode(' ', $passwordErrors);
    }
}

// Password confirmation check
if ($password !== $confirm_password) {
    $validationErrors['confirm_password'] = 'Passwords do not match';
}

// Display validation errors
if (!empty($validationErrors)) {
    $errorMessages = implode('<br>', array_values($validationErrors));
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Validation Error</title>
        <meta http-equiv="refresh" content="5;url=register.html">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Validation Error</h2>
            <p>' . htmlspecialchars($errorMessages) . '</p>
            <p>Redirecting back to registration form...</p>
            <p><a href="register.html">Click here if not redirected</a></p>
        </div>
        <script>setTimeout(function(){window.location.href="register.html";}, 5000);</script>
    </body>
    </html>';
    exit();
}

// Basic required field check (backup)
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
    
    // Generate email verification token
    $verificationToken = generateVerificationToken();

    // Insert new user into Supabase (explicitly use public.users schema)
    // Schema uses password_hash column (not password) and includes phone column
    // Check which columns exist before including them
    $columns = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users'
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    // Build INSERT query dynamically based on available columns
    $insertFields = ['first_name', 'last_name', 'email', 'password_hash'];
    $insertValues = [$first_name, $last_name, $email, $hashed_password];
    
    // Add email verification fields if columns exist
    if (in_array('email_verified', $columns)) {
        $insertFields[] = 'email_verified';
        $insertValues[] = false; // Not verified yet
    }
    
    if (in_array('email_verification_token', $columns)) {
        $insertFields[] = 'email_verification_token';
        $insertValues[] = $verificationToken;
    }
    
    if (in_array('email_verification_sent_at', $columns)) {
        $insertFields[] = 'email_verification_sent_at';
        $insertValues[] = date('Y-m-d H:i:s');
    }
    
    if (in_array('phone', $columns)) {
        $insertFields[] = 'phone';
        $insertValues[] = $phone ?: null;
    }
    
    if (in_array('date_of_birth', $columns) && !empty($date_of_birth)) {
        $insertFields[] = 'date_of_birth';
        $insertValues[] = $date_of_birth;
    }
    
    if (in_array('age', $columns) && $age !== null) {
        $insertFields[] = 'age';
        $insertValues[] = $age;
    }
    
    $placeholders = implode(', ', array_fill(0, count($insertFields), '?'));
    $sql = "INSERT INTO public.users (" . implode(', ', $insertFields) . ") VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($insertValues);
    
    error_log("Registration attempt: email=$email, first_name=$first_name, last_name=$last_name, phone=" . ($phone ?: 'none') . ", dob=" . ($date_of_birth ?: 'none') . ", age=" . ($age !== null ? $age : 'none'));
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
            // Send verification email
            $emailSent = sendVerificationEmail($email, $verificationToken, $first_name);
            
            if (!$emailSent) {
                error_log("Failed to send verification email to: $email");
            }
            
            echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Registration Successful</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .success-box { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .success { color: #2e7d32; }
                .info { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; border-radius: 4px; }
                .btn { display: inline-block; padding: 12px 24px; background: #0047b3; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                .btn:hover { background: #003d99; }
            </style>
        </head>
        <body>
            <div class="success-box">
                <h2 class="success">✓ Registration Successful!</h2>
                <p>Your account has been created successfully.</p>
                <div class="info">
                    <h3 style="margin-top: 0;">📧 Verify Your Email</h3>
                    <p>We\'ve sent a verification email to <strong>' . htmlspecialchars($email) . '</strong></p>
                    <p>Please check your inbox and click the verification link to activate your account.</p>
                    <p><strong>Note:</strong> You must verify your email before you can log in.</p>
                    <p style="margin-bottom: 0;">Didn\'t receive the email? <a href="resend_verification.html">Resend verification email</a></p>
                </div>
                <a href="login.html" class="btn">Go to Login</a>
            </div>
            <script>setTimeout(function(){window.location.href="login.html";}, 10000);</script>
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