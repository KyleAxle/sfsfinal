<?php
/**
 * Email Verification Endpoint
 * Verifies user email addresses using verification tokens
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';

// Set security headers
setSecurityHeaders();

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('<!DOCTYPE html>
    <html>
    <head>
        <title>Email Verification</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .container { background: white; padding: 40px; border-radius: 8px; max-width: 500px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
            .error { color: #d32f2f; }
            .success { color: #2e7d32; }
            .button { display: inline-block; background: #0047b3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2 class="error">Invalid Verification Link</h2>
            <p>The verification link is invalid or missing.</p>
            <a href="login.html" class="button">Go to Login</a>
        </div>
    </body>
    </html>');
}

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    // Find user by verification token
    $stmt = $pdo->prepare("
        SELECT user_id, email, first_name, email_verified, email_verification_sent_at 
        FROM public.users 
        WHERE email_verification_token = ? 
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        die('<!DOCTYPE html>
        <html>
        <head>
            <title>Email Verification</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                .container { background: white; padding: 40px; border-radius: 8px; max-width: 500px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
                .error { color: #d32f2f; }
                .button { display: inline-block; background: #0047b3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2 class="error">Invalid Verification Token</h2>
                <p>The verification link is invalid or has expired.</p>
                <p>Please request a new verification email.</p>
                <a href="resend_verification.html" class="button">Resend Verification Email</a>
            </div>
        </body>
        </html>');
    }
    
    // Check if already verified
    if ($user['email_verified']) {
        die('<!DOCTYPE html>
        <html>
        <head>
            <title>Email Already Verified</title>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                .container { background: white; padding: 40px; border-radius: 8px; max-width: 500px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
                .success { color: #2e7d32; }
                .button { display: inline-block; background: #0047b3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2 class="success">Email Already Verified</h2>
                <p>Your email address has already been verified.</p>
                <a href="login.html" class="button">Go to Login</a>
            </div>
        </body>
        </html>');
    }
    
    // Check if token has expired (24 hours)
    if ($user['email_verification_sent_at']) {
        $sentAt = new DateTime($user['email_verification_sent_at']);
        $now = new DateTime();
        $hoursDiff = ($now->getTimestamp() - $sentAt->getTimestamp()) / 3600;
        
        if ($hoursDiff > 24) {
            die('<!DOCTYPE html>
            <html>
            <head>
                <title>Verification Link Expired</title>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                    .container { background: white; padding: 40px; border-radius: 8px; max-width: 500px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
                    .error { color: #d32f2f; }
                    .button { display: inline-block; background: #0047b3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h2 class="error">Verification Link Expired</h2>
                    <p>This verification link has expired. Verification links are valid for 24 hours.</p>
                    <p>Please request a new verification email.</p>
                    <a href="resend_verification.html" class="button">Resend Verification Email</a>
                </div>
            </body>
            </html>');
        }
    }
    
    // Verify the email
    $updateStmt = $pdo->prepare("
        UPDATE public.users 
        SET email_verified = TRUE, 
            email_verification_token = NULL,
            email_verification_sent_at = NULL
        WHERE user_id = ?
    ");
    $updateStmt->execute([$user['user_id']]);
    
    // Log successful verification
    error_log("Email verified for user ID: {$user['user_id']}, email: {$user['email']}");
    
    // Show success message
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Email Verified</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .container { background: white; padding: 40px; border-radius: 8px; max-width: 500px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
            .success { color: #2e7d32; }
            .button { display: inline-block; background: #0047b3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2 class="success">✓ Email Verified Successfully!</h2>
            <p>Your email address has been verified. You can now log in to your account.</p>
            <a href="login.html" class="button">Go to Login</a>
        </div>
    </body>
    </html>';
    
} catch (PDOException $e) {
    error_log("Email verification error: " . $e->getMessage());
    die('<!DOCTYPE html>
    <html>
    <head>
        <title>Verification Error</title>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .container { background: white; padding: 40px; border-radius: 8px; max-width: 500px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
            .error { color: #d32f2f; }
            .button { display: inline-block; background: #0047b3; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2 class="error">Verification Error</h2>
            <p>An error occurred while verifying your email. Please try again later.</p>
            <a href="login.html" class="button">Go to Login</a>
        </div>
    </body>
    </html>');
}
?>
