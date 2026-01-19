<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/rate_limit.php';

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users, but log them

// Set security headers
setSecurityHeaders();

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    // Sanitize input
    $username_email = sanitizeInput($_POST['username_email'] ?? '', 'email');
    $password = $_POST['password'] ?? '';
    
    // Rate limiting check
    $rateLimit = checkLoginRateLimit($username_email);
    if (!$rateLimit['allowed']) {
        error_log("Login rate limit exceeded for: " . $username_email);
        echo "<script>alert('" . addslashes($rateLimit['message']) . "');window.location.href='login.html';</script>";
        exit();
    }
    
    // Debug: Log what we received
    error_log("Login attempt - Email received: '" . $username_email . "' (length: " . strlen($username_email) . ")");
    
    if (empty($username_email) || empty($password)) {
        echo "<script>alert('Please enter both email and password.');window.location.href='login.html';</script>";
        exit();
    }
    
    // Validate email format
    if (!filter_var($username_email, FILTER_VALIDATE_EMAIL)) {
        error_log("Login attempt with invalid email format: " . $username_email);
        echo "<script>alert('Invalid email format.');window.location.href='login.html';</script>";
        exit();
    }
    
    // Use case-insensitive email matching (LOWER() for both sides)
    // Try with public schema first, fallback to users without schema prefix
    // Update: Use Users table and check by email only (since username is not in the table)
    // Also fetch email_verified status
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, password_hash, COALESCE(email_verified, true) as email_verified FROM public.users WHERE LOWER(email) = LOWER(?)");
    $stmt->execute([$username_email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Debug: Log query result
    if ($row) {
        error_log("Login query found user: ID=" . $row['user_id'] . ", Email=" . $row['email']);
    } else {
        error_log("Login query found NO user for email: '" . $username_email . "'");
        
        // Debug: Check if any users exist and show what emails are in DB
        $checkStmt = $pdo->query("SELECT email FROM public.users LIMIT 5");
        $allEmails = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Sample emails in database: " . implode(", ", $allEmails));
    }
    
    // If not found with public.users, try without schema prefix (in case search_path is set)
    if (!$row) {
        $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, password_hash, COALESCE(email_verified, true) as email_verified FROM users WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$username_email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            error_log("Login query found user WITHOUT schema prefix: ID=" . $row['user_id']);
        }
    }
    
    if ($row) {
        // Check if password_hash column exists and has a value
        if (!isset($row['password_hash']) || empty($row['password_hash'])) {
            error_log("Login error: User found but password_hash is missing for email: " . $username_email);
            echo "<script>alert('Account error. Please contact support.');window.location.href='login.html';</script>";
            exit();
        }
        
        // Schema uses password_hash column (not password)
        if (password_verify($password, $row['password_hash'])) {
            // Check if email is verified
            $emailVerified = $row['email_verified'] ?? true; // Default to true if column doesn't exist (backward compatibility)
            
            if (!$emailVerified) {
                error_log("Login blocked: Email not verified for: " . $username_email);
                echo "<script>alert('Please verify your email address before logging in. Check your inbox for the verification email.');window.location.href='login.html';</script>";
                exit();
            }
            
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['email'] = $row['email'];
            header("Location: proto2.html");
            exit();
        } else {
            error_log("Login failed: Incorrect password for email: " . $username_email);
            echo "<script>alert('Incorrect password.');window.location.href='login.html';</script>";
            exit();
        }
    } else {
        // Log the attempt for debugging
        error_log("Login failed: User not found for email: " . $username_email);
        echo "<script>alert('User not found.');window.location.href='login.html';</script>";
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Login database error: " . $e->getMessage());
    echo "<script>alert('Database error. Please try again later.');window.location.href='login.html';</script>";
    exit();
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo "<script>alert('An error occurred. Please try again later.');window.location.href='login.html';</script>";
    exit();
}

?>