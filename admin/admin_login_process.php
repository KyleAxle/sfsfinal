<?php
require_once __DIR__ . '/../config/session.php';

// Database connection
$pdo = require __DIR__ . '/../config/db.php';

// Get POST data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

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
        $_SESSION['admin_id'] = $admin_id;
        header("Location: admin_dashboard.php");
        exit;
    }
}

header("Location: admin_login.php?error=1");
exit;
?>