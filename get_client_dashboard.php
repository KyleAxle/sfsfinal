<?php
require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No session user_id']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'details' => $e->getMessage()]);
    exit;
}

$sql = "
    SELECT 
        o.office_name AS office,
        a.appointment_id,
        a.user_id,
        u.first_name,
        u.last_name,
        u.email,
        a.concern,
        a.appointment_date,
        a.appointment_time,
        a.status,
        f.rating,
        f.comment AS feedback_comment,
        f.submitted_at AS feedback_submitted_at
    FROM appointments a
    JOIN public.users u ON a.user_id = u.user_id
    JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
    JOIN offices o ON ao.office_id = o.office_id
    LEFT JOIN feedback f ON f.appointment_id = a.appointment_id
    WHERE a.user_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $normalized = array_map(function ($row) {
        $appointment_id = isset($row['appointment_id']) && $row['appointment_id'] !== null ? (int)$row['appointment_id'] : 0;
        return [
            'office' => $row['office'] ?? '',
            'appointment_id' => $appointment_id > 0 ? $appointment_id : null,
            'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : null,
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? '',
            'email' => $row['email'] ?? '',
            'concern' => $row['concern'] ?? '',
            'appointment_date' => $row['appointment_date'] ?? '',
            'appointment_time' => $row['appointment_time'] ?? '',
            'status' => $row['status'] ?? '',
            'has_feedback' => isset($row['rating']) && $row['rating'] !== null,
            'rating' => isset($row['rating']) ? (int)$row['rating'] : null,
            'feedback_comment' => $row['feedback_comment'] ?? null,
            'feedback_submitted_at' => $row['feedback_submitted_at'] ?? null,
        ];
    }, $rows);

    echo json_encode($normalized);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load appointments', 'details' => $e->getMessage()]);
}