<?php
/**
 * Email Configuration and Sending Functions
 * Handles email verification and other email functionality
 */

// Load environment variables
require_once __DIR__ . '/env.php';
loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');

// Email configuration
define('EMAIL_FROM_ADDRESS', getenv('EMAIL_FROM_ADDRESS') ?: 'noreply@cjcsfs.edu.ph');
define('EMAIL_FROM_NAME', getenv('EMAIL_FROM_NAME') ?: 'CJC School Frontline Services');
define('EMAIL_SMTP_HOST', getenv('EMAIL_SMTP_HOST') ?: '');
define('EMAIL_SMTP_PORT', getenv('EMAIL_SMTP_PORT') ?: 587);
define('EMAIL_SMTP_USER', getenv('EMAIL_SMTP_USER') ?: '');
define('EMAIL_SMTP_PASS', getenv('EMAIL_SMTP_PASS') ?: '');
define('EMAIL_USE_SMTP', getenv('EMAIL_USE_SMTP') === 'true');

/**
 * Send email using PHP mail() or SMTP
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $message Email body (HTML or plain text)
 * @param string $headers Additional headers
 * @return bool True if email was sent successfully
 */
function sendEmail($to, $subject, $message, $headers = '') {
    // Sanitize email address
    $to = filter_var($to, FILTER_SANITIZE_EMAIL);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $to");
        return false;
    }
    
    // Default headers
    $defaultHeaders = [
        'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_FROM_ADDRESS . '>',
        'Reply-To: ' . EMAIL_FROM_ADDRESS,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    if ($headers) {
        $defaultHeaders[] = $headers;
    }
    
    $headersString = implode("\r\n", $defaultHeaders);
    
    // Send email
    $result = mail($to, $subject, $message, $headersString);
    
    if ($result) {
        error_log("Email sent successfully to: $to");
    } else {
        error_log("Failed to send email to: $to");
    }
    
    return $result;
}

/**
 * Send email verification email
 * 
 * @param string $email User's email address
 * @param string $token Verification token
 * @param string $firstName User's first name
 * @return bool True if email was sent successfully
 */
function sendVerificationEmail($email, $token, $firstName = '') {
    // Get base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $baseUrl = $protocol . '://' . $host;
    
    // Verification link
    $verificationLink = $baseUrl . '/verify_email.php?token=' . urlencode($token);
    
    // Email subject
    $subject = 'Verify Your Email - CJC School Frontline Services';
    
    // Email body (HTML)
    $greeting = $firstName ? "Hello {$firstName}," : "Hello,";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #0b2d66; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; background: #0047b3; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>CJC School Frontline Services</h2>
            </div>
            <div class='content'>
                <p>{$greeting}</p>
                <p>Thank you for registering with CJC School Frontline Services!</p>
                <p>Please verify your email address by clicking the button below:</p>
                <p style='text-align: center;'>
                    <a href='{$verificationLink}' class='button'>Verify Email Address</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #0047b3;'>{$verificationLink}</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not create an account, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Cor Jesu College. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $message);
}

/**
 * Generate a secure verification token
 * 
 * @return string Random token
 */
function generateVerificationToken() {
    return bin2hex(random_bytes(32)); // 64 character hex string
}

?>
