<?php
header('Content-Type: application/json');

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$sql = "
    SELECT 
        o.office_id,
        o.office_name,
        COALESCE(o.location, '') as location,
        COALESCE(cfg.opening_time, '09:00:00') as opening_time,
        COALESCE(cfg.closing_time, '16:00:00') as closing_time,
        COALESCE(cfg.slot_interval_minutes, 30) as slot_interval_minutes
    FROM public.offices o
    LEFT JOIN public.office_time_configs cfg ON cfg.office_id = o.office_id
    ORDER BY LOWER(o.office_name)
";

try {
    $stmt = $pdo->query($sql);
    $offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format times for easier use in JavaScript
    $formatted = array_map(function($office) {
        return [
            'office_id' => (int)$office['office_id'],
            'office_name' => $office['office_name'],
            'location' => $office['location'],
            'opening_time' => substr($office['opening_time'], 0, 5), // HH:MM
            'closing_time' => substr($office['closing_time'], 0, 5), // HH:MM
            'slot_interval_minutes' => (int)$office['slot_interval_minutes']
        ];
    }, $offices);
    
    echo json_encode(['offices' => $formatted]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load offices', 'details' => $e->getMessage()]);
}

