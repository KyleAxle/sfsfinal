<?php
/**
 * AI Chatbot Assistant API
 * Uses Groq API (FREE) with PHP fallback
 * Features: Context-aware suggestions + Auto-booking capability
 */

// Set error handling to ensure we always return JSON
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors, log them instead

// Function to return error JSON and exit
function returnError($message, $code = 500) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

// Function to return success JSON
function returnSuccess($response, $source, $hasContext = false) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'response' => $response,
        'source' => $source,
        'has_context' => $hasContext
    ]);
    exit;
}

require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

// Load environment variables (fail gracefully)
try {
require_once __DIR__ . '/config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');
} catch (Exception $e) {
    error_log('Config loading error: ' . $e->getMessage());
    // Continue - we can still use PHP fallback without config
}

// Try to connect to database (fail gracefully - PHP fallback works without DB)
$pdo = null;
try {
$pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    // Continue - we can still use PHP fallback without database
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $message = trim($input['message'] ?? '');
    $conversationHistory = $input['history'] ?? [];

    if (empty($message)) {
        returnError('Message cannot be empty', 400);
    }

    // Get user ID if logged in (for context and auto-booking)
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    // Get office information from database (with office_id for booking)
    // If database fails, use empty array - PHP fallback will still work
    $offices = [];
    if ($pdo) {
        try {
    $officesStmt = $pdo->query("
        SELECT office_id, office_name, location, description 
        FROM public.offices 
        ORDER BY office_name
    ");
    $offices = $officesStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching offices: ' . $e->getMessage());
            // Continue with empty offices array
        }
    }
    
    // Get user's appointment history for context (if logged in)
    $userAppointments = [];
    if ($user_id > 0 && $pdo) {
        try {
            $apptStmt = $pdo->prepare("
                SELECT 
                    a.appointment_id,
                    a.appointment_date,
                    a.appointment_time,
                    a.concern,
                    a.status,
                    o.office_name,
                    o.office_id
                FROM appointments a
                INNER JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
                INNER JOIN offices o ON ao.office_id = o.office_id
                WHERE a.user_id = ?
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                LIMIT 5
            ");
            $apptStmt->execute([$user_id]);
            $userAppointments = $apptStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error fetching user appointments: ' . $e->getMessage());
            // Ignore errors, just continue without context
        }
    }
    
    // Check if this is a booking request
    $isBookingRequest = isBookingRequest($message);
    
    // Check if user is providing booking details (office, date, time, concern)
    $hasBookingContext = hasBookingContext($message, $offices);
    
    if ($isBookingRequest && $user_id > 0 && $pdo) {
        // Check if we have all required information
        $officeName = extractOfficeName($message, $offices);
        $extractedDate = extractDate($message);
        $extractedTime = extractTime($message);
        $extractedConcern = extractConcern($message);
        
        // Check if this is a follow-up to a previous booking request (from session)
        $bookingContext = null;
        if (isset($_SESSION['ai_booking_context'])) {
            $bookingContext = $_SESSION['ai_booking_context'];
        }
        
        // Also check conversation history
        if (!$bookingContext) {
            $bookingContext = getBookingContext($conversationHistory);
        }
        
        // Check if this is Registrar Office - requires paper type
        $isRegistrarOffice = false;
        $extractedPaperType = null;
        $paperType = null;
        $processingDays = null;
        $releaseDate = null;
        
        if ($officeName) {
            $isRegistrarOffice = stripos($officeName, 'registrar') !== false;
            
            if ($isRegistrarOffice) {
                $extractedPaperType = extractPaperType($message);
            }
        }
        
        // Merge with context if available
        if ($bookingContext) {
            $officeName = $officeName ?: ($bookingContext['office'] ?? null);
            $extractedDate = $extractedDate ?: ($bookingContext['date'] ?? null);
            $extractedTime = $extractedTime ?: ($bookingContext['time'] ?? null);
            $extractedConcern = $extractedConcern ?: ($bookingContext['concern'] ?? null);
            // Paper type from context (for Registrar Office)
            if ($isRegistrarOffice && isset($bookingContext['paper_type'])) {
                $extractedPaperType = $extractedPaperType ?: $bookingContext['paper_type'];
            }
        }
        
        // If we have paper type, calculate processing days
        if ($isRegistrarOffice && $extractedPaperType) {
            $paperType = $extractedPaperType;
            $processingDays = getProcessingDays($paperType);
        }
        
        // Validate concern - must be meaningful, not just leftover text
        // A valid concern should:
        // 1. Be explicitly provided (not just extracted from booking message)
        // 2. Be at least 10 characters
        // 3. Contain meaningful words (4+ characters)
        // 4. Not be just punctuation, fragments, or question marks
        // 5. Should describe a purpose/need, not just be leftover booking text
        $hasValidConcern = false;
        if ($extractedConcern) {
            $concernTrimmed = trim($extractedConcern);
            
            // Log for debugging
            error_log('Extracted concern: "' . $concernTrimmed . '" from message: "' . substr($message, 0, 100) . '"');
            
            // Check if it's a meaningful concern
            if (strlen($concernTrimmed) >= 10 && 
                !preg_match('/^[?.,!\-_\s]+$/', $concernTrimmed) &&
                preg_match('/\b\w{4,}\b/', $concernTrimmed) &&
                !preg_match('/^(st|nd|rd|th|\d+)$/i', $concernTrimmed)) {
                
                // Additional check: should not be just office name or date/time fragments
                $lowerConcern = strtolower($concernTrimmed);
                $isJustOfficeName = preg_match('/\b(assessment|registrar|cashier|guidance|library|clinic|office)\b/i', $lowerConcern) && 
                                    strlen($lowerConcern) < 20;
                
                // Check if it's just a question (like "january 15?")
                $isJustQuestion = preg_match('/^[a-z\s]*\?$/i', $concernTrimmed) && strlen($concernTrimmed) < 20;
                
                // Check if it contains action words that indicate a real concern
                $hasActionWords = preg_match('/\b(need|want|require|request|get|take|apply|submit|obtain|receive|check|verify|inquire|ask|help|assessment|test|transcript|diploma|certificate|payment|tuition|enrollment|registration)\b/i', $concernTrimmed);
                
                // More lenient: accept if it's 10+ chars and not just office name/question
                // Accept if it has action words OR is 15+ chars OR has meaningful words (4+ chars)
                if (!$isJustOfficeName && !$isJustQuestion) {
                    if ($hasActionWords || strlen($concernTrimmed) >= 15) {
                        $hasValidConcern = true;
                        error_log('Valid concern detected: ' . $concernTrimmed);
                    } else if (strlen($concernTrimmed) >= 10 && preg_match('/\b\w{4,}\b/', $concernTrimmed)) {
                        // Accept 10-14 chars if it has meaningful words (not just fragments)
                        $hasValidConcern = true;
                        error_log('Valid concern detected (10+ chars, meaningful words): ' . $concernTrimmed);
                    } else {
                        error_log('Invalid concern rejected: ' . $concernTrimmed . ' (isJustOfficeName: ' . ($isJustOfficeName ? 'yes' : 'no') . ', isJustQuestion: ' . ($isJustQuestion ? 'yes' : 'no') . ', hasActionWords: ' . ($hasActionWords ? 'yes' : 'no') . ', length: ' . strlen($concernTrimmed) . ')');
                    }
                } else {
                    error_log('Invalid concern rejected: ' . $concernTrimmed . ' (isJustOfficeName: ' . ($isJustOfficeName ? 'yes' : 'no') . ', isJustQuestion: ' . ($isJustQuestion ? 'yes' : 'no') . ')');
                }
            } else {
                error_log('Concern too short or invalid format: ' . $concernTrimmed);
            }
        } else {
            error_log('No concern extracted from message: ' . substr($message, 0, 100));
        }
        
        // ALWAYS require explicit concern - don't book without it
        // If we have office but missing other info, ask for it
        // For Registrar Office, also require paper type
        $missingInfo = [];
        if (!$extractedDate) $missingInfo[] = 'date';
        if (!$extractedTime) $missingInfo[] = 'time';
        if (!$hasValidConcern) $missingInfo[] = 'concern/reason';
        if ($isRegistrarOffice && !$paperType) $missingInfo[] = 'paper_type';
        
        if ($officeName && (!empty($missingInfo))) {
            // Store partial booking context
            $contextConcern = $hasValidConcern ? $extractedConcern : null;
            saveBookingContext($officeName, $extractedDate, $extractedTime, $contextConcern, $paperType);
            
            $response = "I'd like to help you book an appointment with **{$officeName}**!\n\n";
            $response .= "I need a few more details to complete your booking:\n\n";
            
            $questions = [];
            if (in_array('date', $missingInfo)) {
                $questions[] = "**📅 Date**: When would you like the appointment?\n   (e.g., \"January 15, 2026\" or \"1/15/2026\")";
            }
            if (in_array('time', $missingInfo)) {
                $questions[] = "**🕐 Time**: What time works for you?\n   (e.g., \"1 PM\", \"1:30 PM\", or \"13:00\")";
            }
            if (in_array('paper_type', $missingInfo)) {
                $questions[] = "**📄 Paper Type**: What document do you need?\n   Options: \"Transcript of Records\", \"Diploma\", \"Certificate\", or \"Others\"";
            }
            if (in_array('concern/reason', $missingInfo)) {
                $questions[] = "**📝 Concern/Reason**: What is this appointment for?\n   (e.g., \"I need to request a transcript\" or \"Request for diploma\")";
            }
            
            $response .= implode("\n\n", $questions);
            $response .= "\n\nYou can provide all details at once, or I'll ask for them one by one. Just reply with the information!";
            
            echo json_encode([
                'success' => true,
                'response' => $response,
                'source' => 'php',
                'needs_info' => true,
                'missing_info' => $missingInfo
            ]);
            exit;
        }
        
        // Debug logging
        error_log('Booking check - Office: ' . ($officeName ?: 'none') . ', Date: ' . ($extractedDate ?: 'none') . ', Time: ' . ($extractedTime ?: 'none') . ', Concern valid: ' . ($hasValidConcern ? 'yes' : 'no'));
        
        // IMPORTANT: Always ask for concern/reason explicitly before booking
        // Even if we have office, date, and time, we must ask for concern before confirming
        $hasBasicInfo = $officeName && $extractedDate && $extractedTime;
        
        // Check if this is a response to our concern question (confirmation or new concern)
        $isConcernResponse = false;
        if ($bookingContext && isset($bookingContext['office']) && isset($bookingContext['date']) && isset($bookingContext['time'])) {
            // We previously asked for concern, check if this message provides it
            if ($hasValidConcern && $extractedConcern) {
                // Check if concern words appear in current message (not just from context)
                $concernWords = preg_split('/\s+/', strtolower($extractedConcern));
                $messageLower = strtolower($message);
                foreach ($concernWords as $word) {
                    if (strlen($word) >= 4 && strpos($messageLower, $word) !== false) {
                        $isConcernResponse = true;
                        break;
                    }
                }
                
                // Also check for confirmation keywords if concern was already in context
                if (!$isConcernResponse && isset($bookingContext['concern'])) {
                    $confirmationKeywords = ['yes', 'correct', 'right', 'confirm', 'proceed', 'book it', 'that\'s right', 'that is correct', 'okay', 'ok', 'sure', 'go ahead', 'book'];
                    foreach ($confirmationKeywords as $keyword) {
                        if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $messageLower)) {
                            // Make sure it's not just a question
                            if (!preg_match('/\?$/', $message)) {
                                $isConcernResponse = true;
                                $extractedConcern = $bookingContext['concern']; // Use concern from context
                                $hasValidConcern = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        // If we have basic info but haven't explicitly asked for concern yet, ask for it
        if ($hasBasicInfo && !$isConcernResponse) {
            $dateFormatted = date('F j, Y', strtotime($extractedDate));
            $timeFormatted = date('g:i A', strtotime($extractedTime));
            
            $response = "Great! I have your appointment details:\n\n";
            $response .= "**Office:** {$officeName}\n";
            $response .= "**Date:** {$dateFormatted}\n";
            $response .= "**Time:** {$timeFormatted}\n";
            
            if ($isRegistrarOffice && $paperType) {
                $response .= "**Paper Type:** {$paperType}\n";
            }
            
            $response .= "\n**Before I confirm and book this appointment, I need to know:**\n\n";
            $response .= "**📝 What is the reason or concern for this appointment?**\n\n";
            $response .= "Please tell me what you need help with. For example:\n";
            $response .= "• \"I need to take an assessment test\"\n";
            $response .= "• \"Request for transcript of records\"\n";
            $response .= "• \"Payment inquiry for tuition fees\"\n";
            $response .= "• \"Enrollment assistance\"\n";
            $response .= "• Or describe your specific need\n\n";
            $response .= "Once you provide the reason, I'll book your appointment right away!";
            
            // Store booking context (including concern if we have it, but we'll still ask)
            saveBookingContext($officeName, $extractedDate, $extractedTime, $extractedConcern, $paperType);
            
            echo json_encode([
                'success' => true,
                'response' => $response,
                'source' => 'php',
                'needs_info' => true,
                'needs_concern' => true,
                'missing_info' => ['concern/reason']
            ]);
            exit;
        }
        
        // If we have all info AND user has provided/confirmed concern, proceed with booking
        // IMPORTANT: Always require valid concern - never book without it
        // For Registrar Office, also require paper type
        $hasAllRequiredInfo = $officeName && $extractedDate && $extractedTime && $hasValidConcern && $isConcernResponse;
        if ($isRegistrarOffice) {
            $hasAllRequiredInfo = $hasAllRequiredInfo && $paperType;
        }
        
        if ($hasAllRequiredInfo) {
            error_log('All booking info present and confirmed, attempting to book...');
            try {
                // Calculate release date if we have appointment date and processing days
                if ($isRegistrarOffice && $extractedDate && $processingDays) {
                    $releaseDate = calculateReleaseDate($extractedDate, $processingDays);
                    error_log("Calculated release date: {$releaseDate} (appointment: {$extractedDate}, processing: {$processingDays} days)");
                }
                
                $bookingResult = handleAutoBooking($pdo, $message, $offices, $user_id, $extractedDate, $extractedTime, $extractedConcern, $paperType, $processingDays, $releaseDate);
                error_log('Booking result: ' . json_encode($bookingResult));
                if ($bookingResult['success']) {
                    error_log('Booking successful! Appointment ID: ' . ($bookingResult['booking']['appointment_id'] ?? 'unknown'));
                    clearBookingContext(); // Clear context after successful booking
                    header('Content-Type: application/json');
                    echo json_encode($bookingResult);
                    exit;
                } else {
                    // Booking failed - return error immediately, don't let Groq API say it's booked
                    error_log('Booking failed: ' . ($bookingResult['error'] ?? 'Unknown error'));
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => $bookingResult['error'] ?? 'Failed to book appointment. Please try manually.',
                        'booking_failed' => true
                    ]);
                    exit;
                }
            } catch (Exception $e) {
                error_log('Auto-booking exception: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                // Return error instead of continuing to Groq (which might say it's booked)
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'An error occurred while booking: ' . $e->getMessage() . '. Please try booking manually.',
                    'booking_failed' => true
                ]);
                exit;
            }
        } elseif ($officeName) {
            // Has office but missing other info - already handled above (will ask for missing info)
            // This ensures we never book without valid concern
            error_log('Missing booking info - Office found but missing date/time/concern');
        } else {
            // No office specified - let handleAutoBooking handle it (it will ask for office)
            try {
        $bookingResult = handleAutoBooking($pdo, $message, $offices, $user_id);
        if ($bookingResult['success']) {
                    // Only proceed if handleAutoBooking returns success
                    header('Content-Type: application/json');
            echo json_encode($bookingResult);
            exit;
                } else {
                    // Booking failed - return error immediately
                    error_log('Booking failed: ' . ($bookingResult['error'] ?? 'Unknown error'));
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => $bookingResult['error'] ?? 'Failed to book appointment. Please try manually.',
                        'booking_failed' => true
                    ]);
                    exit;
                }
            } catch (Exception $e) {
                error_log('Auto-booking error: ' . $e->getMessage());
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'An error occurred while booking: ' . $e->getMessage() . '. Please try booking manually.',
                    'booking_failed' => true
                ]);
                exit;
            }
        }
    }
    
    // Get Groq API key from environment (optional - works without it using PHP fallback)
    $groqApiKey = getenv('GROQ_API_KEY');
    $useGroq = !empty($groqApiKey) && getenv('USE_PHP_FALLBACK') !== 'true';

    // Try Groq API first if available, otherwise use PHP fallback
    $responseSource = 'php';
    $aiResponse = '';
    
    if ($useGroq) {
        try {
            $aiResponse = callGroqAPI($groqApiKey, $message, $conversationHistory, $offices, $userAppointments);
            $responseSource = 'groq';
        } catch (Exception $e) {
            // If Groq fails, fall back to PHP
            error_log('Groq API error: ' . $e->getMessage());
            $aiResponse = generatePHPResponse($message, $offices, $userAppointments);
            $responseSource = 'php_fallback';
        }
    } else {
        // Use PHP fallback directly (works without any API key!)
        $aiResponse = generatePHPResponse($message, $offices, $userAppointments);
        $responseSource = 'php';
    }
    
    // Log query for analytics (non-blocking - only if database is available)
    if ($pdo) {
        try {
            logAIQuery($pdo, $user_id, $message, $aiResponse, $responseSource, $isBookingRequest);
} catch (Exception $e) {
            error_log('Analytics logging error: ' . $e->getMessage());
            // Continue - logging failure shouldn't break the response
        }
    }
    
    returnSuccess($aiResponse, $responseSource, !empty($userAppointments));

} catch (Exception $e) {
    error_log('AI Chat error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Even on error, try to return a helpful response using PHP fallback
    try {
        $fallbackResponse = generatePHPResponse($message ?? 'Hello', [], []);
        returnSuccess($fallbackResponse, 'php_error_fallback', false);
    } catch (Exception $fallbackError) {
        // Last resort - return error message
        returnError('An error occurred. Please try again or contact support.', 500);
    }
} catch (Error $e) {
    // Catch fatal errors (PHP 7+)
    error_log('Fatal error in AI Chat: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Try to return a basic response
    try {
        $fallbackResponse = "Hello! I'm having some technical difficulties, but I can still help you. Please try asking your question again, or contact support if the problem persists.";
        returnSuccess($fallbackResponse, 'php_error_fallback', false);
    } catch (Exception $fallbackError) {
        returnError('Service temporarily unavailable. Please try again later.', 503);
    }
}

/**
 * Create appointment directly (without HTTP call)
 */
function createAppointmentDirectly($pdo, $user_id, $bookingData) {
    error_log('createAppointmentDirectly called with: ' . json_encode($bookingData));
    
    $office_id = (int)$bookingData['office_id'];
    $appointment_date = trim($bookingData['appointment_date']);
    $appointment_time_raw = trim($bookingData['appointment_time']);
    $concern = trim($bookingData['concern'] ?? '');
    $paper_type = trim($bookingData['paper_type'] ?? '');
    $processing_days = isset($bookingData['processing_days']) ? (int)$bookingData['processing_days'] : null;
    $release_date = trim($bookingData['release_date'] ?? '');
    
    error_log("Booking params - Office ID: {$office_id}, Date: {$appointment_date}, Time: {$appointment_time_raw}, Concern: {$concern}, Paper Type: {$paper_type}, Processing Days: {$processing_days}, Release Date: {$release_date}");
    
    // Validate concern is provided and meaningful
    if (empty($concern) || strlen($concern) < 10) {
        error_log('Concern validation failed - empty or too short: ' . strlen($concern));
        return [
            'success' => false,
            'error' => 'Concern is required and must be at least 10 characters. Please specify what this appointment is for.'
        ];
    }
    
    // Parse time to HH:MM:SS format
    $timeFormatted = '';
    if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $appointment_time_raw, $matches)) {
        $hour = intval($matches[1]);
        $minute = intval($matches[2]);
        $ampm = strtoupper($matches[3]);
        
        if ($ampm === 'PM' && $hour != 12) {
            $hour += 12;
        } elseif ($ampm === 'AM' && $hour == 12) {
            $hour = 0;
        }
        
        $timeFormatted = sprintf('%02d:%02d:00', $hour, $minute);
    } else {
        if (preg_match('/^(\d{1,2}):(\d{2})/', $appointment_time_raw, $matches)) {
            $timeFormatted = sprintf('%02d:%02d:00', intval($matches[1]), intval($matches[2]));
        } else {
            $timeFormatted = $appointment_time_raw;
        }
    }
    
    try {
        // Check if slot is blocked
        $blockedCheck = $pdo->prepare("
            SELECT COUNT(*) AS c
            FROM public.office_blocked_slots
            WHERE office_id = ?
              AND block_date = ?
              AND start_time <= ?::time
              AND end_time > ?::time
        ");
        $blockedCheck->execute([$office_id, $appointment_date, $timeFormatted, $timeFormatted]);
        if ((int)($blockedCheck->fetch()['c'] ?? 0) > 0) {
            return [
                'success' => false,
                'error' => 'This time slot is unavailable due to an office event'
            ];
        }
        
        // Check for duplicate appointments - get details for better error message
        $checkStmt = $pdo->prepare("
            SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.concern
            FROM public.appointments a
            INNER JOIN public.appointment_offices ao ON a.appointment_id = ao.appointment_id
            WHERE a.user_id = ?
              AND ao.office_id = ?
              AND a.appointment_date = ?
              AND a.appointment_time = ?
              AND LOWER(a.status::text) NOT IN ('completed', 'cancelled')
        ");
        $checkStmt->execute([$user_id, $office_id, $appointment_date, $timeFormatted]);
        $existingAppointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingAppointment) {
            $existingStatus = strtolower($existingAppointment['status'] ?? 'pending');
            $dateFormatted = date('F j, Y', strtotime($appointment_date));
            $timeFormattedDisplay = date('g:i A', strtotime($timeFormatted));
            
            return [
                'success' => false,
                'error' => "You already have an appointment with this office on {$dateFormatted} at {$timeFormattedDisplay} (Status: {$existingStatus}). Please choose a different date or time.",
                'duplicate_appointment' => true,
                'existing_appointment_id' => $existingAppointment['appointment_id']
            ];
        }
        
        // Create appointment - use 'pending' (lowercase) to match ENUM schema
        $status = 'pending';
        
        // Build INSERT query with optional fields for Registrar Office
        $fields = ['user_id', 'appointment_date', 'appointment_time', 'concern', 'status'];
        $values = [$user_id, $appointment_date, $timeFormatted, $concern, $status];
        $placeholders = ['?', '?', '?', '?', '?::appointment_status'];
        
        if (!empty($paper_type)) {
            $fields[] = 'paper_type';
            $values[] = $paper_type;
            $placeholders[] = '?';
        }
        
        if ($processing_days !== null && $processing_days > 0) {
            $fields[] = 'processing_days';
            $values[] = $processing_days;
            $placeholders[] = '?';
        }
        
        if (!empty($release_date)) {
            $fields[] = 'release_date';
            $values[] = $release_date;
            $placeholders[] = '?';
        }
        
        $fieldsStr = implode(', ', $fields);
        $placeholdersStr = implode(', ', $placeholders);
        
        $stmt = $pdo->prepare("
            INSERT INTO public.appointments ({$fieldsStr})
            VALUES ({$placeholdersStr})
        ");
        
        error_log("Executing INSERT: user_id={$user_id}, date={$appointment_date}, time={$timeFormatted}, concern={$concern}, status={$status}, paper_type={$paper_type}, processing_days={$processing_days}, release_date={$release_date}");
        $result = $stmt->execute($values);
        
        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            error_log('Failed to create appointment: ' . json_encode($errorInfo));
            throw new Exception('Failed to create appointment: ' . ($errorInfo[2] ?? 'Unknown error'));
        }
        
        error_log('Appointment INSERT successful, getting appointment_id...');
        $appointment_id = (int)$pdo->lastInsertId('appointments_appointment_id_seq');
        error_log('First attempt - appointment_id: ' . $appointment_id);
        
        if ($appointment_id <= 0) {
            $stmt_id = $pdo->query("SELECT lastval()");
            $appointment_id = (int)($stmt_id->fetchColumn() ?? 0);
            error_log('Second attempt (lastval) - appointment_id: ' . $appointment_id);
        }
        
        if ($appointment_id <= 0) {
            error_log('Failed to get appointment ID after insert - both methods failed');
            throw new Exception('Failed to get appointment ID');
        }
        
        error_log('Successfully got appointment_id: ' . $appointment_id);
        
        // Verify office exists before linking
        $checkOffice = $pdo->prepare("SELECT office_id FROM public.offices WHERE office_id = ?");
        $checkOffice->execute([$office_id]);
        if (!$checkOffice->fetch()) {
            // Rollback appointment
            $pdo->prepare("DELETE FROM public.appointments WHERE appointment_id = ?")->execute([$appointment_id]);
            error_log("Office ID {$office_id} does not exist");
            throw new Exception('Office not found. Please contact support.');
        }
        
        // Link to office - use 'pending' (lowercase) for appointment_offices with ENUM casting
        $officeStatus = 'pending';
        $stmt2 = $pdo->prepare("
            INSERT INTO public.appointment_offices (appointment_id, office_id, status)
            VALUES (?, ?, ?::office_assignment_status)
        ");
        
        error_log("Linking office: appointment_id={$appointment_id}, office_id={$office_id}, status={$officeStatus}");
        $result2 = $stmt2->execute([$appointment_id, $office_id, $officeStatus]);
        
        if (!$result2) {
            $errorInfo2 = $stmt2->errorInfo();
            error_log('Failed to assign office: ' . json_encode($errorInfo2));
            // Rollback appointment
            $pdo->prepare("DELETE FROM public.appointments WHERE appointment_id = ?")->execute([$appointment_id]);
            throw new Exception('Failed to assign office: ' . ($errorInfo2[2] ?? 'Unknown error'));
        }
        
        error_log('Office linked successfully!');
        
        // Verify the appointment_offices record was created correctly
        $verifyStmt = $pdo->prepare("
            SELECT ao.appointment_id, ao.office_id, ao.status, o.office_name
            FROM public.appointment_offices ao
            JOIN public.offices o ON o.office_id = ao.office_id
            WHERE ao.appointment_id = ?
        ");
        $verifyStmt->execute([$appointment_id]);
        $verification = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verification) {
            error_log('ERROR: Appointment_offices record not found after insert! Appointment ID: ' . $appointment_id);
            // Rollback appointment
            $pdo->prepare("DELETE FROM public.appointments WHERE appointment_id = ?")->execute([$appointment_id]);
            throw new Exception('Failed to verify appointment-office link. Please try again.');
        }
        
        error_log('Verification: Appointment ID ' . $appointment_id . ' linked to Office ID ' . $verification['office_id'] . ' (' . $verification['office_name'] . ')');
        
        // Get office name for response
        $office = $verification;
        
        error_log('Booking completed successfully! Appointment ID: ' . $appointment_id . ', Office: ' . ($office['office_name'] ?? 'Unknown'));
        
        return [
            'success' => true,
            'appointment_id' => $appointment_id,
            'office_name' => $office['office_name'] ?? 'Office',
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time_raw,
            'message' => 'Appointment booked successfully!'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Detect if message is a booking request
 */
function isBookingRequest($message) {
    $lower = strtolower($message);
    $bookingKeywords = [
        'book', 'schedule', 'appointment', 'reserve', 'book me', 'i want to book',
        'can you book', 'please book', 'book an appointment', 'set up appointment',
        'mag-book', 'gusto ko mag-book', 'pwedeng mag-book'
    ];
    
    foreach ($bookingKeywords as $keyword) {
        if (strpos($lower, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Check if message has booking context (office, date, time mentioned)
 */
function hasBookingContext($message, $offices) {
    $officeName = extractOfficeName($message, $offices);
    $date = extractDate($message);
    $time = extractTime($message);
    return !empty($officeName) || !empty($date) || !empty($time);
}

/**
 * Get booking context from conversation history
 */
function getBookingContext($conversationHistory) {
    // Look for booking context in recent messages
    $recentHistory = array_slice($conversationHistory, -5); // Last 5 messages
    foreach (array_reverse($recentHistory) as $msg) {
        if (isset($msg['booking_context'])) {
            return $msg['booking_context'];
        }
    }
    return null;
}

/**
 * Save booking context (for multi-turn conversations)
 */
function saveBookingContext($office, $date, $time, $concern, $paperType = null) {
    // Store in session for next request
    if (!isset($_SESSION['ai_booking_context'])) {
        $_SESSION['ai_booking_context'] = [];
    }
    $_SESSION['ai_booking_context'] = array_filter([
        'office' => $office,
        'date' => $date,
        'time' => $time,
        'concern' => $concern
    ]);
}

/**
 * Clear booking context
 */
function clearBookingContext() {
    unset($_SESSION['ai_booking_context']);
}

/**
 * Handle auto-booking request
 */
function handleAutoBooking($pdo, $message, $offices, $user_id, $preferredDate = null, $preferredTime = null, $concern = null, $paperType = null, $processingDays = null, $releaseDate = null) {
    try {
    // Extract office name from message
    $officeName = extractOfficeName($message, $offices);
    if (!$officeName) {
        return [
            'success' => false,
                'error' => 'Please specify which office you want to book with. For example: "Book me with Registrar" or "Schedule appointment with Cashier".'
        ];
    }
    
    // Find office ID
    $officeId = null;
    foreach ($offices as $office) {
        if (stripos($office['office_name'], $officeName) !== false) {
            $officeId = (int)$office['office_id'];
            $officeName = $office['office_name'];
            break;
        }
    }
    
    if (!$officeId) {
        return [
            'success' => false,
            'error' => 'Office not found. Please specify a valid office name.'
        ];
    }
    
    // Use provided date or extract from message
    if (!$preferredDate) {
    $preferredDate = extractDate($message);
    }
    if (!$preferredDate) {
        // Default to tomorrow
        $tomorrow = new DateTime('tomorrow');
        $preferredDate = $tomorrow->format('Y-m-d');
    }
    
    // Get available slots for the office and date
    // Use direct file inclusion for internal calls
    $_GET['office_id'] = $officeId;
    $_GET['date'] = $preferredDate;
    ob_start();
    try {
    include __DIR__ . '/get_available_slots_ai.php';
    } catch (Exception $e) {
        ob_end_clean();
        error_log('Error including get_available_slots_ai.php: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => 'Could not check available slots. Please try booking manually.'
        ];
    }
    $slotsResponse = ob_get_clean();
    
    if (empty($slotsResponse)) {
        error_log('Empty response from get_available_slots_ai.php');
        return [
            'success' => false,
            'error' => 'Could not check available slots. Please try booking manually.'
        ];
    }
    
    $slotsData = json_decode($slotsResponse, true);
    if (!$slotsData) {
        error_log('Invalid JSON from get_available_slots_ai.php: ' . substr($slotsResponse, 0, 200));
        return [
            'success' => false,
            'error' => 'Could not check available slots. Please try booking manually.'
        ];
    }
    
    if (!$slotsData['success'] || empty($slotsData['available_slots'])) {
        // Try next day
        $nextDate = new DateTime($preferredDate);
        $nextDate->modify('+1 day');
        $nextDateStr = $nextDate->format('Y-m-d');
        
        // Try next day
        $_GET['office_id'] = $officeId;
        $_GET['date'] = $nextDateStr;
        ob_start();
        try {
        include __DIR__ . '/get_available_slots_ai.php';
        } catch (Exception $e) {
            ob_end_clean();
            error_log('Error including get_available_slots_ai.php (retry): ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Could not check available slots. Please try booking manually.'
            ];
        }
        $slotsResponse = ob_get_clean();
        
        if (empty($slotsResponse)) {
            return [
                'success' => false,
                'error' => "No available slots found for {$officeName}. Please try a different date or book manually."
            ];
        }
        
        $slotsData = json_decode($slotsResponse, true);
        if (!$slotsData || !$slotsData['success'] || empty($slotsData['available_slots'])) {
            return [
                'success' => false,
                'error' => "No available slots found for {$officeName}. Please try a different date or book manually."
            ];
        }
        $preferredDate = $nextDateStr;
    }
    
    // Check if we have available slots
    if (empty($slotsData['available_slots']) || !isset($slotsData['available_slots'][0])) {
        error_log('No available slots in response: ' . json_encode($slotsData));
        return [
            'success' => false,
            'error' => "No available slots found for {$officeName} on " . date('F j, Y', strtotime($preferredDate)) . ". Please try a different date or book manually."
        ];
    }
    
    // Use provided time or find best available slot
    $appointmentTime = null;
    $appointmentTimeFormatted = null;
    
    if ($preferredTime) {
        // User specified a time - convert to HH:MM:SS format for matching
        $preferredTimeFormatted = $preferredTime;
        if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(AM|PM)/i', $preferredTime, $matches)) {
            $hour = intval($matches[1]);
            $minute = isset($matches[2]) ? intval($matches[2]) : 0;
            $ampm = strtoupper($matches[3]);
            if ($ampm === 'PM' && $hour != 12) $hour += 12;
            elseif ($ampm === 'AM' && $hour == 12) $hour = 0;
            $preferredTimeFormatted = sprintf('%02d:%02d:00', $hour, $minute);
        } elseif (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $preferredTime, $matches)) {
            // Already in HH:MM format
            $preferredTimeFormatted = sprintf('%02d:%02d:00', intval($matches[1]), intval($matches[2]));
        }
        
        // Find closest matching slot (within 30 minutes)
        $bestMatch = null;
        $bestDiff = PHP_INT_MAX;
        
        foreach ($slotsData['available_slots'] as $slot) {
            $slotTime = $slot['time']; // HH:MM:SS format
            $diff = abs(strtotime($slotTime) - strtotime($preferredTimeFormatted));
            
            if ($diff < $bestDiff && $diff <= 1800) { // Within 30 minutes
                $bestDiff = $diff;
                $bestMatch = $slot;
            }
        }
        
        if ($bestMatch) {
            $appointmentTime = $bestMatch['time_formatted'];
            $appointmentTimeFormatted = $bestMatch['time'];
        } elseif (!empty($slotsData['available_slots'])) {
            // No close match found, use first available slot
            $appointmentTime = $slotsData['available_slots'][0]['time_formatted'];
            $appointmentTimeFormatted = $slotsData['available_slots'][0]['time'];
        }
    } else {
        // No time specified - use first available slot
        if (!empty($slotsData['available_slots'])) {
    $bestSlot = $slotsData['available_slots'][0];
            $appointmentTime = $bestSlot['time_formatted'] ?? $bestSlot['time'] ?? null;
            $appointmentTimeFormatted = $bestSlot['time'] ?? null;
        }
    }
    
    if (!$appointmentTime || !$appointmentTimeFormatted) {
        error_log('Invalid slot format: ' . json_encode($slotsData['available_slots'] ?? []));
        return [
            'success' => false,
            'error' => 'Invalid time slot format. Please try booking manually.'
        ];
    }
    
    // Use provided concern (should already be validated before reaching here)
    // If no concern provided, return error - never use default
    if (!$concern || trim($concern) === '') {
        error_log('Warning: handleAutoBooking called without concern');
        return [
            'success' => false,
            'error' => 'Concern is required for booking. Please specify what this appointment is for.'
        ];
    }
    
    // Final validation - ensure concern is meaningful
    $concernTrimmed = trim($concern);
    if (strlen($concernTrimmed) < 10) {
        return [
            'success' => false,
            'error' => 'Please provide a more detailed concern/reason for this appointment (at least 10 characters).'
        ];
    }
    
    // Check if it's just punctuation or fragments
    if (preg_match('/^[?.,!\-_\s]+$/', $concernTrimmed) || !preg_match('/\b\w{4,}\b/', $concernTrimmed)) {
        return [
            'success' => false,
            'error' => 'Please provide a valid concern/reason describing what you need (e.g., "I need to take an assessment test").'
        ];
    }
    
    // Book the appointment (use formatted time for display, but store in HH:MM:SS format)
    $bookingData = [
        'office_id' => $officeId,
        'appointment_date' => $preferredDate,
        'appointment_time' => $appointmentTimeFormatted ?: $appointmentTime, // Use HH:MM:SS format for database
        'concern' => $concern
    ];
    
    // Call booking function directly (refactored approach)
    // Pass $pdo as parameter since it's needed
    try {
    $bookingResult = createAppointmentDirectly($pdo, $user_id, $bookingData);
    
    if ($bookingResult['success']) {
        $dateFormatted = date('F j, Y', strtotime($preferredDate));
        return [
            'success' => true,
            'response' => "✅ **Appointment booked successfully!**\n\n" .
                         "**Office:** {$officeName}\n" .
                         "**Date:** {$dateFormatted}\n" .
                         "**Time:** {$appointmentTime}\n" .
                         "**Concern:** {$concern}\n\n" .
                         "Your appointment is now pending approval. You'll receive updates on the status.",
            'booking' => $bookingResult,
            'source' => 'auto_booking'
        ];
    } else {
            error_log('Booking failed: ' . ($bookingResult['error'] ?? 'Unknown error'));
        return [
            'success' => false,
            'error' => $bookingResult['error'] ?? 'Failed to book appointment. Please try manually.'
            ];
        }
    } catch (Exception $e) {
        error_log('Exception in createAppointmentDirectly: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return [
            'success' => false,
            'error' => 'An error occurred while booking. Please try manually: ' . $e->getMessage()
        ];
    }
    } catch (Exception $e) {
        error_log('Exception in handleAutoBooking: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return [
            'success' => false,
            'error' => 'An error occurred while processing your booking request. Please try booking manually.'
        ];
    }
}

/**
 * Extract office name from message
 */
function extractOfficeName($message, $offices) {
    $lower = strtolower($message);
    
    // Check for office keywords
    $officeKeywords = [
        'registrar' => ['registrar', 'transcript', 'diploma', 'certificate', 'records'],
        'cashier' => ['cashier', 'payment', 'tuition', 'pay', 'financial'],
        'guidance' => ['guidance', 'counseling', 'counselor'],
        'library' => ['library', 'book', 'borrow'],
        'clinic' => ['clinic', 'health', 'medical', 'doctor'],
        'assessment' => ['assessment', 'assess', 'testing', 'exam']
    ];
    
    foreach ($officeKeywords as $officeType => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($lower, $keyword) !== false) {
                // Find matching office
                foreach ($offices as $office) {
                    $officeNameLower = strtolower($office['office_name']);
                    if (strpos($officeNameLower, $officeType) !== false) {
                        return $office['office_name'];
                    }
                }
            }
        }
    }
    
    // Try direct office name match
    foreach ($offices as $office) {
        if (stripos($message, $office['office_name']) !== false) {
            return $office['office_name'];
        }
    }
    
    return null;
}

/**
 * Extract date preference from message
 * Handles formats like: "January 15, 2026", "1/15/2026", "january 15", etc.
 */
function extractDate($message) {
    $lower = strtolower($message);
    
    // Check for full date format: "January 15, 2026" or "Jan 15, 2026"
    if (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)\s+(\d{1,2})(?:st|nd|rd|th)?,?\s+(\d{4})/i', $message, $matches)) {
        $monthName = strtolower($matches[1]);
        $day = (int)$matches[2];
        $year = (int)$matches[3];
        
        $monthMap = [
            'january' => 1, 'jan' => 1,
            'february' => 2, 'feb' => 2,
            'march' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'may' => 5,
            'june' => 6, 'jun' => 6,
            'july' => 7, 'jul' => 7,
            'august' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9, 'sept' => 9,
            'october' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11,
            'december' => 12, 'dec' => 12
        ];
        
        $month = $monthMap[$monthName] ?? null;
        if ($month && $day >= 1 && $day <= 31 && $year >= 2020 && $year <= 2100) {
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }
    
    // Check for date without year: "January 15" (assume current or next year)
    if (preg_match('/(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)\s+(\d{1,2})(?:st|nd|rd|th)?/i', $message, $matches)) {
        $monthName = strtolower($matches[1]);
        $day = (int)$matches[2];
        
        $monthMap = [
            'january' => 1, 'jan' => 1,
            'february' => 2, 'feb' => 2,
            'march' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'may' => 5,
            'june' => 6, 'jun' => 6,
            'july' => 7, 'jul' => 7,
            'august' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9, 'sept' => 9,
            'october' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11,
            'december' => 12, 'dec' => 12
        ];
        
        $month = $monthMap[$monthName] ?? null;
        if ($month && $day >= 1 && $day <= 31) {
            $year = date('Y');
            $targetDate = new DateTime("{$year}-{$month}-{$day}");
            // If date has passed this year, use next year
            if ($targetDate < new DateTime('today')) {
                $year++;
            }
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }
    }
    
    // Check for specific dates in MM/DD/YYYY or DD/MM/YYYY format
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $message, $matches)) {
        $first = (int)$matches[1];
        $second = (int)$matches[2];
        $year = (int)$matches[3];
        
        // Determine which format makes more sense
        // If first number > 12, it must be DD/MM/YYYY (day > 12)
        // If second number > 12, it must be MM/DD/YYYY (day > 12)
        // If both <= 12, prefer the one closer to today's date
        
        $today = new DateTime();
        $currentYear = (int)$today->format('Y');
        $currentMonth = (int)$today->format('m');
        $currentDay = (int)$today->format('d');
        
        $isMMDD = false;
        $isDDMM = false;
        
        // Check if MM/DD/YYYY is valid
        if ($first <= 12 && $second <= 31 && checkdate($first, $second, $year)) {
            $isMMDD = true;
        }
        
        // Check if DD/MM/YYYY is valid
        if ($first <= 31 && $second <= 12 && checkdate($second, $first, $year)) {
            $isDDMM = true;
        }
        
        // If only one format is valid, use it
        if ($isMMDD && !$isDDMM) {
            return sprintf('%04d-%02d-%02d', $year, $first, $second);
        }
        if ($isDDMM && !$isMMDD) {
            return sprintf('%04d-%02d-%02d', $year, $second, $first);
        }
        
        // If both are valid, choose based on which is closer to today
        if ($isMMDD && $isDDMM) {
            $dateMMDD = new DateTime(sprintf('%04d-%02d-%02d', $year, $first, $second));
            $dateDDMM = new DateTime(sprintf('%04d-%02d-%02d', $year, $second, $first));
            
            $diffMMDD = abs($dateMMDD->getTimestamp() - $today->getTimestamp());
            $diffDDMM = abs($dateDDMM->getTimestamp() - $today->getTimestamp());
            
            // Prefer the date closer to today
            if ($diffDDMM < $diffMMDD) {
                // DD/MM/YYYY is closer to today
                return sprintf('%04d-%02d-%02d', $year, $second, $first);
            } else {
                // MM/DD/YYYY is closer to today (or equal)
                return sprintf('%04d-%02d-%02d', $year, $first, $second);
            }
        }
    }
    
    // Check for relative dates
    if (strpos($lower, 'today') !== false) {
        return date('Y-m-d');
    }
    if (strpos($lower, 'tomorrow') !== false) {
        $tomorrow = new DateTime('tomorrow');
        return $tomorrow->format('Y-m-d');
    }
    if (preg_match('/(\d+)\s*(day|days)\s*(from now|later)/', $lower, $matches)) {
        $days = (int)$matches[1];
        $date = new DateTime("+{$days} days");
        return $date->format('Y-m-d');
    }
    
    return null;
}

/**
 * Extract time preference from message
 * Handles formats like: "1 pm", "1:30 pm", "13:00", etc.
 */
function extractTime($message) {
    $lower = strtolower($message);
    
    // Check for time with AM/PM: "1 pm", "1:30 pm", "11:00 am", etc.
    if (preg_match('/(\d{1,2})(?::(\d{2}))?\s*(am|pm)/i', $message, $matches)) {
        $hour = (int)$matches[1];
        $minute = isset($matches[2]) ? (int)$matches[2] : 0;
        $ampm = strtolower($matches[3]);
        
        // Convert to 24-hour format
        if ($ampm === 'pm' && $hour != 12) {
            $hour += 12;
        } elseif ($ampm === 'am' && $hour == 12) {
            $hour = 0;
        }
        
        // Validate time
        if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
            return sprintf('%02d:%02d:00', $hour, $minute);
        }
    }
    
    // Check for 24-hour format: "13:00", "09:30", etc.
    if (preg_match('/(\d{1,2}):(\d{2})(?:\s|$)/', $message, $matches)) {
        $hour = (int)$matches[1];
        $minute = (int)$matches[2];
        if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
            return sprintf('%02d:%02d:00', $hour, $minute);
        }
    }
    
    return null;
}

/**
 * Extract paper type from message (for Registrar Office)
 */
function extractPaperType($message) {
    $lower = strtolower($message);
    
    // Paper type keywords
    $paperTypes = [
        'Transcript of Records' => ['transcript', 'tor', 'record', 'records'],
        'Diploma' => ['diploma', 'degree'],
        'Certificate' => ['certificate', 'cert'],
        'Others' => ['other', 'others', 'miscellaneous']
    ];
    
    foreach ($paperTypes as $paperType => $keywords) {
        foreach ($keywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $message)) {
                return $paperType;
            }
        }
    }
    
    return null;
}

