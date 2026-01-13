<?php
/**
 * Creates/updates the staff table and seeds one staff user per office.
 *
 * Run: php create_staff_table.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = require __DIR__ . '/config/db.php';

$pdo->exec("
	create table if not exists public.staff (
		staff_id     bigserial primary key,
		email        varchar(100) not null unique,
		password     varchar(255) not null,
		office_id    bigint references public.offices (office_id) on delete set null,
		office_name  varchar(100),
		role         varchar(50) default 'staff',
		created_at   timestamptz not null default now(),
		updated_at   timestamptz not null default now()
	)
");

$offices = $pdo->query("select office_id, office_name from offices order by office_id")->fetchAll(PDO::FETCH_ASSOC);

if (!$offices) {
	echo "No offices found. Run create_default_offices.php first.\n";
	exit;
}

$passwordHash = password_hash('1234', PASSWORD_DEFAULT);
$inserted = 0;

$stmtCheck = $pdo->prepare("select staff_id from staff where email = ?");
$stmtInsert = $pdo->prepare("
	insert into staff (email, password, office_id, office_name, role)
	values (?, ?, ?, ?, 'staff')
");

foreach ($offices as $office) {
	$slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $office['office_name']));
	$email = $slug . ".staff@cjcsfs.local";

	$stmtCheck->execute([$email]);
	if ($stmtCheck->fetch()) {
		continue;
	}

	$stmtInsert->execute([
		$email,
		$passwordHash,
		$office['office_id'],
		$office['office_name']
	]);
	$inserted++;
}

echo "Staff table ready. Seeded {$inserted} staff account(s).\n";

