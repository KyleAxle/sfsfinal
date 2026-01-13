<?php
/**
 * Helper script to ensure office_blocked_slots table exists in the database.
 * Run via: php create_blocked_slots_table.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
	$pdo = require __DIR__ . '/config/db.php';
} catch (Throwable $e) {
	die("Database connection failed: " . $e->getMessage() . PHP_EOL);
}

$sql = "
	create table if not exists public.office_blocked_slots (
		block_id   bigserial primary key,
		office_id  bigint not null references public.offices (office_id) on delete cascade,
		block_date date not null,
		start_time time not null,
		end_time   time not null,
		reason     text,
		created_by bigint references public.staff (staff_id) on delete set null,
		created_at timestamptz not null default now()
	);
";

$pdo->exec($sql);

echo "office_blocked_slots table is ready." . PHP_EOL;

