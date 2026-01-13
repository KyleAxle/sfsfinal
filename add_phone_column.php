<?php
/**
 * Add phone column to users table if it doesn't exist
 * Run this once: php add_phone_column.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Adding phone column to users table...\n\n";

try {
    $pdo = require __DIR__ . '/config/db.php';
    echo "✅ Database connection successful\n\n";
    
    // Check if phone column exists
    $check = $pdo->prepare("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'phone'
    ");
    $check->execute();
    $exists = $check->fetch();
    
    if ($exists) {
        echo "✅ Phone column already exists in users table\n";
    } else {
        echo "📝 Phone column does not exist. Adding it now...\n";
        
        // Add phone column
        $pdo->exec("ALTER TABLE public.users ADD COLUMN phone VARCHAR(20)");
        echo "✅ Phone column added successfully!\n";
    }
    
    // Verify the column
    echo "\n📋 Verifying column structure:\n";
    $verify = $pdo->query("
        SELECT column_name, data_type, character_maximum_length, is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'public' 
        AND table_name = 'users'
        AND column_name = 'phone'
    ")->fetch(PDO::FETCH_ASSOC);
    
    if ($verify) {
        echo "   Column Name: {$verify['column_name']}\n";
        echo "   Data Type: {$verify['data_type']}\n";
        echo "   Max Length: {$verify['character_maximum_length']}\n";
        echo "   Nullable: {$verify['is_nullable']}\n";
    }
    
    // Check current users with/without phone
    echo "\n📊 Current phone number statistics:\n";
    $stats = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(phone) as users_with_phone,
            COUNT(*) - COUNT(phone) as users_without_phone
        FROM public.users
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "   Total users: {$stats['total_users']}\n";
    echo "   Users with phone: {$stats['users_with_phone']}\n";
    echo "   Users without phone: {$stats['users_without_phone']}\n";
    
    echo "\n✅ Done! Phone column is ready.\n";
    echo "\n💡 Users can now add their phone number during registration or in their profile.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

