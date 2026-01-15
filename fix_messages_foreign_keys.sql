-- Fix messages table foreign key constraints
-- This changes ON DELETE SET NULL to ON DELETE CASCADE
-- to prevent constraint violations when users/staff are deleted

-- Drop existing foreign key constraints
ALTER TABLE public.messages 
DROP CONSTRAINT IF EXISTS messages_sender_user_id_fkey;

ALTER TABLE public.messages 
DROP CONSTRAINT IF EXISTS messages_sender_staff_id_fkey;

ALTER TABLE public.messages 
DROP CONSTRAINT IF EXISTS messages_recipient_user_id_fkey;

ALTER TABLE public.messages 
DROP CONSTRAINT IF EXISTS messages_recipient_staff_id_fkey;

-- Recreate foreign keys with CASCADE instead of SET NULL
ALTER TABLE public.messages 
ADD CONSTRAINT messages_sender_user_id_fkey 
FOREIGN KEY (sender_user_id) 
REFERENCES public.users (user_id) 
ON DELETE CASCADE;

ALTER TABLE public.messages 
ADD CONSTRAINT messages_sender_staff_id_fkey 
FOREIGN KEY (sender_staff_id) 
REFERENCES public.staff (staff_id) 
ON DELETE CASCADE;

ALTER TABLE public.messages 
ADD CONSTRAINT messages_recipient_user_id_fkey 
FOREIGN KEY (recipient_user_id) 
REFERENCES public.users (user_id) 
ON DELETE CASCADE;

ALTER TABLE public.messages 
ADD CONSTRAINT messages_recipient_staff_id_fkey 
FOREIGN KEY (recipient_staff_id) 
REFERENCES public.staff (staff_id) 
ON DELETE CASCADE;
