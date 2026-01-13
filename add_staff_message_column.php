<?php
/**
 * Add staff_message column to appointment_offices table
 * This allows staff to send messages to users when updating appointment status
 */

try {
    $pdo = require __DIR__ . '/config/db.php';
    
    // Check if column already exists
    $checkStmt = $pdo->query("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'appointment_offices' 
        AND column_name = 'staff_message'
    ");
    
    if ($checkStmt->fetch()) {
        echo "Column 'staff_message' already exists in appointment_offices table.\n";
        exit(0);
    }
    
    // Add the column
    $pdo->exec("
        ALTER TABLE public.appointment_offices 
        ADD COLUMN staff_message TEXT
    ");
    
    echo "Successfully added 'staff_message' column to appointment_offices table.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

