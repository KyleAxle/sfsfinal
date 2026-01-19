<?php
/**
 * Resend Email Verification
 * Allows users to request a new verification email
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/email.php';

// Set security headers
setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: resend_verification.html');
    exit();
}

$email = sanitizeInput($_POST['email'] ?? '', 'email');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Invalid Email</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 20px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
            .btn { display: inline-block; padding: 12px 24px; background: #0047b3; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Invalid Email Address</h2>
            <p>Please enter a valid email address.</p>
            <a href="resend_verification.html" class="btn">Try Again</a>
        </div>
    </body>
    </html>';
    exit();
}

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    // Find user by email
    $stmt = $pdo->prepare("
        SELECT user_id, first_name, email_verified, email_verification_token 
        FROM public.users 
        WHERE email = ? 
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Don't reveal if email exists or not (security best practice)
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Verification Email Sent</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .success-box { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .success { color: #2e7d32; }
                .btn { display: inline-block; padding: 12px 24px; background: #0047b3; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class="success-box">
                <h2 class="success">Verification Email Sent</h2>
                <p>If an account exists with this email address, a verification email has been sent.</p>
                <p>Please check your inbox and click the verification link.</p>
                <a href="login.html" class="btn">Go to Login</a>
            </div>
        </body>
        </html>';
        exit();
    }
    
    // Check if already verified
    if ($user['email_verified']) {
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Email Already Verified</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .info-box { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .info { color: #2196F3; }
                .btn { display: inline-block; padding: 12px 24px; background: #0047b3; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class="info-box">
                <h2 class="info">Email Already Verified</h2>
                <p>Your email address has already been verified. You can log in now.</p>
                <a href="login.html" class="btn">Go to Login</a>
            </div>
        </body>
        </html>';
        exit();
    }
    
    // Generate new verification token
    $newToken = generateVerificationToken();
    
    // Update user with new token
    $updateStmt = $pdo->prepare("
        UPDATE public.users 
        SET email_verification_token = ?,
            email_verification_sent_at = NOW()
        WHERE user_id = ?
    ");
    $updateStmt->execute([$newToken, $user['user_id']]);
    
    // Send verification email
    $emailSent = sendVerificationEmail($email, $newToken, $user['first_name']);
    
    if ($emailSent) {
        error_log("Verification email resent to: $email");
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Verification Email Sent</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .success-box { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .success { color: #2e7d32; }
                .btn { display: inline-block; padding: 12px 24px; background: #0047b3; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class="success-box">
                <h2 class="success">✓ Verification Email Sent!</h2>
                <p>A new verification email has been sent to <strong>' . htmlspecialchars($email) . '</strong></p>
                <p>Please check your inbox and click the verification link to activate your account.</p>
                <p><strong>Note:</strong> The verification link will expire in 24 hours.</p>
                <a href="login.html" class="btn">Go to Login</a>
            </div>
        </body>
        </html>';
    } else {
        error_log("Failed to resend verification email to: $email");
        echo '<!DOCTYPE html>
        <html>
        <head>
            <title>Email Send Failed</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
                .error-box { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .error { color: #d32f2f; }
                .btn { display: inline-block; padding: 12px 24px; background: #0047b3; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <h2 class="error">Failed to Send Email</h2>
                <p>We encountered an error while sending the verification email. Please try again later.</p>
                <p>If the problem persists, please contact support.</p>
                <a href="resend_verification.html" class="btn">Try Again</a>
            </div>
        </body>
        </html>';
    }
    
} catch (PDOException $e) {
    error_log("Resend verification error: " . $e->getMessage());
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
            .error-box { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            .error { color: #d32f2f; }
            .btn { display: inline-block; padding: 12px 24px; background: #0047b3; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class="error-box">
            <h2 class="error">Error</h2>
            <p>An error occurred. Please try again later.</p>
            <a href="resend_verification.html" class="btn">Try Again</a>
        </div>
    </body>
    </html>';
}
?>
