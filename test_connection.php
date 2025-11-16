<?php
/**
 * Test Supabase connection
 * Run this after setting up .env file: php test_connection.php
 * Or open in browser: http://localhost/sfs/test_connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Supabase Connection Test</h2>";

// Check if .env exists
if (!file_exists(__DIR__ . '/.env')) {
    echo "<p style='color: red;'>❌ .env file not found!</p>";
    echo "<p>Run: <code>php create_env.php</code> to create it.</p>";
    exit;
}

try {
    echo "<p>Attempting to connect to Supabase...</p>";
    
    $pdo = require __DIR__ . '/config/db.php';
    
    echo "<p style='color: green;'>✅ Successfully connected to Supabase!</p>";
    
    // Test query - check users table
    echo "<h3>Database Tables Check:</h3>";
    echo "<ul>";
    
    $tables = ['users', 'admins', 'offices', 'appointments', 'appointment_offices', 'feedback'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $result = $stmt->fetch();
            $count = $result['count'] ?? 0;
            echo "<li>✅ Table '{$table}': {$count} records</li>";
        } catch (Exception $e) {
            echo "<li style='color: orange;'>⚠️  Table '{$table}': " . $e->getMessage() . "</li>";
        }
    }
    
    echo "</ul>";
    
    // Test insert/select
    echo "<h3>Connection Test:</h3>";
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetch();
    echo "<p>PostgreSQL Version: " . htmlspecialchars($version['version']) . "</p>";
    
    echo "<p style='color: green; font-weight: bold;'>🎉 Everything is working! Your project is ready to use Supabase.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Connection failed!</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    echo "<h3>Troubleshooting:</h3>";
    echo "<ul>";
    echo "<li>Check that your .env file exists and has the correct password</li>";
    echo "<li>Verify PHP PostgreSQL extension is enabled: <code>php -m | grep pdo_pgsql</code></li>";
    echo "<li>Make sure your Supabase project is active</li>";
    echo "<li>Check that your IP is allowed in Supabase (if using connection pooling restrictions)</li>";
    echo "</ul>";
    
    echo "<h3>Common Issues:</h3>";
    echo "<ul>";
    echo "<li><strong>Call to undefined function pg_connect():</strong> Enable PHP PostgreSQL extension</li>";
    echo "<li><strong>Connection refused:</strong> Check host and password in .env</li>";
    echo "<li><strong>SSL connection error:</strong> Make sure SUPABASE_DB_SSLMODE=require in .env</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

