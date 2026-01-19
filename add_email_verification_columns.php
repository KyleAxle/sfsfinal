<?php
/**
 * Add email verification columns to users table
 * Run this once to add email verification support
 */

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    echo "Adding email verification columns to users table...\n\n";
    
    // Check if columns exist and add them if they don't
    $columns = [
        'email_verified' => 'BOOLEAN DEFAULT FALSE',
        'email_verification_token' => 'VARCHAR(64)',
        'email_verification_sent_at' => 'TIMESTAMPTZ'
    ];
    
    foreach ($columns as $columnName => $columnType) {
        // Check if column exists
        $check = $pdo->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'users' 
            AND column_name = ?
        ");
        $check->execute([$columnName]);
        $exists = $check->fetch();
        
        if ($exists) {
            echo "✓ Column '{$columnName}' already exists, skipping...\n";
        } else {
            // Add the column
            $pdo->exec("ALTER TABLE public.users ADD COLUMN {$columnName} {$columnType}");
            echo "✓ Added column '{$columnName}'\n";
        }
    }
    
    // Create index on verification token for faster lookups
    try {
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_verification_token ON public.users(email_verification_token) WHERE email_verification_token IS NOT NULL");
        echo "✓ Created index on email_verification_token\n";
    } catch (PDOException $e) {
        echo "⚠ Could not create index (may already exist): " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ All columns added successfully!\n";
    echo "Email verification is now enabled.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
