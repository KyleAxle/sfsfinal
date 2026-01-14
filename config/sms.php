<?php
// SMS API Configuration
// This file centralizes SMS API settings including header and footer templates

// Load environment variables if available
require_once __DIR__ . '/env.php';
loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');

// SMS API Configuration
define('SMS_API_TOKEN', getenv('SMS_API_TOKEN') ?: 'dadd747dcc588f49217a6d239d9ddf6a81a6e91b');
define('SMS_API_URL', 'https://sms.iprogtech.com/api/v1/sms_messages');

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
 * Format SMS message with header and footer template
 * 
 * @param string $messageContent The main message content
 * @return string Formatted message with header and footer
 */
function formatSMSMessage($messageContent) {
	$header = SMS_HEADER_TEXT;
	$footer = SMS_FOOTER_TEXT;
	
	// Build the message with template
	$formattedMessage = '';
	
	// Add header if set
	if (!empty($header)) {
		$formattedMessage .= $header . "\n\n";
	}
	
	// Add main message content
	$formattedMessage .= trim($messageContent);
	
	// Add footer if set
	if (!empty($footer)) {
		$formattedMessage .= "\n\n" . $footer;
	}
	
	return trim($formattedMessage);
}
