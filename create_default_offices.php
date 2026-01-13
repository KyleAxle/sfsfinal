<?php
/**
 * Create default offices in the database
 * Run this once: php create_default_offices.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// Default offices matching the frontend mapping
$offices = [
    ['office_id' => 1, 'office_name' => 'Registrar Office', 'location' => 'Main Building', 'description' => 'Student records and transcripts'],
    ['office_id' => 2, 'office_name' => 'Cashier Office', 'location' => 'Main Building', 'description' => 'Payment and financial transactions'],
    ['office_id' => 3, 'office_name' => 'CCIS Office', 'location' => 'CCIS Building', 'description' => 'College of Computing and Information Sciences'],
    ['office_id' => 4, 'office_name' => 'Dean of CCIS', 'location' => 'CCIS Building', 'description' => 'Dean\'s Office for CCIS'],
    ['office_id' => 5, 'office_name' => 'Guidance Office', 'location' => 'Student Services Building', 'description' => 'Student counseling and guidance'],
    ['office_id' => 6, 'office_name' => 'Assessment Office', 'location' => 'Main Building', 'description' => 'Student assessment and evaluation'],
];

echo "Creating default offices...\n\n";

foreach ($offices as $office) {
    try {
        // Check if office already exists
        $check = $pdo->prepare("SELECT office_id FROM offices WHERE office_id = ? OR office_name = ?");
        $check->execute([$office['office_id'], $office['office_name']]);
        $existing = $check->fetch();
        
        if ($existing) {
            echo "✓ Office '{$office['office_name']}' already exists (ID: {$existing['office_id']})\n";
            continue;
        }
        
        // Insert office
        $stmt = $pdo->prepare("
            INSERT INTO offices (office_id, office_name, location, description) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $office['office_id'],
            $office['office_name'],
            $office['location'],
            $office['description']
        ]);
        
        echo "✓ Created office: {$office['office_name']} (ID: {$office['office_id']})\n";
        
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'duplicate key') !== false || strpos($e->getMessage(), 'unique constraint') !== false) {
            echo "⚠ Office '{$office['office_name']}' already exists\n";
        } else {
            echo "✗ Error creating office '{$office['office_name']}': " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Done! Offices are ready.\n";
echo "\nTo verify, run:\n";
echo "SELECT office_id, office_name FROM offices ORDER BY office_id;\n";

