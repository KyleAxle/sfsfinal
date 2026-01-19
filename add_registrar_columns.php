<?php
/**
 * Add missing columns for Registrar Office appointments
 * Adds: paper_type, processing_days, release_date to appointments table
 */

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    echo "Adding missing columns to appointments table...\n\n";
    
    // Check if columns exist and add them if they don't
    $columns = [
        'paper_type' => 'VARCHAR(100)',
        'processing_days' => 'INTEGER',
        'release_date' => 'DATE'
    ];
    
    foreach ($columns as $columnName => $columnType) {
        // Check if column exists
        $check = $pdo->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'appointments' 
            AND column_name = ?
        ");
        $check->execute([$columnName]);
        $exists = $check->fetch();
        
        if ($exists) {
            echo "✓ Column '{$columnName}' already exists, skipping...\n";
        } else {
            // Add the column
            $pdo->exec("ALTER TABLE public.appointments ADD COLUMN {$columnName} {$columnType}");
            echo "✓ Added column '{$columnName}'\n";
        }
    }
    
    echo "\n✅ All columns added successfully!\n";
    echo "You can now book appointments at the Registrar Office.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
