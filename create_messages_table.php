<?php
/**
 * Creates the messages table for general chat between users and staff.
 * Run: php create_messages_table.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = require __DIR__ . '/config/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS public.messages (
            message_id       BIGSERIAL PRIMARY KEY,
            sender_type      VARCHAR(20) NOT NULL CHECK (sender_type IN ('user', 'staff')),
            sender_user_id   BIGINT REFERENCES public.users (user_id) ON DELETE SET NULL,
            sender_staff_id  BIGINT REFERENCES public.staff (staff_id) ON DELETE SET NULL,
            recipient_type   VARCHAR(20) NOT NULL CHECK (recipient_type IN ('user', 'staff')),
            recipient_user_id BIGINT REFERENCES public.users (user_id) ON DELETE SET NULL,
            recipient_staff_id BIGINT REFERENCES public.staff (staff_id) ON DELETE SET NULL,
            message          TEXT NOT NULL,
            is_read          BOOLEAN NOT NULL DEFAULT FALSE,
            created_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            CONSTRAINT messages_sender_check
                CHECK (
                    (sender_type = 'user' AND sender_user_id IS NOT NULL AND sender_staff_id IS NULL)
                    OR (sender_type = 'staff' AND sender_staff_id IS NOT NULL AND sender_user_id IS NULL)
                ),
            CONSTRAINT messages_recipient_check
                CHECK (
                    (recipient_type = 'user' AND recipient_user_id IS NOT NULL AND recipient_staff_id IS NULL)
                    OR (recipient_type = 'staff' AND recipient_staff_id IS NOT NULL AND recipient_user_id IS NULL)
                ),
            CONSTRAINT messages_not_self
                CHECK (
                    NOT (sender_type = recipient_type AND sender_user_id = recipient_user_id)
                    AND NOT (sender_type = recipient_type AND sender_staff_id = recipient_staff_id)
                )
        )
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_messages_sender_user 
        ON public.messages (sender_user_id, created_at DESC)
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_messages_sender_staff 
        ON public.messages (sender_staff_id, created_at DESC)
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_messages_recipient_user 
        ON public.messages (recipient_user_id, created_at DESC)
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_messages_recipient_staff 
        ON public.messages (recipient_staff_id, created_at DESC)
    ");

    $pdo->exec("
        CREATE INDEX IF NOT EXISTS idx_messages_conversation 
        ON public.messages (
            LEAST(
                COALESCE(sender_user_id, sender_staff_id),
                COALESCE(recipient_user_id, recipient_staff_id)
            ),
            GREATEST(
                COALESCE(sender_user_id, sender_staff_id),
                COALESCE(recipient_user_id, recipient_staff_id)
            ),
            sender_type,
            recipient_type,
            created_at DESC
        )
    ");

    echo "Messages table created successfully!\n";
    echo "Indexes created for optimal query performance.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

