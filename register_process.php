<?php
session_start();

$pdo = require __DIR__ . '/config/db.php';

$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']); // <-- Add this line
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

if ($password !== $confirm_password) {
    echo "<script>alert('Passwords do not match.');window.location.href='register.html';</script>";
    exit();
}

// Check if email already exists
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "<script>alert('Email already exists.');window.location.href='register.html';</script>";
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update the query to include phone
$stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
if ($stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password])) {
    echo "<script>alert('Registered! You can now log in.');window.location.href='login.html';</script>";
} else {
    echo "<script>alert('Registration failed. Please try again.');window.location.href='register.html';</script>";
}
?>