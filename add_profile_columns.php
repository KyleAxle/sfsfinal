<?php
/**
 * Add profile columns to users table if they don't exist
 * Run this once: php add_profile_columns.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "Adding profile columns to users table...\n\n";

$columns = [
    'middle_initial' => "VARCHAR(10)",
    'student_id' => "VARCHAR(50)",
    'age' => "INTEGER",
    'date_of_birth' => "DATE"
];

foreach ($columns as $columnName => $columnType) {
    try {
        // Check if column exists
        $check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'users' 
            AND column_name = ?
        ");
        $check->execute([$columnName]);
        $exists = (int)$check->fetchColumn() > 0;
        
        if ($exists) {
            echo "✓ Column '{$columnName}' already exists\n";
        } else {
            // Add column
            $pdo->exec("ALTER TABLE public.users ADD COLUMN {$columnName} {$columnType}");
            echo "✓ Added column '{$columnName}'\n";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⚠ Column '{$columnName}' already exists\n";
        } else {
            echo "✗ Error adding column '{$columnName}': " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Done! Profile columns are ready.\n";
echo "\nTo verify, run:\n";
echo "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users' ORDER BY ordinal_position;\n";

