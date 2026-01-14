-- Add date_of_birth and age columns to users table
-- Run this in your Supabase SQL Editor

-- Add date_of_birth column (DATE type)
ALTER TABLE public.users 
ADD COLUMN IF NOT EXISTS date_of_birth DATE;

-- Add age column (INTEGER type)
ALTER TABLE public.users 
ADD COLUMN IF NOT EXISTS age INTEGER;

-- Add comment to columns for documentation
COMMENT ON COLUMN public.users.date_of_birth IS 'User date of birth';
COMMENT ON COLUMN public.users.age IS 'User age in years';
