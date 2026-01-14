<?php
// SMS API Configuration
// This file centralizes SMS API settings including header and footer templates

// Load environment variables if available
require_once __DIR__ . '/env.php';
loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');

// SMS API Configuration
define('SMS_API_TOKEN', getenv('SMS_API_TOKEN') ?: '331a374186640304a6ffa890f60f3f5ec550d702');
define('SMS_API_URL', 'https://www.iprogsms.com/api/v1/sms_messages');

// SMS Template Configuration
// These will be automatically prepended (header) and appended (footer) to all SMS messages
// Configure these based on your iprogsms.com account settings

// Header Text (REQUIRED) - Must be related to your project title or school name
// Example: "SFS Student Management" or "CJC Appointment System"
define('SMS_HEADER_TEXT', getenv('SMS_HEADER_TEXT') ?: 'SFS Appointment System');

// Footer Text (OPTIONAL) - Will be appended to all messages
// Example: "Thank you for using CJC SFS" or leave empty
define('SMS_FOOTER_TEXT', getenv('SMS_FOOTER_TEXT') ?: '');

/**
 * Format SMS message
 * 
 * Note: iprogsms.com automatically adds header and footer from your account settings.
 * We don't add them here to avoid duplication.
 * 
 * @param string $messageContent The main message content
 * @return string Formatted message (header/footer added automatically by iprogsms.com)
 */
function formatSMSMessage($messageContent) {
	// iprogsms.com automatically adds header/footer from account settings
	// So we just return the message content as-is to avoid duplication
	return trim($messageContent);
}
