<?php
/**
 * Session Configuration
 * Ensures sessions persist across page refreshes and browser restarts
 */

// Only configure if session hasn't been started yet
if (session_status() === PHP_SESSION_NONE) {
	// Set session cookie parameters for persistence
	// Cookie lifetime: 30 days (in seconds)
	$cookieLifetime = 30 * 24 * 60 * 60; // 30 days
	
	// Session data lifetime on server: 30 days
	ini_set('session.gc_maxlifetime', $cookieLifetime);
	
	// Cookie parameters
	$cookieParams = [
		'lifetime' => $cookieLifetime, // Cookie expires in 30 days
		'path' => '/', // Available across entire site
		'domain' => '', // Use current domain
		'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // HTTPS only if available
		'httponly' => true, // Prevent JavaScript access (security)
		'samesite' => 'Lax' // CSRF protection
	];
	
	session_set_cookie_params(
		$cookieParams['lifetime'],
		$cookieParams['path'],
		$cookieParams['domain'],
		$cookieParams['secure'],
		$cookieParams['httponly']
	);
	
	// Set SameSite attribute (PHP 7.3+)
	if (PHP_VERSION_ID >= 70300) {
		ini_set('session.cookie_samesite', $cookieParams['samesite']);
	}
	
	// Prevent session ID regeneration on every request (helps with persistence)
	ini_set('session.use_strict_mode', 1);
	ini_set('session.use_only_cookies', 1);
	ini_set('session.cookie_lifetime', $cookieLifetime);
	
	// Start the session
	session_start();
	
	// Regenerate session ID periodically for security (every 30 minutes)
	// Only regenerate if session is older than 30 minutes
	if (!isset($_SESSION['created'])) {
		$_SESSION['created'] = time();
	} else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
		session_regenerate_id(true);
		$_SESSION['created'] = time();
	}
}
?>
