-- Performance Optimization: Database Indexes
-- Run this in your Supabase SQL Editor to improve query performance
-- These indexes will significantly speed up message, appointment, and conversation queries

-- ============================================
-- MESSAGES TABLE INDEXES
-- ============================================

-- Index for finding messages by sender
CREATE INDEX IF NOT EXISTS idx_messages_sender 
ON public.messages(sender_type, sender_user_id, sender_staff_id);

-- Index for finding messages by recipient
CREATE INDEX IF NOT EXISTS idx_messages_recipient 
ON public.messages(recipient_type, recipient_user_id, recipient_staff_id);

-- Index for sorting messages by creation time (most recent first)
CREATE INDEX IF NOT EXISTS idx_messages_created_at 
ON public.messages(created_at DESC);

-- Index for finding unread messages
CREATE INDEX IF NOT EXISTS idx_messages_is_read 
ON public.messages(is_read) 
WHERE is_read = FALSE;

-- Composite index for conversation queries (sender + recipient + time)
CREATE INDEX IF NOT EXISTS idx_messages_conversation 
ON public.messages(sender_type, recipient_type, created_at DESC);

-- ============================================
-- APPOINTMENTS TABLE INDEXES
-- ============================================

-- Index for user appointments sorted by date
CREATE INDEX IF NOT EXISTS idx_appointments_user_date 
ON public.appointments(user_id, appointment_date DESC);

-- Index for appointment status queries
CREATE INDEX IF NOT EXISTS idx_appointments_status 
ON public.appointments(status);

-- Index for appointment date queries
CREATE INDEX IF NOT EXISTS idx_appointments_date 
ON public.appointments(appointment_date, appointment_time);

-- ============================================
-- APPOINTMENT_OFFICES TABLE INDEXES
-- ============================================

-- Index for finding appointments by office
CREATE INDEX IF NOT EXISTS idx_appointment_offices_office 
ON public.appointment_offices(office_id);

-- Composite index for office + appointment queries
CREATE INDEX IF NOT EXISTS idx_appointment_offices_composite 
ON public.appointment_offices(office_id, appointment_id);

-- ============================================
-- USERS TABLE INDEXES
-- ============================================

-- Index for email lookups (login queries)
CREATE INDEX IF NOT EXISTS idx_users_email 
ON public.users(LOWER(email));

-- ============================================
-- STAFF TABLE INDEXES
-- ============================================

-- Index for staff email lookups
CREATE INDEX IF NOT EXISTS idx_staff_email 
ON public.staff(LOWER(email));

-- Index for staff office lookups
CREATE INDEX IF NOT EXISTS idx_staff_office 
ON public.staff(office_id);

-- ============================================
-- FEEDBACK TABLE INDEXES
-- ============================================

-- Index for finding feedback by appointment
CREATE INDEX IF NOT EXISTS idx_feedback_appointment 
ON public.feedback(appointment_id);

-- ============================================
-- NOTES
-- ============================================
-- After running these indexes:
-- 1. Query performance should improve significantly
-- 2. Database will use these indexes automatically
-- 3. Indexes are automatically maintained by PostgreSQL
-- 4. Monitor query performance in Supabase dashboard
