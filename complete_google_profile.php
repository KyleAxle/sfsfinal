<?php
/**
 * Complete Google Profile - Save phone, date of birth, and age for Google login users
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';

// Set security headers
setSecurityHeaders();

// Check if user is logged in and needs profile completion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// If user doesn't need profile completion, redirect to dashboard
if (!isset($_SESSION['needs_profile_completion'])) {
    header('Location: proto2.html');
    exit();
}

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    die('<!DOCTYPE html>
    <html>
    <head>
        <title>Database Error</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Database Connection Failed</h2>
            <p>Please try again later.</p>
            <p><a href="complete_profile.html">← Back</a></p>
        </div>
    </body>
    </html>');
}

$user_id = (int)$_SESSION['user_id'];

// Sanitize and validate input
$phone = sanitizeInput($_POST['phone'] ?? '');
$date_of_birth = sanitizeInput($_POST['date_of_birth'] ?? '');
$age = null;

// Calculate age from date of birth
if (!empty($date_of_birth)) {
    try {
        $birthDate = new DateTime($date_of_birth);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
    } catch (Exception $e) {
        error_log("Error calculating age: " . $e->getMessage());
    }
}

// Validate required fields
if (empty($phone) || empty($date_of_birth)) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Validation Error</title>
        <meta http-equiv="refresh" content="3;url=complete_profile.html">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Validation Error</h2>
            <p>Please fill in all required fields (Phone Number and Date of Birth).</p>
            <p>Redirecting back...</p>
            <p><a href="complete_profile.html">Click here if not redirected</a></p>
        </div>
        <script>setTimeout(function(){window.location.href="complete_profile.html";}, 3000);</script>
    </body>
    </html>';
    exit();
}

// Validate phone number format (basic validation)
if (!preg_match('/^[\d\s\-\+\(\)]+$/', $phone)) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Invalid Phone Number</title>
        <meta http-equiv="refresh" content="3;url=complete_profile.html">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Invalid Phone Number</h2>
            <p>Please enter a valid phone number.</p>
            <p>Redirecting back...</p>
            <p><a href="complete_profile.html">Click here if not redirected</a></p>
        </div>
        <script>setTimeout(function(){window.location.href="complete_profile.html";}, 3000);</script>
    </body>
    </html>';
    exit();
}

try {
    // Check which columns exist before updating
    $columns = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users'
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    // Build UPDATE query dynamically based on available columns
    $updateFields = [];
    $updateValues = [];
    
    if (in_array('phone', $columns) && !empty($phone)) {
        $updateFields[] = 'phone = ?';
        $updateValues[] = $phone;
    }
    
    if (in_array('date_of_birth', $columns) && !empty($date_of_birth)) {
        $updateFields[] = 'date_of_birth = ?';
        $updateValues[] = $date_of_birth;
    }
    
    if (in_array('age', $columns) && $age !== null) {
        $updateFields[] = 'age = ?';
        $updateValues[] = $age;
    }
    
    if (!empty($updateFields)) {
        $updateValues[] = $user_id;
        $sql = "UPDATE public.users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);
        
        // Remove profile completion flag
        unset($_SESSION['needs_profile_completion']);
        
        // Log successful profile completion
        error_log("Profile completed for Google user ID: $user_id, email: " . ($_SESSION['email'] ?? 'unknown'));
        
        // Redirect to dashboard
        header('Location: proto2.html');
        exit();
    } else {
        // No columns to update (shouldn't happen, but handle gracefully)
        unset($_SESSION['needs_profile_completion']);
        header('Location: proto2.html');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Profile completion error: " . $e->getMessage());
    echo '<!DOCTYPE html>
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
            <h2 class="error">Error Saving Profile</h2>
            <p>An error occurred while saving your profile. Please try again.</p>
            <p><a href="complete_profile.html">← Back</a></p>
        </div>
    </body>
    </html>';
    exit();
}
?>
