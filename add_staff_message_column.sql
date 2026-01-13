-- Add staff_message column to appointment_offices table
-- This allows staff to send messages to users when updating appointment status

ALTER TABLE public.appointment_offices 
ADD COLUMN IF NOT EXISTS staff_message TEXT;