/**
 * Get processing days for paper type
 */
function getProcessingDays($paperType) {
    $processingDays = [
        'Transcript of Records' => 7,
        'Diploma' => 5,
        'Certificate' => 3,
        'Others' => 2
    ];
    
    return $processingDays[$paperType] ?? null;
}

/**
 * Calculate expected release date based on appointment date and processing days
 * Excludes weekends (business days only)
 */
function calculateReleaseDate($appointmentDate, $processingDays) {
    if (!$appointmentDate || !$processingDays || $processingDays <= 0) {
        return null;
    }
    
    try {
        $date = new DateTime($appointmentDate);
        $daysAdded = 0;
        
        while ($daysAdded < $processingDays) {
            $date->modify('+1 day');
            $dayOfWeek = (int)$date->format('w'); // 0 = Sunday, 6 = Saturday
            // Skip weekends
            if ($dayOfWeek != 0 && $dayOfWeek != 6) {
                $daysAdded++;
            }
        }
        
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        error_log('Error calculating release date: ' . $e->getMessage());
        return null;
    }
}

/**
 * Extract concern from message
 * Removes booking keywords, dates, times, and office names
 * Only returns a concern if it's clearly a meaningful concern, not just leftover text
 */
function extractConcern($message) {
    $originalMessage = $message;
    
    // Remove booking keywords
    $concern = preg_replace('/\b(book|schedule|appointment|reserve|for|with|at|on|help|me|can you|please|i want|i need|mag-book|gusto ko|pwedeng)\b/i', '', $message);
    
    // Remove date patterns (more comprehensive)
    $concern = preg_replace('/(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)\s+\d{1,2}(?:st|nd|rd|th)?,?\s*\d{4}/i', '', $concern);
    $concern = preg_replace('/(january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|may|jun|jul|aug|sep|sept|oct|nov|dec)\s+\d{1,2}(?:st|nd|rd|th)?/i', '', $concern);
    $concern = preg_replace('/\d{1,2}\/\d{1,2}\/\d{4}/', '', $concern);
    $concern = preg_replace('/\d{4}-\d{2}-\d{2}/', '', $concern); // YYYY-MM-DD format
    
    // Remove time patterns
    $concern = preg_replace('/\d{1,2}(?::\d{2})?\s*(am|pm)/i', '', $concern);
    $concern = preg_replace('/\d{1,2}:\d{2}(?::\d{2})?(?:\s|$)/', '', $concern);
    
    // Remove office names (common ones and variations)
    $concern = preg_replace('/\b(assessment|registrar|cashier|guidance|library|clinic|office|offices)\b/i', '', $concern);
    
    // Remove common question words that might be leftover
    $concern = preg_replace('/\b(what|when|where|who|why|how|which|is|are|was|were|do|does|did|will|would|can|could|should|may|might)\b/i', '', $concern);
    
    // Remove common prepositions and articles
    $concern = preg_replace('/\b(the|a|an|in|on|at|to|from|by|for|of|with|about|into|onto|upon)\b/i', '', $concern);
    
    $concern = preg_replace('/\s+/', ' ', $concern); // Collapse multiple spaces
    $concern = trim($concern);
    
    // Check if what's left looks like a real concern
    // It should have at least 10 characters and contain meaningful words
    if (strlen($concern) < 10) {
        return null;
    }
    
    // Check if it's just a question mark or very short fragments
    if (preg_match('/^[?.,!\-_\s]+$/', $concern)) {
        return null;
    }
    
    // Check if it contains at least one word that's 4+ characters (likely meaningful)
    if (!preg_match('/\b\w{4,}\b/', $concern)) {
        return null;
    }
    
    // If it's just a date fragment or time fragment, return null
    if (preg_match('/^\d+$/', $concern) || preg_match('/^(st|nd|rd|th)$/i', $concern)) {
        return null;
    }
    
    return $concern;
}

