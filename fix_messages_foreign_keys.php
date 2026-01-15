<?php
/**
 * Fix messages table foreign key constraints
 * Changes ON DELETE SET NULL to ON DELETE CASCADE
 * Run: php fix_messages_foreign_keys.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Throwable $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

try {
    $pdo->beginTransaction();

    echo "Dropping existing foreign key constraints...\n";
    
    // Drop existing foreign key constraints (they may have different names)
    // First, find and drop all foreign keys on the messages table
    $stmt = $pdo->query("
        SELECT constraint_name 
        FROM information_schema.table_constraints 
        WHERE table_schema = 'public' 
        AND table_name = 'messages' 
        AND constraint_type = 'FOREIGN KEY'
    ");
    
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($foreignKeys as $constraintName) {
        try {
            $pdo->exec("ALTER TABLE public.messages DROP CONSTRAINT IF EXISTS " . $pdo->quote($constraintName));
            echo "Dropped constraint: $constraintName\n";
        } catch (PDOException $e) {
            echo "Note: Could not drop constraint $constraintName: " . $e->getMessage() . "\n";
        }
    }

    echo "\nRecreating foreign keys with CASCADE...\n";

    // Recreate foreign keys with CASCADE
    $pdo->exec("
        ALTER TABLE public.messages 
        ADD CONSTRAINT messages_sender_user_id_fkey 
        FOREIGN KEY (sender_user_id) 
        REFERENCES public.users (user_id) 
        ON DELETE CASCADE
    ");
    echo "Created: messages_sender_user_id_fkey\n";

    $pdo->exec("
        ALTER TABLE public.messages 
        ADD CONSTRAINT messages_sender_staff_id_fkey 
        FOREIGN KEY (sender_staff_id) 
        REFERENCES public.staff (staff_id) 
        ON DELETE CASCADE
    ");
    echo "Created: messages_sender_staff_id_fkey\n";

    $pdo->exec("
        ALTER TABLE public.messages 
        ADD CONSTRAINT messages_recipient_user_id_fkey 
        FOREIGN KEY (recipient_user_id) 
        REFERENCES public.users (user_id) 
        ON DELETE CASCADE
    ");
    echo "Created: messages_recipient_user_id_fkey\n";

    $pdo->exec("
        ALTER TABLE public.messages 
        ADD CONSTRAINT messages_recipient_staff_id_fkey 
        FOREIGN KEY (recipient_staff_id) 
        REFERENCES public.staff (staff_id) 
        ON DELETE CASCADE
    ");
    echo "Created: messages_recipient_staff_id_fkey\n";

    $pdo->commit();
    echo "\n✅ Foreign key constraints updated successfully!\n";
    echo "Messages will now be deleted when users/staff are deleted.\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
