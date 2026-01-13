<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users, but log them

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    $username_email = trim($_POST['username_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug: Log what we received
    error_log("Login attempt - Email received: '" . $username_email . "' (length: " . strlen($username_email) . ")");
    error_log("Login attempt - POST data: " . print_r($_POST, true));
    
    if (empty($username_email) || empty($password)) {
        echo "<script>alert('Please enter both email and password.');window.location.href='login.html';</script>";
        exit();
    }
    
    // Use case-insensitive email matching (LOWER() for both sides)
    // Try with public schema first, fallback to users without schema prefix
    // Update: Use Users table and check by email only (since username is not in the table)
    $stmt = $pdo->prepare("SELECT * FROM public.users WHERE LOWER(email) = LOWER(?)");
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
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?)");
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