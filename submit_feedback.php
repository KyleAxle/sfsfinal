<?php
$pdo = require __DIR__ . '/config/db.php';
$appointment_id = $_POST['appointment_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = $_POST['comment'] ?? '';
if ($appointment_id && $rating) {
	$stmt = $pdo->prepare("INSERT INTO feedback (appointment_id, rating, comment, submitted_at) VALUES (:id, :rating, :comment, NOW())");
	$stmt->execute([':id' => $appointment_id, ':rating' => $rating, ':comment' => $comment]);
	echo json_encode(['success' => true]);
} else {
	echo json_encode(['success' => false]);
}
?>