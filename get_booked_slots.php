<?php
header('Content-Type: application/json');

$officeId = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
$date = $_GET['date'] ?? '';

if ($officeId <= 0 || !$date) {
	echo json_encode(['error' => 'Missing office_id or date']);
	exit;
}

try {
	$pdo = require __DIR__ . '/config/db.php';
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['error' => 'Database connection failed']);
	exit;
}

$sql = "
	select distinct a.appointment_time
	from appointments a
	join appointment_offices ao on a.appointment_id = ao.appointment_id
	where ao.office_id = ?
	  and a.appointment_date = ?
	  and coalesce(lower(a.status::text), '') not in ('completed', 'cancelled')
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$officeId, $date]);
$slots = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'appointment_time');

$blockedStmt = $pdo->prepare("
	select start_time, end_time, coalesce(reason, '') as reason
	from office_blocked_slots
	where office_id = ?
	  and block_date = ?
	order by start_time asc
");
$blockedStmt->execute([$officeId, $date]);
$blockedTimes = $blockedStmt->fetchAll(PDO::FETCH_ASSOC);

$blockedRanges = [];
foreach ($blockedTimes as $block) {
	$blockedRanges[] = [
		'start_time' => $block['start_time'],
		'end_time' => $block['end_time'],
		'reason' => $block['reason'] ?? ''
	];
	$start = new DateTime($block['start_time']);
	$end = new DateTime($block['end_time']);
	while ($start < $end) {
		$slots[] = $start->format('H:i:s');
		$start->modify('+30 minutes');
	}
}

$slots = array_values(array_unique($slots));

echo json_encode([
	'slots' => $slots,
	'blocked_ranges' => $blockedRanges
]);

