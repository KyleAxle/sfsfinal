<?php
/**
 * Get available time slots for an office on a specific date
 * Used by AI for auto-booking
 */

require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

$officeId = isset($_GET['office_id']) ? (int)$_GET['office_id'] : 0;
$date = $_GET['date'] ?? '';

if ($officeId <= 0 || !$date) {
    echo json_encode(['success' => false, 'error' => 'Missing office_id or date']);
    exit;
}

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

try {
    // Get office configuration (with time configs from separate table)
    $officeStmt = $pdo->prepare("
        SELECT 
            o.office_id,
            o.office_name,
            COALESCE(cfg.opening_time, '09:00:00') as opening_time,
            COALESCE(cfg.closing_time, '16:00:00') as closing_time,
            COALESCE(cfg.slot_interval_minutes, 30) as slot_interval_minutes
        FROM public.offices o
        LEFT JOIN public.office_time_configs cfg ON cfg.office_id = o.office_id
        WHERE o.office_id = ?
    ");
    $officeStmt->execute([$officeId]);
    $office = $officeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$office) {
        echo json_encode(['success' => false, 'error' => 'Office not found']);
        exit;
    }
    
    // Get booked slots
    $bookedStmt = $pdo->prepare("
        SELECT DISTINCT a.appointment_time
        FROM appointments a
        INNER JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
        WHERE ao.office_id = ?
          AND a.appointment_date = ?
          AND COALESCE(LOWER(a.status::text), '') NOT IN ('completed', 'cancelled')
    ");
    $bookedStmt->execute([$officeId, $date]);
    $bookedSlots = array_column($bookedStmt->fetchAll(PDO::FETCH_ASSOC), 'appointment_time');
    
    // Get blocked slots
    $blockedStmt = $pdo->prepare("
        SELECT start_time, end_time, reason
        FROM office_blocked_slots
        WHERE office_id = ?
          AND block_date = ?
        ORDER BY start_time ASC
    ");
    $blockedStmt->execute([$officeId, $date]);
    $blockedRanges = $blockedStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate all possible slots
    $openingTime = new DateTime($office['opening_time']);
    $closingTime = new DateTime($office['closing_time']);
    $interval = (int)($office['slot_interval_minutes'] ?? 30);
    
    $allSlots = [];
    $current = clone $openingTime;
    
    while ($current < $closingTime) {
        $slotTime = $current->format('H:i:s');
        $slotTimeFormatted = $current->format('g:i A');
        
        // Check if slot is booked
        $isBooked = in_array($slotTime, $bookedSlots);
        
        // Check if slot is blocked
        $isBlocked = false;
        $blockReason = '';
        foreach ($blockedRanges as $block) {
            $blockStart = new DateTime($block['start_time']);
            $blockEnd = new DateTime($block['end_time']);
            if ($current >= $blockStart && $current < $blockEnd) {
                $isBlocked = true;
                $blockReason = $block['reason'] ?? 'Blocked';
                break;
            }
        }
        
        if (!$isBooked && !$isBlocked) {
            $allSlots[] = [
                'time' => $slotTime,
                'time_formatted' => $slotTimeFormatted,
                'available' => true
            ];
        }
        
        $current->modify("+{$interval} minutes");
    }
    
    echo json_encode([
        'success' => true,
        'office_id' => $officeId,
        'office_name' => $office['office_name'],
        'date' => $date,
        'available_slots' => $allSlots,
        'count' => count($allSlots),
        'next_available' => !empty($allSlots) ? $allSlots[0] : null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    error_log('get_available_slots_ai.php error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
}

