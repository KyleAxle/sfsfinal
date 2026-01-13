<?php
session_start();

try {
	$pdo = require __DIR__ . '/../config/db.php';
} catch (Throwable $e) {
	http_response_code(500);
	echo "Database connection failed.";
	exit;
}

// Ensure helper table for time configs exists
$pdo->exec("
	create table if not exists public.office_time_configs (
		office_id bigint primary key references public.offices (office_id) on delete cascade,
		opening_time time not null default '09:00',
		closing_time time not null default '16:00',
		slot_interval_minutes integer not null default 30,
		created_at timestamptz not null default now(),
		updated_at timestamptz not null default now()
	)
");

$pdo->exec("
	create or replace function public.touch_office_time_configs()
	returns trigger
	language plpgsql
	as $$
	begin
		new.updated_at = now();
		return new;
	end;
	$$;

	drop trigger if exists trg_office_time_configs on public.office_time_configs;
	create trigger trg_office_time_configs
	before update on public.office_time_configs
	for each row execute function public.touch_office_time_configs();
");

$errors = [];
$success = '';
$deleteError = '';
$editOfficeId = null;
$editOffice = null;

// Handle office edit request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit_office_id'])) {
	$editOfficeId = (int)$_GET['edit_office_id'];
	if ($editOfficeId > 0) {
		$editStmt = $pdo->prepare("
			select
				o.office_id,
				o.office_name,
				coalesce(o.location, '') as location,
				coalesce(o.description, '') as description,
				coalesce(cfg.opening_time, '09:00:00') as opening_time,
				coalesce(cfg.closing_time, '16:00:00') as closing_time,
				coalesce(cfg.slot_interval_minutes, 30) as slot_interval_minutes
			from public.offices o
			left join public.office_time_configs cfg on cfg.office_id = o.office_id
			where o.office_id = ?
		");
		$editStmt->execute([$editOfficeId]);
		$editOffice = $editStmt->fetch(PDO::FETCH_ASSOC);
	}
}

// Handle office update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_office_id'])) {
	$updateOfficeId = (int)$_POST['update_office_id'];
	$name = trim($_POST['office_name'] ?? '');
	$location = trim($_POST['location'] ?? '');
	$description = trim($_POST['description'] ?? '');
	$openingTime = parseTime($_POST['opening_time'] ?? '', 'Opening time', $errors);
	$closingTime = parseTime($_POST['closing_time'] ?? '', 'Closing time', $errors);
	$slotInterval = (int)($_POST['slot_interval'] ?? 30);

	if ($name === '') {
		$errors[] = 'Office name is required.';
	}
	if ($slotInterval < 15 || $slotInterval > 180) {
		$errors[] = 'Slot interval must be between 15 and 180 minutes.';
	}
	if ($openingTime && $closingTime && strtotime($closingTime) <= strtotime($openingTime)) {
		$errors[] = 'Closing time must be after opening time.';
	}

	if (!$errors && $updateOfficeId > 0) {
		try {
			$pdo->beginTransaction();

			// Check if name conflicts with another office
			$existsStmt = $pdo->prepare("select office_id from public.offices where lower(office_name) = lower(?) and office_id != ? limit 1");
			$existsStmt->execute([$name, $updateOfficeId]);
			if ($existsStmt->fetch()) {
				$errors[] = 'An office with this name already exists.';
				$pdo->rollBack();
			} else {
				// Update office
				$updateOffice = $pdo->prepare("update public.offices set office_name = ?, location = ?, description = ?, updated_at = now() where office_id = ?");
				$updateOffice->execute([$name, $location, $description, $updateOfficeId]);

				// Update or insert time config
				$checkConfig = $pdo->prepare("select office_id from public.office_time_configs where office_id = ?");
				$checkConfig->execute([$updateOfficeId]);
				if ($checkConfig->fetch()) {
					$updateConfig = $pdo->prepare("
						update public.office_time_configs 
						set opening_time = ?::time, closing_time = ?::time, slot_interval_minutes = ?, updated_at = now()
						where office_id = ?
					");
					$updateConfig->execute([$openingTime, $closingTime, $slotInterval, $updateOfficeId]);
				} else {
					$insertConfig = $pdo->prepare("
						insert into public.office_time_configs (office_id, opening_time, closing_time, slot_interval_minutes)
						values (?, ?::time, ?::time, ?)
					");
					$insertConfig->execute([$updateOfficeId, $openingTime, $closingTime, $slotInterval]);
				}

				$pdo->commit();
				$success = 'Office updated successfully.';
				// Redirect to clear the edit parameter
				header('Location: manage_offices.php');
				exit;
			}
		} catch (Throwable $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			$errors[] = 'Failed to update office. ' . $e->getMessage();
		}
	}
}

// Handle office deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_office_id'])) {
	$deleteOfficeId = (int)$_POST['delete_office_id'];
	if ($deleteOfficeId > 0) {
		try {
			$pdo->beginTransaction();

			// Check for dependencies
			$checkAppointments = $pdo->prepare("SELECT COUNT(*) FROM appointment_offices WHERE office_id = ?");
			$checkAppointments->execute([$deleteOfficeId]);
			$appointmentCount = (int)$checkAppointments->fetchColumn();

			$checkStaff = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE office_id = ?");
			$checkStaff->execute([$deleteOfficeId]);
			$staffCount = (int)$checkStaff->fetchColumn();

			$checkBlocks = $pdo->prepare("SELECT COUNT(*) FROM office_blocked_slots WHERE office_id = ?");
			$checkBlocks->execute([$deleteOfficeId]);
			$blockCount = (int)$checkBlocks->fetchColumn();

			if ($appointmentCount > 0 || $staffCount > 0 || $blockCount > 0) {
				$pdo->rollBack();
				$deleteError = "Cannot delete office: It has {$appointmentCount} appointment(s), {$staffCount} staff member(s), and {$blockCount} blocked slot(s). Please remove these first.";
			} else {
				// Safe to delete (cascade will handle office_time_configs)
				$deleteStmt = $pdo->prepare("DELETE FROM public.offices WHERE office_id = ?");
				$deleteStmt->execute([$deleteOfficeId]);
				$pdo->commit();
				$success = 'Office deleted successfully.';
			}
		} catch (Throwable $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			$deleteError = 'Failed to delete office: ' . $e->getMessage();
		}
	}
}

function parseTime(string $value, string $fieldName, array &$errors): ?string {
	$trimmed = trim($value);
	if ($trimmed === '') {
		$errors[] = "$fieldName is required.";
		return null;
	}
	$dt = DateTime::createFromFormat('H:i', $trimmed);
	if (!$dt) {
		$errors[] = "$fieldName must be in HH:MM format (24-hour).";
		return null;
	}
	return $dt->format('H:i');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_office_id'])) {
	$name = trim($_POST['office_name'] ?? '');
	$location = trim($_POST['location'] ?? '');
	$description = trim($_POST['description'] ?? '');
	$openingTime = parseTime($_POST['opening_time'] ?? '', 'Opening time', $errors);
	$closingTime = parseTime($_POST['closing_time'] ?? '', 'Closing time', $errors);
	$slotInterval = (int)($_POST['slot_interval'] ?? 30);

	if ($name === '') {
		$errors[] = 'Office name is required.';
	}
	if ($slotInterval < 15 || $slotInterval > 180) {
		$errors[] = 'Slot interval must be between 15 and 180 minutes.';
	}
	if ($openingTime && $closingTime && strtotime($closingTime) <= strtotime($openingTime)) {
		$errors[] = 'Closing time must be after opening time.';
	}

		if (!$errors) {
		try {
			$pdo->beginTransaction();

			$existsStmt = $pdo->prepare("select office_id from public.offices where lower(office_name) = lower(?) limit 1");
			$existsStmt->execute([$name]);
			if ($existsStmt->fetch()) {
				$errors[] = 'An office with this name already exists.';
			} else {
				// Ensure the sequence is synced before inserting
				$pdo->exec("
					SELECT setval(
						pg_get_serial_sequence('public.offices', 'office_id'),
						COALESCE((SELECT MAX(office_id) FROM public.offices), 0) + 1,
						false
					)
				");
				
				$insertOffice = $pdo->prepare("insert into public.offices (office_name, location, description) values (?, ?, ?) returning office_id");
				$insertOffice->execute([$name, $location, $description]);
				$newOfficeId = (int)$insertOffice->fetchColumn();

				$insertConfig = $pdo->prepare("
					insert into public.office_time_configs (office_id, opening_time, closing_time, slot_interval_minutes)
					values (?, ?::time, ?::time, ?)
				");
				$insertConfig->execute([$newOfficeId, $openingTime, $closingTime, $slotInterval]);

				$pdo->commit();
				$success = 'Office created successfully.';
			}
		} catch (Throwable $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			$errors[] = 'Failed to create office. ' . $e->getMessage();
		}
	}
}

$officesStmt = $pdo->query("
	select
		o.office_id,
		o.office_name,
		coalesce(o.location, '') as location,
		coalesce(o.description, '') as description,
		coalesce(cfg.opening_time, '09:00:00') as opening_time,
		coalesce(cfg.closing_time, '16:00:00') as closing_time,
		coalesce(cfg.slot_interval_minutes, 30) as slot_interval_minutes
	from public.offices o
	left join public.office_time_configs cfg on cfg.office_id = o.office_id
	order by lower(o.office_name)
");
$offices = $officesStmt->fetchAll(PDO::FETCH_ASSOC);

function formatTimeLabel(string $time): string {
	return DateTime::createFromFormat('H:i:s', $time)?->format('g:i A') ?? $time;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Admin • Manage Offices</title>
	<link rel="stylesheet" href="../assets/css/staff_dashboard.css">
	<style>
		body {
			overflow: hidden;
			height: 100vh;
			padding: 0;
			margin: 0;
		}
		.staff-shell {
			max-width: 100%;
			overflow: hidden;
			height: 100vh;
			border-radius: 0;
			position: relative;
		}
		.staff-sidebar {
			position: fixed;
			left: 0;
			top: 0;
			width: 260px;
			min-height: 100vh;
			padding: 20px;
			overflow-y: auto;
			overflow-x: hidden;
			z-index: 10;
			display: flex;
			flex-direction: column;
		}
		.staff-sidebar::-webkit-scrollbar {
			width: 6px;
		}
		.staff-sidebar::-webkit-scrollbar-track {
			background: rgba(0, 0, 0, 0.1);
		}
		.staff-sidebar::-webkit-scrollbar-thumb {
			background: rgba(255, 255, 255, 0.3);
			border-radius: 3px;
		}
		.staff-main {
			overflow-y: auto;
			overflow-x: auto;
			min-width: 0;
			width: calc(100% - var(--sidebar-width));
			flex: 1;
			height: 100vh;
			margin-left: var(--sidebar-width);
		}
		.staff-main::-webkit-scrollbar {
			width: 8px;
		}
		.staff-main::-webkit-scrollbar-track {
			background: #f1f5f9;
		}
		.staff-main::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 4px;
		}
		.staff-main::-webkit-scrollbar-thumb:hover {
			background: #94a3b8;
		}
		.block-form label {
			font-size: 0.9rem;
			color: var(--muted);
			font-weight: 600;
			margin-bottom: 6px;
			display: block;
		}
		.block-form input,
		.block-form select,
		.block-form textarea {
			width: 100%;
			padding: 12px 16px;
			border-radius: 14px;
			border: 1px solid #e5e7eb;
			background: #f9fafb;
			margin-top: 6px;
			margin-bottom: 12px;
			font-family: inherit;
		}
		.block-form button[type="submit"] {
			border: none;
			border-radius: 999px;
			background: var(--primary);
			color: #fff;
			padding: 12px 24px;
			font-weight: 600;
			cursor: pointer;
		}
		.office-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
			gap: 20px;
		}
		.office-card {
			border: 1px solid #f1f5f9;
			border-radius: 18px;
			padding: 20px;
			display: flex;
			flex-direction: column;
			gap: 12px;
			background: #fff;
			box-shadow: 0 20px 45px rgba(15, 23, 42, 0.05);
		}
		.office-card h4 {
			margin: 0;
			color: var(--text);
		}
		.badge-time {
			background: #eef2ff;
			color: #3730a3;
			border-radius: 999px;
			padding: 6px 14px;
			font-size: 0.85rem;
			font-weight: 600;
		}
		.btn-delete {
			border: none;
			background: transparent;
			color: #dc2626;
			font-weight: 600;
			cursor: pointer;
			padding: 8px 16px;
			border-radius: 999px;
			font-size: 0.85rem;
		}
		.btn-delete:hover {
			background: #fee2e2;
		}
		.btn-edit {
			border: none;
			background: transparent;
			color: var(--primary);
			font-weight: 600;
			cursor: pointer;
			padding: 8px 16px;
			border-radius: 999px;
			font-size: 0.85rem;
			text-decoration: none;
			display: inline-block;
		}
		.btn-edit:hover {
			background: rgba(125, 0, 0, 0.1);
		}
		.office-actions {
			display: flex;
			gap: 8px;
			align-items: center;
		}
		.staff-sidebar img {
			width: 110px;
			height: 110px;
			object-fit: contain;
			margin-bottom: 30px;
		}
		.staff-sidebar nav {
			display: flex;
			flex-direction: column;
			gap: 20px;
			flex: 1;
		}
		.staff-sidebar nav a {
			color: #fff;
			text-decoration: none;
			padding: 12px 16px;
			border-radius: 30px;
			font-weight: 600;
			font-size: 1rem;
			text-align: center;
			transition: all 0.2s;
		}
		.staff-sidebar nav a:hover,
		.staff-sidebar nav a.active {
			background: #fff;
			color: #7d0000;
		}
		.logout-btn {
			margin-top: auto;
			background: #fff;
			color: #7d0000;
			border: none;
			border-radius: 24px;
			padding: 10px 20px;
			cursor: pointer;
			font-weight: 600;
			font-size: 1rem;
			width: 100%;
		}
		.logout-btn:hover {
			background: #f9fafb;
		}
	</style>
</head>
<body>
	<div class="staff-shell">
		<aside class="staff-sidebar">
			<img src="../img/cjclogo.png" alt="CJC Logo">
			<nav>
				<?php
				$currentPage = basename($_SERVER['PHP_SELF']);
				$isAppointments = ($currentPage === 'admin_dashboard.php');
				$isManageOffices = ($currentPage === 'manage_offices.php');
				?>
				<a href="admin_dashboard.php" <?= $isAppointments ? 'class="active"' : '' ?>>Appointments</a>
				<a href="manage_offices.php" <?= $isManageOffices ? 'class="active"' : '' ?>>Manage Offices</a>
			</nav>
			<button class="logout-btn" id="adminLogoutBtn">Sign Out</button>
		</aside>

		<main class="staff-main">
			<header class="staff-header">
				<div>
					<p style="letter-spacing:0.25em;text-transform:uppercase;font-weight:600;margin-bottom:6px;">Admin Access</p>
					<h1>MANAGE OFFICES</h1>
				</div>
				<div style="text-align:right;">
					<p style="font-weight:600;">Welcome, Admin</p>
					<p style="font-size:0.9rem;opacity:0.85;">Create and manage service offices</p>
				</div>
			</header>

			<section class="panel">
				<h2><?= $editOffice ? 'Edit Office' : 'Create New Office'; ?></h2>

				<?php if ($errors): ?>
					<div class="alert alert-danger" style="background:#fee2e2;color:#b91c1c;padding:16px;border-radius:14px;margin-bottom:20px;">
						<ul style="margin:0;padding-left:20px;">
							<?php foreach ($errors as $error): ?>
								<li><?= htmlspecialchars($error) ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php elseif ($success): ?>
					<div class="alert alert-success" style="background:#d1fae5;color:#065f46;padding:16px;border-radius:14px;margin-bottom:20px;"><?= htmlspecialchars($success) ?></div>
				<?php endif; ?>
				<?php if ($deleteError): ?>
					<div class="alert alert-danger" style="background:#fee2e2;color:#b91c1c;padding:16px;border-radius:14px;margin-bottom:20px;"><?= htmlspecialchars($deleteError) ?></div>
				<?php endif; ?>

				<form method="POST" class="block-form" id="officeForm">
					<?php if ($editOffice): ?>
						<input type="hidden" name="update_office_id" value="<?= (int)$editOffice['office_id']; ?>">
					<?php endif; ?>
					<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:16px;">
						<div>
							<label>Office Name</label>
							<input type="text" name="office_name" placeholder="e.g., Records Office" value="<?= htmlspecialchars($editOffice['office_name'] ?? ''); ?>" required>
						</div>
						<div>
							<label>Location / Building</label>
							<input type="text" name="location" placeholder="e.g., Main Building, 2nd Floor" value="<?= htmlspecialchars($editOffice['location'] ?? ''); ?>">
						</div>
					</div>
					<div style="margin-bottom:16px;">
						<label>Description</label>
						<textarea name="description" rows="3" placeholder="What services does this office handle?"><?= htmlspecialchars($editOffice['description'] ?? ''); ?></textarea>
					</div>
					<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px;">
						<div>
							<label>Opening Time</label>
							<?php
							$openingTime = $editOffice ? substr($editOffice['opening_time'], 0, 5) : '09:00';
							?>
							<input type="time" name="opening_time" value="<?= htmlspecialchars($openingTime); ?>" required>
						</div>
						<div>
							<label>Closing Time</label>
							<?php
							$closingTime = $editOffice ? substr($editOffice['closing_time'], 0, 5) : '16:00';
							?>
							<input type="time" name="closing_time" value="<?= htmlspecialchars($closingTime); ?>" required>
						</div>
						<div>
							<label>Slot Interval (minutes)</label>
							<input type="number" name="slot_interval" min="15" max="180" step="5" value="<?= (int)($editOffice['slot_interval_minutes'] ?? 30); ?>" required>
						</div>
					</div>
					<div style="display:flex;justify-content:flex-end;gap:12px;">
						<?php if ($editOffice): ?>
							<a href="manage_offices.php" class="btn-cancel" style="padding:12px 24px;border-radius:999px;background:#f3f4f6;color:var(--text);text-decoration:none;font-weight:600;display:inline-block;">Cancel</a>
						<?php endif; ?>
						<button type="submit"><?= $editOffice ? 'Update Office' : 'Save Office'; ?></button>
					</div>
				</form>
			</section>

			<section class="panel">
				<h2>Existing Offices</h2>
				<?php if (!$offices): ?>
					<p style="color:var(--muted);">No offices found yet.</p>
				<?php else: ?>
					<div class="office-grid">
						<?php foreach ($offices as $office): ?>
							<div class="office-card">
								<div style="display:flex;justify-content:space-between;align-items:center;">
									<h4><?= htmlspecialchars($office['office_name']); ?></h4>
									<span class="badge-time">ID <?= (int)$office['office_id']; ?></span>
								</div>
								<p style="color:var(--muted);margin:0;"><?= htmlspecialchars($office['location'] ?: 'No location specified'); ?></p>
								<p style="min-height:48px;margin:0;"><?= htmlspecialchars($office['description'] ?: 'No description provided.'); ?></p>
								<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
									<span class="badge-time">Open: <?= htmlspecialchars(formatTimeLabel($office['opening_time'])); ?></span>
									<span class="badge-time">Close: <?= htmlspecialchars(formatTimeLabel($office['closing_time'])); ?></span>
									<span class="badge-time"><?= (int)$office['slot_interval_minutes']; ?> min slots</span>
								</div>
								<div style="margin-top:12px;padding-top:12px;border-top:1px solid #f1f5f9;">
									<div class="office-actions">
										<a href="manage_offices.php?edit_office_id=<?= (int)$office['office_id']; ?>" class="btn-edit">Edit Office</a>
										<form method="POST" onsubmit="return confirm('Are you sure you want to delete this office? This action cannot be undone.');" style="display:inline;">
											<input type="hidden" name="delete_office_id" value="<?= (int)$office['office_id']; ?>">
											<button type="submit" class="btn-delete">Delete Office</button>
										</form>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		</main>
	</div>

	<script>
		const logoutBtn = document.getElementById('adminLogoutBtn');
		if (logoutBtn) {
			logoutBtn.addEventListener('click', async () => {
				try {
					await fetch('../logout.php', { method: 'POST', credentials: 'same-origin' });
				} catch (err) {
					console.warn('Logout request failed, continuing to redirect.', err);
				} finally {
					window.location.href = 'admin_login.php';
				}
			});
		}

		// Scroll to form when editing
		<?php if ($editOffice): ?>
		const officeForm = document.getElementById('officeForm');
		if (officeForm) {
			setTimeout(() => {
				officeForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}, 100);
		}
		<?php endif; ?>
	</script>
</body>
</html>
