-- Add profile_picture column to users table
-- Run this in Supabase SQL Editor if the column doesn't exist

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'profile_picture'
    ) THEN
        ALTER TABLE public.users ADD COLUMN profile_picture VARCHAR(255);
    END IF;
END $$;

-- Verify column was added
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_schema = 'public' 
AND table_name = 'users'
AND column_name = 'profile_picture';