/**
 * Call Groq API (FREE - No payment required!)
 * Get your free API key at: https://console.groq.com/
 */
function callGroqAPI($apiKey, $message, $conversationHistory, $offices, $userAppointments = []) {
    // Build system prompt with office information
    $officeList = array_map(function($office) {
        $info = $office['office_name'];
        if (!empty($office['location'])) {
            $info .= " (Location: {$office['location']})";
        }
        if (!empty($office['description'])) {
            $info .= " - {$office['description']}";
        }
        return $info;
    }, $offices);
    
    $contextInfo = '';
    if (!empty($userAppointments)) {
        $contextInfo = "\n\nUser's Recent Appointments:\n";
        foreach (array_slice($userAppointments, 0, 3) as $apt) {
            $contextInfo .= "- {$apt['office_name']} on {$apt['appointment_date']} at {$apt['appointment_time']} (Status: {$apt['status']})\n";
        }
        $contextInfo .= "\nUse this context to provide personalized suggestions. For example, if they've booked with Registrar's Office before, you can reference that.";
    }

    $systemPrompt = "You are a helpful AI assistant for Cor Jesu College's appointment booking system. Your role is to help students and faculty book appointments with the correct office.

Available Offices:
" . implode("\n", $officeList) . $contextInfo . "

IMPORTANT GUIDELINES:
- LANGUAGE: Respond in the same language the user uses (English or Filipino/Tagalog). Match their language preference naturally.
- CONCISENESS: Keep responses brief and actionable (under 100 words when possible). Get to the point quickly.
- EMOJIS: Use emojis sparingly - only for greetings (👋) or status indicators (✅ ⏳). Avoid overusing them.
- ACCURACY: Never make up office information. Only use information provided in the office list above.
- BOOKING: Always offer to book appointments automatically when relevant. Say things like 'I can book this for you!' or 'Would you like me to schedule it?'
- CONTEXT: Use user's appointment history to provide personalized suggestions. Reference past bookings naturally.
- UNCERTAINTY: If you don't know something, admit it and suggest contacting the office directly or checking the website.
- TONE: Be friendly, professional, and helpful. Sound like a knowledgeable college staff member.
- OFFICE HOURS: Appointments are typically available from 9:00 AM to 4:00 PM, Monday to Friday.
- AUTO-BOOKING: When users request booking, offer to do it automatically. Use phrases like 'book me with [Office]' or 'schedule an appointment'.

RESPONSE FORMAT:
- Use markdown formatting for emphasis (**bold** for office names, important info)
- Use bullet points (•) for lists
- Keep paragraphs short (2-3 sentences max)
- End with a clear call-to-action when appropriate

Current date: " . date('F j, Y') . "
Current time: " . date('g:i A') . "

Remember: You're here to help users navigate the appointment system efficiently and book appointments quickly.";

    // Build conversation messages
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];

    // Add conversation history (last 10 messages to avoid token limits)
    $recentHistory = array_slice($conversationHistory, -10);
    foreach ($recentHistory as $msg) {
        $messages[] = [
            'role' => $msg['role'] ?? 'user',
            'content' => $msg['content'] ?? ''
        ];
    }

    // Add current message
    $messages[] = ['role' => 'user', 'content' => $message];

    // Call Groq API (FREE tier - very fast!)
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'llama-3.1-8b-instant', // Fast and free model
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 500
        ])
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception('API request failed: ' . $curlError);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? 'Unknown API error';
        throw new Exception('Groq API error: ' . $errorMsg);
    }

    $data = json_decode($response, true);
    
    if (!isset($data['choices'][0]['message']['content'])) {
        throw new Exception('Invalid response from AI service');
    }

    return trim($data['choices'][0]['message']['content']);
}

