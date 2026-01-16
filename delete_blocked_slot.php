<?php
require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['success' => false, 'error' => 'Invalid request']);
	exit;
}

if (!isset($_SESSION['staff_id'], $_SESSION['office_id'])) {
	http_response_code(401);
	echo json_encode(['success' => false, 'error' => 'Unauthorized']);
	exit;
}

$blockId = isset($_POST['block_id']) ? (int)$_POST['block_id'] : 0;
if ($blockId <= 0) {
	echo json_encode(['success' => false, 'error' => 'Invalid block id']);
	exit;
}

try {
	$pdo = require __DIR__ . '/config/db.php';
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['success' => false, 'error' => 'Database connection failed']);
	exit;
}

$officeId = (int)$_SESSION['office_id'];

$stmt = $pdo->prepare("delete from office_blocked_slots where block_id = ? and office_id = ?");
$stmt->execute([$blockId, $officeId]);

if ($stmt->rowCount() === 0) {
	echo json_encode(['success' => false, 'error' => 'Block not found']);
	return;
}

echo json_encode(['success' => true]);

