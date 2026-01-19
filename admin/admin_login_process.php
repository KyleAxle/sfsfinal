<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/account_lockout.php';

setSecurityHeaders();

// Database connection
$pdo = require __DIR__ . '/../config/db.php';

// Get POST data
$name = sanitizeInput($_POST['name'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '', 'email');
$password = $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {
    header("Location: admin_login.php?error=1");
    exit;
}

// Check account lockout (using email as identifier)
$lockoutCheck = checkAccountLockout($pdo, 'admins', 'email', $email);
if ($lockoutCheck['locked']) {
    error_log("Admin login blocked: Account locked for: " . $email);
    header("Location: admin_login.php?error=locked");
    exit;
}

// Prepare and execute query to find admin by name and email
// Schema uses password_hash column (not password)
$stmt = $pdo->prepare("SELECT admin_id, password_hash FROM admins WHERE name = ? AND email = ?");
$stmt->execute([$name, $email]);
$row = $stmt->fetch();

if ($row) {
    $admin_id = $row['admin_id'];
    // Schema uses password_hash column (not password)
    $hashed_password = $row['password_hash'];
    // If you store hashed passwords, use password_verify
    if (password_verify($password, $hashed_password)) {
        // Reset failed attempts on successful login
        resetFailedAttempts($pdo, 'admins', 'email', $email);
        
        $_SESSION['admin_id'] = $admin_id;
        $_SESSION['role'] = 'admin';
        
        error_log("Successful admin login: Admin ID " . $admin_id . " (" . $email . ")");
        header("Location: admin_dashboard.php");
        exit;
    } else {
        // Record failed attempt
        $lockoutResult = recordFailedAttempt($pdo, 'admins', 'email', $email);
        error_log("Admin login failed: Incorrect credentials for: " . $email . " (Attempts: " . $lockoutResult['attempts'] . ")");
    }
} else {
    // Record failed attempt even if user not found (don't reveal if account exists)
    $lockoutResult = recordFailedAttempt($pdo, 'admins', 'email', $email);
}

header("Location: admin_login.php?error=1");
exit;
?>