/**
 * Generate PHP-based intelligent response (FREE - No API needed!)
 * This works even without any API key
 * Now includes context-aware suggestions!
 */
function generatePHPResponse($message, $offices, $userAppointments = []) {
    $lowerMessage = strtolower($message);
    $response = '';

    // Detect language preference
    $isFilipino = detectFilipinoLanguage($message);

    // Context-aware greeting
    if (preg_match('/\b(hi|hello|hey|halo|kamusta|kumusta|magandang|good morning|good afternoon|good evening|mabuhay)\b/i', $message)) {
        if ($isFilipino) {
            $response = "Kumusta! 👋 Ako ang AI assistant para sa appointment booking system ng Cor Jesu College.\n\n";
            
            // Add context if user has appointments
            if (!empty($userAppointments)) {
                $recentApt = $userAppointments[0];
                $response .= "Nakita ko na mayroon kang appointment sa **{$recentApt['office_name']}** sa {$recentApt['appointment_date']} ng {$recentApt['appointment_time']}.\n\n";
            }
            
            $response .= "Maaari kitang tulungan sa:\n";
            $response .= "• Paghanap ng tamang opisina para sa iyong concern\n";
            $response .= "• Pag-unawa sa proseso ng pag-book ng appointment\n";
            $response .= "• **Awtomatikong pag-book ng appointment para sa iyo**\n";
            $response .= "• Pagsagot sa mga tanong tungkol sa serbisyo ng opisina\n\n";
            $response .= "Ano ang maitutulong ko sa iyo ngayon?";
        } else {
        $response = "Hello! 👋 I'm your AI assistant for Cor Jesu College's appointment booking system.\n\n";
        
        // Add context if user has appointments
        if (!empty($userAppointments)) {
            $recentApt = $userAppointments[0];
                $response .= "I see you have an upcoming appointment with **{$recentApt['office_name']}** on {$recentApt['appointment_date']} at {$recentApt['appointment_time']}.\n\n";
        }
        
        $response .= "I can help you:\n";
        $response .= "• Find the right office for your concern\n";
        $response .= "• Understand the appointment booking process\n";
        $response .= "• **Book appointments automatically for you**\n";
        $response .= "• Answer questions about office services\n\n";
        $response .= "What can I help you with today?";
        }
    }
    // Check appointment status
    elseif (preg_match('/\b(status|check|my appointment|upcoming|pending|approved)\b/i', $message)) {
        if (!empty($userAppointments)) {
            $response = "Here are your recent appointments:\n\n";
            foreach (array_slice($userAppointments, 0, 5) as $apt) {
                $statusEmoji = $apt['status'] === 'approved' ? '✅' : ($apt['status'] === 'pending' ? '⏳' : '📋');
                $response .= "{$statusEmoji} **{$apt['office_name']}**\n";
                $response .= "   Date: {$apt['appointment_date']}\n";
                $response .= "   Time: {$apt['appointment_time']}\n";
                $response .= "   Status: {$apt['status']}\n\n";
            }
            $response .= "You can track all your appointments in the dashboard!";
        } else {
            $response = "You don't have any appointments yet. Would you like me to help you book one?";
        }
    }
    // Office-specific queries - Transcripts/Records
    elseif (preg_match('/\b(transcript|diploma|certificate|records|grades|tog|tor|form 137|form 138|tor|tog|transkrip|rekord|marka)\b/i', $message)) {
        $office = findOffice($offices, ['registrar', 'record', 'transcript']);
        if ($office) {
            if ($isFilipino) {
                $response = "Para sa **transcripts, diplomas, certificates, at student records**, dapat kang mag-book sa **{$office['office_name']}**.\n\n";
                
                // Context: Check if user has booked with this office before
                foreach ($userAppointments as $apt) {
                    if (stripos($apt['office_name'], 'registrar') !== false) {
                        $response .= "Nakita ko na nag-book ka na sa {$apt['office_name']} dati! ";
                        break;
                    }
                }
                
                if (!empty($office['description'])) {
                    $response .= "Sila ang nagha-handle ng: {$office['description']}.\n\n";
                }
                $response .= "**Gusto mo bang tulungan kitang mag-book?** Sabihin mo lang 'book mo ako sa Registrar' o 'mag-schedule ka ng appointment' at hahanapin ko ang pinakamagandang available time!";
            } else {
            $response = "For **transcripts, diplomas, certificates, and student records**, you should book with the **{$office['office_name']}**.\n\n";
            
            // Context: Check if user has booked with this office before
            foreach ($userAppointments as $apt) {
                if (stripos($apt['office_name'], 'registrar') !== false) {
                    $response .= "I see you've booked with {$apt['office_name']} before! ";
                    break;
                }
            }
            
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with Registrar' or 'schedule an appointment' and I'll find the best available time!";
            }
        } else {
            if ($isFilipino) {
                $response = "Para sa transcripts, diplomas, certificates, at student records, kailangan mong makipag-ugnayan sa **Registrar's Office**.\n\n";
                $response .= "**Pwede kitang tulungan mag-book!** Sabihin mo lang 'book mo ako sa Registrar' at awtomatiko kong i-schedule!";
        } else {
            $response = "For transcripts, diplomas, certificates, and student records, you'll need to contact the **Registrar's Office**.\n\n";
            $response .= "**I can book this for you!** Just say 'book me with Registrar' and I'll schedule it automatically.";
            }
        }
    }
    // Payment/Tuition
    elseif (preg_match('/\b(payment|pay|tuition|fee|financial|money|cash|installment|bayad|tuition|bayaran|pera|halaga)\b/i', $message)) {
        $office = findOffice($offices, ['cashier', 'accounting', 'finance', 'payment', 'tuition']);
        if ($office) {
            if ($isFilipino) {
                $response = "Para sa **payment, tuition, at financial matters**, dapat kang mag-book sa **{$office['office_name']}**.\n\n";
                if (!empty($office['description'])) {
                    $response .= "Sila ang nagha-handle ng: {$office['description']}.\n\n";
                }
                $response .= "**Gusto mo bang tulungan kitang mag-book?** Sabihin mo lang 'book mo ako sa {$office['office_name']}' at hahanapin ko ang pinakamagandang available time!";
            } else {
            $response = "For **payment, tuition, and financial matters**, you should book with the **{$office['office_name']}**.\n\n";
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with {$office['office_name']}' and I'll find the best available time!";
            }
        } else {
            if ($isFilipino) {
                $response = "Para sa payment at tuition concerns, kailangan mong makipag-ugnayan sa **Cashier's Office** o **Accounting Office**.\n\n";
                $response .= "**Pwede kitang tulungan mag-book!** Sabihin mo lang kung aling opisina at awtomatiko kong i-schedule!";
        } else {
            $response = "For payment and tuition concerns, you'll need to contact the **Cashier's Office** or **Accounting Office**.\n\n";
            $response .= "**I can book this for you!** Just tell me which office and I'll schedule it automatically.";
            }
        }
    }
    // Guidance/Counseling
    elseif (preg_match('/\b(guidance|counseling|counselor|mental health|stress|anxiety|depression|advice|help)\b/i', $message)) {
        $office = findOffice($offices, ['guidance', 'counseling', 'counselor']);
        if ($office) {
            $response = "For **guidance and counseling services**, you should book with the **{$office['office_name']}**.\n\n";
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with Guidance' and I'll schedule it automatically.";
        } else {
            $response = "For guidance and counseling, you'll need to contact the **Guidance Office**.\n\n";
            $response .= "**I can book this for you!** Just say 'book me with Guidance' and I'll schedule it automatically.";
        }
    }
    // Library
    elseif (preg_match('/\b(library|book|borrow|return|research|study|libro)\b/i', $message)) {
        $office = findOffice($offices, ['library']);
        if ($office) {
            $response = "For **library services** (borrowing books, research assistance, study spaces), you should book with the **{$office['office_name']}**.\n\n";
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with Library' and I'll schedule it automatically.";
        } else {
            $response = "For library services, you'll need to contact the **Library**.\n\n";
            $response .= "**I can book this for you!** Just say 'book me with Library' and I'll schedule it automatically.";
        }
    }
    // Clinic/Health
    elseif (preg_match('/\b(clinic|health|medical|doctor|nurse|sick|illness|medicine|gamot)\b/i', $message)) {
        $office = findOffice($offices, ['clinic', 'health', 'medical']);
        if ($office) {
            $response = "For **health and medical concerns**, you should book with the **{$office['office_name']}**.\n\n";
            if (!empty($office['description'])) {
                $response .= "They handle: {$office['description']}.\n\n";
            }
            $response .= "**Would you like me to book an appointment for you?** Just say 'book me with Clinic' and I'll schedule it automatically.";
        } else {
            $response = "For health and medical concerns, you'll need to contact the **Clinic**.\n\n";
            $response .= "**I can book this for you!** Just say 'book me with Clinic' and I'll schedule it automatically.";
        }
    }
    // Office hours
    elseif (preg_match('/\b(hours|time|when|open|close|available|schedule|oras|bukas|sarado|kailan)\b/i', $message)) {
        if ($isFilipino) {
            $response = "Ang office hours ay karaniwang **9:00 AM hanggang 4:00 PM**, Lunes hanggang Biyernes.\n\n";
            $response .= "**Pwede kitang tulungan mag-book ng appointment ngayon!** Sabihin mo lang kung aling opisina ang kailangan mo at hahanapin ko ang pinakamagandang available time.";
        } else {
        $response = "Office hours are typically **9:00 AM to 4:00 PM**, Monday to Friday.\n\n";
        $response .= "**I can book an appointment for you right now!** Just tell me which office you need and I'll find the best available time.";
        }
    }
    // How to book
    elseif (preg_match('/\b(how|book|appointment|schedule|reserve|process|steps|paano|mag-book|pano|pwedeng|gusto ko)\b/i', $message)) {
        if ($isFilipino) {
            $response = "Narito kung paano mag-book ng appointment:\n\n";
            $response .= "**Option 1: Pwede kitang tulungan mag-book!** 🚀\n";
            $response .= "Sabihin mo lang: 'Book mo ako sa [Office Name]' o 'Mag-schedule ka ng appointment sa [Office Name]'\n";
            $response .= "Awtomatiko kong hahanapin ang pinakamagandang available time at i-book para sa iyo!\n\n";
            $response .= "**Option 2: Manual booking**\n";
            $response .= "1. Pumili ng opisina mula sa menu\n";
            $response .= "2. Pumili ng gusto mong petsa\n";
            $response .= "3. Pumili ng available time slot\n";
            $response .= "4. Ilagay ang iyong concern at i-submit\n\n";
            $response .= "Alin ang gusto mo?";
        } else {
        $response = "Here's how to book an appointment:\n\n";
        $response .= "**Option 1: I can book for you!** 🚀\n";
        $response .= "Just say: 'Book me with [Office Name]' or 'Schedule an appointment with [Office Name]'\n";
        $response .= "I'll automatically find the best available time and book it for you!\n\n";
        $response .= "**Option 2: Manual booking**\n";
        $response .= "1. Select the office from the menu\n";
        $response .= "2. Choose your preferred date\n";
        $response .= "3. Select an available time slot\n";
        $response .= "4. Fill in your concern and submit\n\n";
        $response .= "Which would you prefer?";
        }
    }
    // List offices
    elseif (preg_match('/\b(office|offices|list|what|which|available|options|ano|saan)\b/i', $message)) {
        if (count($offices) > 0) {
            $response = "Here are the available offices:\n\n";
            foreach ($offices as $office) {
                $response .= "• **{$office['office_name']}**";
                if (!empty($office['location'])) {
                    $response .= " - {$office['location']}";
                }
                $response .= "\n";
            }
            $response .= "\n**I can book any of these for you!** Just say 'book me with [Office Name]' and I'll schedule it automatically.";
        } else {
            $response = "Please select an office from the dropdown menu to see available options.\n\n";
            $response .= "**Or tell me which office you need and I'll book it for you!**";
        }
    }
    // Default response
    else {
        if ($isFilipino) {
            $response = "Naiintindihan ko na nagtatanong ka tungkol sa: \"" . htmlspecialchars($message) . "\"\n\n";
            $response .= "Maaari kitang tulungan sa:\n";
            $response .= "• Paghanap ng tamang opisina para sa iyong concern\n";
            $response .= "• Pag-unawa sa proseso ng pag-book ng appointment\n";
            $response .= "• **Awtomatikong pag-book ng appointment para sa iyo**\n";
            $response .= "• Impormasyon tungkol sa office hours at serbisyo\n\n";
            $response .= "**Subukan mong sabihin:**\n";
            $response .= "• 'Book mo ako sa Registrar' → Awtomatiko kong i-schedule\n";
            $response .= "• 'Kailangan ko ng transcript' → Ituturo kita at tutulungan mag-book\n";
            $response .= "• 'Tingnan ang aking appointments' → Ipapakita ko ang booking history mo";
        } else {
        $response = "I understand you're asking about: \"" . htmlspecialchars($message) . "\"\n\n";
        $response .= "I can help you with:\n";
        $response .= "• Finding the right office for your concern\n";
        $response .= "• Understanding how to book appointments\n";
        $response .= "• **Booking appointments automatically for you**\n";
        $response .= "• Information about office hours and services\n\n";
        $response .= "**Try saying:**\n";
        $response .= "• 'Book me with Registrar' → I'll schedule it automatically\n";
        $response .= "• 'I need my transcript' → I'll direct you and offer to book\n";
        $response .= "• 'Check my appointments' → I'll show your booking history";
        }
    }

    return $response;
}

