<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
	echo json_encode(['success' => false, 'error' => 'Not logged in']);
	exit;
}

$pdo = require __DIR__ . '/config/db.php';

$user_id = intval($_SESSION['user_id']);
$stmt = $pdo->prepare("SELECT first_name, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if ($row) {
	echo json_encode([
		'success' => true,
		'first_name' => $row['first_name'],
		'email' => $row['email']
	]);
} else {
	echo json_encode(['success' => false, 'error' => 'User not found']);
}
?>