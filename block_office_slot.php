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

$blockDate = $_POST['block_date'] ?? '';
$startTime = $_POST['start_time'] ?? '';
$endTime = $_POST['end_time'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (!$blockDate || !$startTime || !$endTime) {
	echo json_encode(['success' => false, 'error' => 'Missing fields']);
	exit;
}

if (strtotime($endTime) <= strtotime($startTime)) {
	echo json_encode(['success' => false, 'error' => 'End time must be after start time']);
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
$staffId = (int)$_SESSION['staff_id'];

$overlap = $pdo->prepare("
	select count(*) as c
	from office_blocked_slots
	where office_id = ?
	  and block_date = ?
	  and NOT (end_time <= ?::time or start_time >= ?::time)
");
$overlap->execute([$officeId, $blockDate, $startTime, $endTime]);
if ((int)($overlap->fetch()['c'] ?? 0) > 0) {
	echo json_encode(['success' => false, 'error' => 'This window already has a block.']);
	exit;
}

$stmt = $pdo->prepare("
	insert into office_blocked_slots (office_id, block_date, start_time, end_time, reason, created_by)
	values (?, ?, ?::time, ?::time, ?, ?)
	returning block_id, block_date, start_time, end_time, reason
");
$stmt->execute([$officeId, $blockDate, $startTime, $endTime, $reason, $staffId]);
$newBlock = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'block' => $newBlock]);