/**
 * Detect if message is in Filipino/Tagalog
 */
function detectFilipinoLanguage($message) {
    $filipinoKeywords = [
        'kamusta', 'kumusta', 'magandang', 'salamat', 'paano', 'pano', 'saan', 'ano', 
        'gusto', 'pwede', 'pwedeng', 'kailangan', 'tulong', 'tulungan', 'mag-book',
        'mag-schedule', 'appointment', 'opisina', 'bayad', 'tuition', 'transkrip',
        'rekord', 'marka', 'bukas', 'sarado', 'oras', 'kailan', 'ngayon', 'bukas',
        'mabuhay', 'opo', 'hindi', 'oo', 'sige', 'okay', 'ok', 'libro', 'gamot'
    ];
    
    $lowerMessage = strtolower($message);
    $filipinoCount = 0;
    $totalWords = str_word_count($message);
    
    foreach ($filipinoKeywords as $keyword) {
        if (strpos($lowerMessage, $keyword) !== false) {
            $filipinoCount++;
        }
    }
    
    // If more than 20% of detected keywords are Filipino, respond in Filipino
    return $filipinoCount > 0 && ($totalWords < 5 || ($filipinoCount / max($totalWords, 1)) > 0.2);
}

/**
 * Helper function to find office by keywords
 */
