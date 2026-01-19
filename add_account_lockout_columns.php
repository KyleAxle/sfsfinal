<?php
/**
 * Add account lockout columns to users, staff, and admins tables
 * Run this once to add account lockout support
 */

require_once __DIR__ . '/config/db.php';

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    echo "Adding account lockout columns...\n\n";
    
    $tables = ['users', 'staff', 'admins'];
    $columns = [
        'failed_login_attempts' => 'INTEGER DEFAULT 0',
        'account_locked_until' => 'TIMESTAMPTZ',
        'last_failed_login_at' => 'TIMESTAMPTZ'
    ];
    
    foreach ($tables as $table) {
        echo "Processing table: {$table}...\n";
        
        foreach ($columns as $columnName => $columnType) {
            // Check if column exists
            $check = $pdo->prepare("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_schema = 'public' 
                AND table_name = ? 
                AND column_name = ?
            ");
            $check->execute([$table, $columnName]);
            $exists = $check->fetch();
            
            if ($exists) {
                echo "  ✓ Column '{$columnName}' already exists, skipping...\n";
            } else {
                // Add the column
                $pdo->exec("ALTER TABLE public.{$table} ADD COLUMN {$columnName} {$columnType}");
                echo "  ✓ Added column '{$columnName}'\n";
            }
        }
        
        echo "\n";
    }
    
    echo "✅ All account lockout columns added successfully!\n";
    echo "Account lockout feature is now enabled.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
