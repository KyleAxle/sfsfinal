<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['success' => false, 'error' => 'Invalid request']);
	exit;
}

$appointmentId = $_POST['appointment_id'] ?? null;
$status = $_POST['status'] ?? null;

if (!$appointmentId || !$status) {
	echo json_encode(['success' => false, 'error' => 'Missing parameters']);
	exit;
}

$validStatuses = ['pending', 'approved', 'rejected', 'completed'];
if (!in_array(strtolower($status), $validStatuses, true)) {
	echo json_encode(['success' => false, 'error' => 'Invalid status']);
	exit;
}

$pdo = require __DIR__ . '/config/db.php';

$stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE appointment_id = :id");
$stmt->execute([
	':status' => $status,
	':id' => $appointmentId
]);

echo json_encode(['success' => true]);

