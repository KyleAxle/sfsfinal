<?php
/**
 * Health check endpoint for Railway
 * Prevents cold starts by keeping the app warm
 */
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Simple health check - just return OK
// Optionally check database connection
$health = [
    'status' => 'ok',
    'timestamp' => time(),
    'service' => 'SFS Appointment System'
];

// Optional: Quick database connection test (commented out to avoid overhead)
/*
try {
    $pdo = require __DIR__ . '/config/db.php';
    $pdo->query('SELECT 1');
    $health['database'] = 'connected';
} catch (Exception $e) {
    $health['database'] = 'error';
    $health['error'] = $e->getMessage();
}
*/

echo json_encode($health, JSON_PRETTY_PRINT);