function findOffice($offices, $keywords) {
    foreach ($offices as $office) {
        $officeName = strtolower($office['office_name']);
        $description = strtolower($office['description'] ?? '');
        
        foreach ($keywords as $keyword) {
            if (strpos($officeName, $keyword) !== false || strpos($description, $keyword) !== false) {
                return $office;
            }
        }
    }
    return null;
}

/**
 * Log AI query for analytics (non-blocking, fails silently)
 */
function logAIQuery($pdo, $user_id, $message, $response, $source, $isBookingRequest = false) {
    try {
        // Create table if it doesn't exist (one-time setup)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS public.ai_chat_logs (
                log_id BIGSERIAL PRIMARY KEY,
                user_id BIGINT REFERENCES public.users(user_id) ON DELETE SET NULL,
                user_message TEXT NOT NULL,
                ai_response TEXT,
                response_source VARCHAR(50) NOT NULL,
                is_booking_request BOOLEAN DEFAULT FALSE,
                response_length INTEGER,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
            )
        ");
        
        // Insert log entry
        $stmt = $pdo->prepare("
            INSERT INTO public.ai_chat_logs 
            (user_id, user_message, ai_response, response_source, is_booking_request, response_length)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $responseLength = strlen($response);
        $userId = $user_id > 0 ? $user_id : null;
        
        $stmt->execute([
            $userId,
            substr($message, 0, 500), // Limit message length
            substr($response, 0, 2000), // Limit response length
            $source, // Parameter name is $source, not $responseSource
            $isBookingRequest ? 't' : 'f',
            $responseLength
        ]);
    } catch (Exception $e) {
        // Silently fail - don't break the chat if logging fails
        error_log('AI query logging failed: ' . $e->getMessage());
    }
}
