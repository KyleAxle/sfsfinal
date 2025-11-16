<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$pdo = require __DIR__ . '/config/db.php';

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

$user_sql = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
$user_sql->execute([$user_id]);
$user_row = $user_sql->fetch();
$first_name = $user_row['first_name'] ?? '';
$last_name = $user_row['last_name'] ?? '';
$email = $user_row['email'] ?? '';

$appointment_date = $_POST['appointment_date'] ?? '';
$appointment_time = $_POST['appointment_time'] ?? '';
$office_id = isset($_POST['office_id']) ? intval($_POST['office_id']) : 0;
$paper_type = $_POST['paperType'] ?? null;
$processing_days = $_POST['processingDays'] ?? null;
$release_date = $_POST['releaseDate'] ?? null;
$concern = $_POST['concern'] ?? $_POST['concernText'] ?? null;
$status = "Pending";

// Prevent duplicate non-completed appointments for same slot (ignore concern)
$check_sql = $pdo->prepare("SELECT COUNT(*) AS c FROM appointments WHERE user_id = ? AND office_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'Completed'");
$check_sql->execute([$user_id, $office_id, $appointment_date, $appointment_time]);
$count = (int)($check_sql->fetch()['c'] ?? 0);

if ($count > 0) {
    echo "duplicate";
    $conn->close();
    exit;
}

// Insert new appointment
$stmt = $pdo->prepare("INSERT INTO appointments 
    (user_id, first_name, last_name, email, appointment_date, appointment_time, office_id, paper_type, processing_days, release_date, concern, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if ($stmt->execute([$user_id, $first_name, $last_name, $email, $appointment_date, $appointment_time, $office_id, $paper_type, $processing_days, $release_date, $concern, $status])) {
    // Get the last inserted appointment_id
    $appointment_id = (int)$pdo->lastInsertId();

    // Insert into appointment_offices table
    $stmt2 = $pdo->prepare("INSERT INTO appointment_offices (appointment_id, office_id, status) VALUES (?, ?, ?)");
    $stmt2->execute([$appointment_id, $office_id, $status]);

    echo "success";
} else {
    echo "SQL error";
}
?>