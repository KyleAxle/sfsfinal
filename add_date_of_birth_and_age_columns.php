<?php
/**
 * Add date_of_birth and age columns to users table
 * Run this file once to add the columns to your database
 */

require_once __DIR__ . '/config/db.php';

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    echo "Adding date_of_birth and age columns to users table...\n";
    
    // Add date_of_birth column
    try {
        $pdo->exec("ALTER TABLE public.users ADD COLUMN IF NOT EXISTS date_of_birth DATE");
        echo "✓ Added date_of_birth column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ date_of_birth column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add age column
    try {
        $pdo->exec("ALTER TABLE public.users ADD COLUMN IF NOT EXISTS age INTEGER");
        echo "✓ Added age column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "✓ age column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Add comments
    try {
        $pdo->exec("COMMENT ON COLUMN public.users.date_of_birth IS 'User date of birth'");
        $pdo->exec("COMMENT ON COLUMN public.users.age IS 'User age in years'");
        echo "✓ Added column comments\n";
    } catch (PDOException $e) {
        echo "⚠ Could not add comments (this is optional): " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Successfully added date_of_birth and age columns to users table!\n";
    echo "You can now use these fields in registration and profile pages.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. Database connection is working\n";
    echo "2. You have permission to alter the users table\n";
    echo "3. The users table exists in the public schema\n";
    exit(1);
}
