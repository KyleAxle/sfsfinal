<?php
session_start();

$pdo = require __DIR__ . '/config/db.php';

$username_email = trim($_POST['username_email']);
$password = $_POST['password'];

// Update: Use Users table and check by email only (since username is not in the table)
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$username_email]);
$result = $stmt;

if ($row = $result->fetch()) {
    if (password_verify($password, $row['password'])) {
        $_SESSION['user_id'] = $row['user_id']; // or your user ID field
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['last_name'] = $row['last_name'];
        $_SESSION['email'] = $row['email'];
        header("Location: proto2.html");
        exit();
    } else {
        echo "<script>alert('Incorrect password.');window.location.href='login.html';</script>";
    }
} else {
    echo "<script>alert('User not found.');window.location.href='login.html';</script>";
}


?>