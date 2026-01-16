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
$dsn  = "pgsql:host={$host};port={$poolerPort};dbname={$db};sslmode={$sslMode}";

$options = [
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
	PDO::ATTR_EMULATE_PREPARES => false,
	// Add connection timeout to fail fast (10 seconds - increased for stability)
	PDO::ATTR_TIMEOUT => 10,
	// Remove persistent connections - they can cause issues with connection pooling
	// PDO::ATTR_PERSISTENT => true,
];

// Retry logic for connection
$maxRetries = 3;
$retryDelay = 1; // seconds

for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
	try {
		$pdo = new PDO($dsn, $user, $pass, $options);
		
		// Set statement timeout to prevent long-running queries (10 seconds - increased)
		// Wrap in try-catch in case connection closes immediately
		try {
			$pdo->exec("SET statement_timeout = '10s'");
		} catch (PDOException $e) {
			error_log("Warning: Could not set statement_timeout: " . $e->getMessage());
			// Continue anyway - this is not critical
		}
		
		return $pdo;
	} catch (PDOException $e) {
		error_log("Database connection attempt {$attempt}/{$maxRetries} failed: " . $e->getMessage());
		
		if ($attempt < $maxRetries) {
			// Wait before retrying
			sleep($retryDelay);
			$retryDelay *= 2; // Exponential backoff
		} else {
			// Last attempt failed
			error_log("Database connection failed after {$maxRetries} attempts");
			throw $e;
		}
	}
}
