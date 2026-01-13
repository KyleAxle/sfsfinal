<?php
// Centralized Supabase Postgres connection using PDO
// Configure via .env (see config/env.example) or system environment variables:
// SUPABASE_DB_HOST, SUPABASE_DB_PORT, SUPABASE_DB_NAME, SUPABASE_DB_USER, SUPABASE_DB_PASSWORD

require_once __DIR__ . '/env.php';
loadEnv(dirname(__DIR__) . '/.env');
loadEnv(__DIR__ . '/.env');

// Use a static variable to cache the connection
static $pdo = null;
if ($pdo instanceof PDO) {
	return $pdo;
}

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
	// Add connection timeout to fail fast (5 seconds)
	PDO::ATTR_TIMEOUT => 5,
	// Persistent connections (reuse connections when possible)
	PDO::ATTR_PERSISTENT => true,
];

try {
	$pdo = new PDO($dsn, $user, $pass, $options);
	// Set statement timeout to prevent long-running queries (5 seconds)
	$pdo->exec("SET statement_timeout = '5s'");
	return $pdo;
} catch (PDOException $e) {
	error_log("Database connection failed: " . $e->getMessage());
	throw $e;
}
