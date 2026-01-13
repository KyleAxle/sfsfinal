-- Add profile columns to users table
-- Run this in Supabase SQL Editor if the columns don't exist

-- Add middle_initial column
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'middle_initial'
    ) THEN
        ALTER TABLE public.users ADD COLUMN middle_initial VARCHAR(10);
    END IF;
END $$;

-- Add student_id column
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'student_id'
    ) THEN
        ALTER TABLE public.users ADD COLUMN student_id VARCHAR(50);
    END IF;
END $$;

-- Add age column
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'age'
    ) THEN
        ALTER TABLE public.users ADD COLUMN age INTEGER;
    END IF;
END $$;

-- Add date_of_birth column
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'users' 
        AND column_name = 'date_of_birth'
    ) THEN
        ALTER TABLE public.users ADD COLUMN date_of_birth DATE;
    END IF;
END $$;

-- Verify columns were added
SELECT column_name, data_type, is_nullable
FROM information_schema.columns
WHERE table_schema = 'public' 
AND table_name = 'users'
AND column_name IN ('middle_initial', 'student_id', 'age', 'date_of_birth')
ORDER BY column_name;

