<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/account_lockout.php';

setSecurityHeaders();

$pdo = require __DIR__ . '/config/db.php';

$email = sanitizeInput($_POST['email'] ?? '', 'email');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo "<script>alert('Please enter both email and password.');window.location.href='staff_login.html';</script>";
    exit;
}

// Check account lockout
$lockoutCheck = checkAccountLockout($pdo, 'staff', 'email', $email);
if ($lockoutCheck['locked']) {
    error_log("Staff login blocked: Account locked for: " . $email);
    echo "<script>alert('" . addslashes($lockoutCheck['message']) . "');window.location.href='staff_login.html';</script>";
    exit;
}

$stmt = $pdo->prepare("SELECT staff_id, office_id, office_name, password FROM staff WHERE email = ?");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && password_verify($password, $row['password'])) {
    // Reset failed attempts on successful login
    resetFailedAttempts($pdo, 'staff', 'email', $email);
    
    $_SESSION['staff_id'] = $row['staff_id'];
    $_SESSION['office_id'] = $row['office_id'];
    $_SESSION['office_name'] = $row['office_name'];
    $_SESSION['staff_email'] = $email;
    $_SESSION['role'] = 'staff';

    error_log("Successful staff login: Staff ID " . $row['staff_id'] . " (" . $email . ")");
    header("Location: staff_dashboard.php");
    exit;
} else {
    // Record failed attempt
    $lockoutResult = recordFailedAttempt($pdo, 'staff', 'email', $email);
    error_log("Staff login failed: Incorrect credentials for: " . $email . " (Attempts: " . $lockoutResult['attempts'] . ")");
    
    if ($lockoutResult['locked']) {
        echo "<script>alert('" . addslashes($lockoutResult['message']) . "');window.location.href='staff_login.html';</script>";
    } else {
        echo "<script>alert('" . addslashes($lockoutResult['message']) . "');window.location.href='staff_login.html';</script>";
    }
}
?>