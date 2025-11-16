<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No session user_id']);
    exit;
}
$user_id = intval($_SESSION['user_id']);

$pdo = require __DIR__ . '/config/db.php';

$sql = "SELECT 
            o.office_name AS office,
            a.user_id,
            a.last_name,
            a.first_name,
            a.email,
            a.concern,
            a.appointment_date,
            a.appointment_time,
            a.status,
            a.appointment_id
        FROM appointments a
        JOIN offices o ON a.office_id = o.office_id
        WHERE a.user_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$result = $stmt;

$data = $result->fetchAll();
echo json_encode($data);
?>