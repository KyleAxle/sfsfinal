<?php
header('Content-Type: application/json');
$pdo = require __DIR__ . '/../config/db.php';

$office = isset($_GET['office']) ? $_GET['office'] : '';

$sql = "SELECT 
            o.office_name AS office,
            a.user_id,
            a.last_name,
            a.first_name,
            a.email,
            a.appointment_date,
            a.appointment_time,
            a.paper_type,
            a.processing_days,
            a.release_date,
            a.concern,
            a.status
        FROM appointments a
        JOIN offices o ON a.office_id = o.office_id";

$params = [];
$types = "";

if ($office !== "") {
    $sql .= " WHERE o.office_name = ?";
    $params[] = $office;
    $types .= "s";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time ASC";

$stmt = $pdo->prepare($sql);
if ($office !== "") {
    $stmt->execute([$office]);
} else {
    $stmt->execute();
}
$result = $stmt;

$data = $result->fetchAll();
echo json_encode($data);
?>