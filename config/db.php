<?php
// Centralized Supabase Postgres connection using PDO
// Configure via .env (see config/env.example) or system environment variables:
// SUPABASE_DB_HOST, SUPABASE_DB_PORT, SUPABASE_DB_NAME, SUPABASE_DB_USER, SUPABASE_DB_PASSWORD

require_once __DIR__ . '/env.php';
loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');

// Use a static variable to cache the connection
static $pdo = null;

// Function to check if connection is still alive
function isConnectionAlive($pdo) {
	if (!$pdo instanceof PDO) {
		return false;
	}
	try {
		// Try a simple query to check if connection is alive
		$pdo->query('SELECT 1');
		return true;
	} catch (PDOException $e) {
		error_log("Connection check failed: " . $e->getMessage());
		return false;
	}
}

// Check if we have a cached connection and if it's still alive
if ($pdo instanceof PDO && isConnectionAlive($pdo)) {
	return $pdo;
}

// Connection doesn't exist or is dead, create a new one
$pdo = null;

$host = getenv('SUPABASE_DB_HOST') ?: 'YOUR_SUPABASE_HOST';
$port = getenv('SUPABASE_DB_PORT') ?: '5432';
$db   = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$user = getenv('SUPABASE_DB_USER') ?: 'postgres';
$pass = getenv('SUPABASE_DB_PASSWORD') ?: 'YOUR_SUPABASE_PASSWORD';
$sslMode = getenv('SUPABASE_DB_SSLMODE') ?: 'require';

// Use connection pooling (pooler) for better performance if available
$poolerPort = getenv('SUPABASE_DB_POOLER_PORT') ?: $port;
// Add connection timeout and connect_timeout to DSN for PostgreSQL
$dsn  = "pgsql:host={$host};port={$poolerPort};dbname={$db};sslmode={$sslMode};connect_timeout=30";

$options = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
	// Increase connection timeout to 30 seconds for slow connections
	PDO::ATTR_TIMEOUT => 30,
	// Remove persistent connections - they can cause issues with connection pooling
	// PDO::ATTR_PERSISTENT => true,
];

// Retry logic for connection with longer delays
$maxRetries = 5;
$retryDelay = 2; // seconds - start with 2 seconds

for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
	try {
		// Set a longer timeout for this specific connection attempt
		$context = stream_context_create([
			'socket' => [
				'timeout' => 30
			]
		]);
		
		$pdo = new PDO($dsn, $user, $pass, $options);
		
		// Set statement timeout to prevent long-running queries (30 seconds)
		// Wrap in try-catch in case connection closes immediately
		try {
			$pdo->exec("SET statement_timeout = '30s'");
			// Also set connection timeout at database level
			$pdo->exec("SET connect_timeout = '30'");
		} catch (PDOException $e) {
			error_log("Warning: Could not set timeout settings: " . $e->getMessage());
			// Continue anyway - this is not critical
		}
		
		// Test the connection with a simple query
		$pdo->query('SELECT 1');
		
		return $pdo;
	} catch (PDOException $e) {
		$errorMsg = $e->getMessage();
		error_log("Database connection attempt {$attempt}/{$maxRetries} failed: " . $errorMsg);
		
		if ($attempt < $maxRetries) {
			// Wait before retrying with exponential backoff
			$waitTime = $retryDelay * $attempt; // Progressive delay: 2s, 4s, 6s, 8s
			error_log("Retrying in {$waitTime} seconds...");
			sleep($waitTime);
		} else {
			// Last attempt failed - provide helpful error message
			error_log("Database connection failed after {$maxRetries} attempts");
			error_log("Final error: " . $errorMsg);
			
			// Throw a more user-friendly error
			throw new PDOException(
				"Unable to connect to database. Please check your database configuration or try again later. " .
				"Error: " . $errorMsg,
				$e->getCode(),
				$e
			);
		}
	}
}
