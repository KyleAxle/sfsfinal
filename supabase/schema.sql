-- Supabase schema for the appointment management system
-- Run this script after creating a new project/database in Supabase.

-- Enable helpful extensions
create extension if not exists "pgcrypto";
create extension if not exists "uuid-ossp";

-- Note: Using varchar for status fields to match code expectations (e.g., "Pending", "Completed")
-- If you prefer enums, you'll need to update the PHP code to use lowercase values

-- Core tables
create table if not exists public.admins (
  admin_id        bigserial primary key,
  name            varchar(100) not null,
  email           varchar(100) not null unique,
  password        varchar(255) not null,
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now()
);

create table if not exists public.users (
  user_id         bigserial primary key,
  first_name      varchar(100) not null,
  last_name       varchar(100) not null,
  email           varchar(100) not null unique,
  phone           varchar(20),
  password        varchar(255) not null,
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now()
);

create table if not exists public.offices (
  office_id           bigserial primary key,
  office_name         varchar(100) not null unique,
  location            varchar(150),
  description         text,
  created_by          bigint references public.admins (admin_id) on delete set null,
  created_at          timestamptz not null default now(),
  updated_at          timestamptz not null default now(),
  last_profiled_at    timestamptz,
  last_profiled_by    bigint references public.admins (admin_id) on delete set null
);

create table if not exists public.appointments (
  appointment_id   bigserial primary key,
  user_id          bigint references public.users (user_id) on delete cascade,
  first_name       varchar(100),
  last_name        varchar(100),
  email            varchar(100),
  appointment_date date not null,
  appointment_time time not null,
  office_id        bigint references public.offices (office_id) on delete set null,
  paper_type       varchar(100),
  processing_days  integer,
  release_date     date,
  concern          text,
  status           varchar(50) not null default 'Pending',
  created_at       timestamptz not null default now(),
  updated_at       timestamptz not null default now()
);

create table if not exists public.appointment_offices (
  appointment_id   bigint not null references public.appointments (appointment_id) on delete cascade,
  office_id        bigint not null references public.offices (office_id) on delete cascade,
  status           varchar(50) not null default 'pending',
  assigned_at      timestamptz not null default now(),
  updated_at       timestamptz not null default now(),
  primary key (appointment_id, office_id)
);

create table if not exists public.feedback (
  feedback_id      bigserial primary key,
  appointment_id   bigint references public.appointments (appointment_id) on delete cascade,
  rating           int check (rating between 1 and 5),
  comment          text,
  submitted_at     timestamptz not null default now()
);

-- Office profiling history (timestamp profiling per office)
create table if not exists public.office_profile_events (
  profile_event_id bigserial primary key,
  office_id        bigint not null references public.offices (office_id) on delete cascade,
  profiled_by      bigint references public.admins (admin_id) on delete set null,
  notes            text,
  profiled_at      timestamptz not null default now()
);

-- Automatically update office.last_profiled_at when new profile events are logged
create or replace function public.update_office_profile_timestamp()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  update public.offices
     set last_profiled_at = new.profiled_at,
         last_profiled_by = new.profiled_by,
         updated_at = now()
   where office_id = new.office_id;
  return new;
end;
$$;

drop trigger if exists trg_office_profile_timestamp on public.office_profile_events;
create trigger trg_office_profile_timestamp
after insert on public.office_profile_events
for each row
execute function public.update_office_profile_timestamp();

-- Updated_at maintenance triggers
create or replace function public.touch_updated_at()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

drop trigger if exists trg_admins_touch_updated_at on public.admins;
create trigger trg_admins_touch_updated_at
before update on public.admins
for each row
execute function public.touch_updated_at();

drop trigger if exists trg_users_touch_updated_at on public.users;
create trigger trg_users_touch_updated_at
before update on public.users
for each row
execute function public.touch_updated_at();

drop trigger if exists trg_offices_touch_updated_at on public.offices;
create trigger trg_offices_touch_updated_at
before update on public.offices
for each row
execute function public.touch_updated_at();

drop trigger if exists trg_appointments_touch_updated_at on public.appointments;
create trigger trg_appointments_touch_updated_at
before update on public.appointments
for each row
execute function public.touch_updated_at();

drop trigger if exists trg_appointment_offices_touch_updated_at on public.appointment_offices;
create trigger trg_appointment_offices_touch_updated_at
before update on public.appointment_offices
for each row
execute function public.touch_updated_at();

