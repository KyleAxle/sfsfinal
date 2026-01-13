<?php
/**
 * Add profile_picture column to users table if it doesn't exist
 * Run this once: php add_profile_picture_column.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "Adding profile_picture column to users table...\n\n";

try {
    // Check if column exists
    $check = $pdo->prepare("
        SELECT COUNT(*) 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'profile_picture'
    ");
    $check->execute();
    $exists = (int)$check->fetchColumn() > 0;
    
    if ($exists) {
        echo "✓ Column 'profile_picture' already exists\n";
    } else {
        // Add column
        $pdo->exec("ALTER TABLE public.users ADD COLUMN profile_picture VARCHAR(255)");
        echo "✓ Added column 'profile_picture'\n";
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "⚠ Column 'profile_picture' already exists\n";
    } else {
        echo "✗ Error adding column 'profile_picture': " . $e->getMessage() . "\n";
    }
}

// Create uploads directory
$uploadDir = __DIR__ . '/uploads/profile_pictures/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    echo "✓ Created uploads directory: {$uploadDir}\n";
} else {
    echo "✓ Uploads directory already exists\n";
}

echo "\n✅ Done! Profile picture column is ready.\n";
echo "\nTo verify, run:\n";
echo "SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'profile_picture';\n";

