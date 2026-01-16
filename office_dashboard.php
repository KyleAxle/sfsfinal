<?php
require_once __DIR__ . '/config/session.php';

// Determine office from query or session
$officeParam = isset($_GET['office']) ? strtolower(trim($_GET['office'])) : '';
if ($officeParam === '' && isset($_SESSION['office_name'])) {
	$officeParam = strtolower(trim($_SESSION['office_name']));
}
if ($officeParam === '') {
	http_response_code(400);
	echo "Missing office. Provide ?office={office_name}.";
	exit;
}

// Database connection
$pdo = require __DIR__ . '/config/db.php';

// Normalize and fetch the canonical office name from DB to avoid typos
$stmtOffice = $pdo->prepare("select office_id, office_name from offices where lower(office_name) = :name");
$stmtOffice->execute([':name' => $officeParam]);
$office = $stmtOffice->fetch();

if (!$office) {
	http_response_code(404);
	echo "Office not found.";
	exit;
}

$officeName = $office['office_name'];
$officeId = (int)$office['office_id'];

// Load appointments for the office with feedback
// The relationship is: feedback -> appointments -> appointment_offices -> offices
$sql = "
	select
		a.appointment_id,
		o.office_name,
		u.first_name,
		u.last_name,
		u.email,
		u.phone,
		a.appointment_date,
		a.appointment_time,
		a.concern,
		a.status,
		a.paper_type,
		a.processing_days,
		a.release_date,
		coalesce(f.rating, null) as rating,
		coalesce(f.comment, null) as feedback_comment,
		coalesce(f.submitted_at, null) as feedback_submitted_at
	from public.appointments a
	inner join public.appointment_offices ao on ao.appointment_id = a.appointment_id
	inner join public.users u on a.user_id = u.user_id
	inner join public.offices o on ao.office_id = o.office_id
	left join public.feedback f on f.appointment_id = a.appointment_id
	where ao.office_id = ?
	order by a.appointment_date desc, a.appointment_time desc
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$officeId]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Log first appointment with feedback (uncomment to debug)
// if (!empty($appointments)) {
// 	error_log("First appointment ID: " . $appointments[0]['appointment_id'] . ", Rating: " . var_export($appointments[0]['rating'], true));
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($officeName); ?> Dashboard</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
	<style>
		* {
			box-sizing: border-box;
		}
		:root {
			--primary: #7d0000;
			--sidebar-width: 260px;
			--text: #1f2933;
			--muted: #6b7280;
			--surface: #ffffff;
			--bg: #f5f6fb;
		}
		body {
			background: var(--bg);
			font-family: 'Poppins', 'Segoe UI', sans-serif;
			margin: 0;
			padding: 0;
			overflow: hidden;
			height: 100vh;
		}
		.sidebar {
			background: var(--primary);
			color: #fff;
			min-height: 100vh;
			padding: 20px;
			position: fixed;
			width: var(--sidebar-width);
			left: 0;
			top: 0;
			overflow-y: auto;
			overflow-x: hidden;
			z-index: 10;
			display: flex;
			flex-direction: column;
		}
		.sidebar img {
			width: 110px;
			height: 110px;
			object-fit: contain;
			margin-bottom: 30px;
		}
		.sidebar nav {
			display: flex;
			flex-direction: column;
			gap: 20px;
		}
		.sidebar nav a {
			color: #fff;
			text-decoration: none;
			padding: 12px 16px;
			border-radius: 30px;
			font-weight: 600;
			text-align: center;
			transition: all 0.2s;
		}
		.sidebar nav a:hover {
			background: #fff;
			color: var(--primary);
		}
		.logout-btn {
			margin-top: auto;
			background: #fff;
			color: var(--primary);
			border: none;
			border-radius: 24px;
			padding: 10px 20px;
			cursor: pointer;
			font-weight: 600;
			width: 100%;
		}
		.logout-btn:hover {
			background: #f9fafb;
		}
		.main-content {
			margin-left: var(--sidebar-width);
			padding: 30px;
			overflow-y: auto;
			overflow-x: auto;
			height: 100vh;
		}
		.main-content::-webkit-scrollbar {
			width: 8px;
		}
		.main-content::-webkit-scrollbar-track {
			background: #f1f5f9;
		}
		.main-content::-webkit-scrollbar-thumb {
			background: #cbd5e1;
			border-radius: 4px;
		}
		.main-content::-webkit-scrollbar-thumb:hover {
			background: #94a3b8;
		}
		.header {
			background: var(--primary);
			color: #fff;
			padding: 20px 28px;
			border-radius: 20px;
			margin-bottom: 24px;
			box-shadow: 0 15px 35px rgba(125, 0, 0, 0.25);
		}
		.header h1 {
			font-size: 2rem;
			margin: 0;
			letter-spacing: 0.2em;
		}
		.content-card {
			background: var(--surface);
			border-radius: 20px;
			padding: 24px;
			box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
		}
		.filter-section {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
			flex-wrap: wrap;
			gap: 12px;
		}
		.filter-section h2 {
			margin: 0;
			font-size: 1.5rem;
			font-weight: 600;
		}
		.table-responsive {
			border-radius: 12px;
			overflow: hidden;
		}
		.table {
			width: 100%;
			border-collapse: collapse;
			table-layout: fixed;
			min-width: 1400px;
		}
		.table thead {
			background: #f9fafb;
		}
		.table thead th {
			font-weight: 600;
			text-transform: uppercase;
			font-size: 0.85rem;
			letter-spacing: 0.08em;
			color: var(--muted);
			border-bottom: 2px solid #e5e7eb;
			padding: 12px 16px;
			text-align: left;
		}
		.table thead th:nth-child(1) { width: 140px; }
		.table thead th:nth-child(2) { width: 180px; }
		.table thead th:nth-child(3) { width: 150px; }
		.table thead th:nth-child(4) { width: 150px; }
		.table thead th:nth-child(5) { width: 120px; }
		.table thead th:nth-child(6) { width: 120px; }
		.table thead th:nth-child(7) { width: 100px; }
		.table thead th:nth-child(8) { width: 200px; }
		.table thead th:nth-child(9) { width: 110px; }
		.table tbody td {
			padding: 12px 16px;
			border-bottom: 1px solid #f1f5f9;
			color: var(--text);
			vertical-align: middle;
			word-wrap: break-word;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;
		}
		.table tbody td:nth-child(3),
		.table tbody td:nth-child(4),
		.table tbody td:nth-child(8) {
			white-space: normal;
			word-break: break-word;
		}
		.feedback-display {
			background: #f0f9ff;
			border-left: 4px solid #0ea5e9;
			padding: 12px;
			border-radius: 8px;
			margin-top: 8px;
		}
		.feedback-rating {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 6px;
		}
		.feedback-stars {
			font-size: 1.1rem;
			letter-spacing: 2px;
		}
		.feedback-comment {
			color: #475569;
			font-size: 0.9rem;
			line-height: 1.5;
			margin: 0;
		}
		.feedback-date {
			color: #64748b;
			font-size: 0.75rem;
			margin-top: 4px;
		}
		.no-feedback {
			color: var(--muted);
			font-style: italic;
			font-size: 0.85rem;
		}
		.table tbody tr:hover {
			background: #f9fafb;
		}
		.badge-pending {
			background: #fff3cd;
			color: #8a6d3b;
			padding: 6px 14px;
			border-radius: 999px;
			font-size: 0.8rem;
			font-weight: 600;
		}
		.badge-accepted,
		.badge-approved {
			background: #d1fae5;
			color: #065f46;
			padding: 6px 14px;
			border-radius: 999px;
			font-size: 0.8rem;
			font-weight: 600;
		}
		.badge-declined,
		.badge-rejected {
			background: #fee2e2;
			color: #b91c1c;
			padding: 6px 14px;
			border-radius: 999px;
			font-size: 0.8rem;
			font-weight: 600;
		}
		.badge-completed {
			background: #dbeafe;
			color: #1d4ed8;
			padding: 6px 14px;
			border-radius: 999px;
			font-size: 0.8rem;
			font-weight: 600;
		}
		.btn-approve {
			background: #16a34a;
			color: #fff;
			border: none;
			border-radius: 999px;
			padding: 8px 16px;
			font-weight: 600;
			font-size: 0.85rem;
			cursor: pointer;
			margin-right: 8px;
		}
		.btn-approve:hover {
			background: #15803d;
		}
		.btn-decline {
			background: #dc2626;
			color: #fff;
			border: none;
			border-radius: 999px;
			padding: 8px 16px;
			font-weight: 600;
			font-size: 0.85rem;
			cursor: pointer;
		}
		.btn-decline:hover {
			background: #b91c1c;
		}
		@media (max-width: 768px) {
			.sidebar {
				width: 100%;
				position: relative;
				min-height: auto;
			}
			.main-content {
				margin-left: 0;
				padding: 15px;
			}
		}
	</style>
</head>
<body>
	<aside class="sidebar">
		<img src="img/cjclogo.png" alt="CJC Logo">
		<nav>
			<a href="#" onclick="return false;">Appointments</a>
		</nav>
		<button class="logout-btn" onclick="window.location.href='index.html'">Sign Out</button>
	</aside>

	<main class="main-content">
		<div class="header">
			<div class="d-flex justify-content-between align-items-center">
				<div>
					<p style="letter-spacing:0.25em;text-transform:uppercase;font-weight:600;margin-bottom:6px;font-size:0.9rem;">Office Access</p>
					<h1><?php echo htmlspecialchars(strtoupper($officeName)); ?></h1>
				</div>
				<div class="text-end">
					<p style="font-weight:600;margin:0;">Welcome, Office Staff</p>
					<p style="font-size:0.9rem;opacity:0.85;margin:0;">View and manage appointments</p>
				</div>
			</div>
		</div>

		<div class="content-card">
			<div class="filter-section">
				<h2>All Appointments</h2>
			</div>

			<div class="table-responsive">
				<?php if (empty($appointments)): ?>
					<p class="text-center text-muted py-5">No appointments found.</p>
				<?php else: ?>
					<table class="table table-hover align-middle">
						<thead>
							<tr>
								<th>Name</th>
								<th>Email</th>
								<th>Phone</th>
								<th>Concern</th>
								<th>Date</th>
								<th>Time</th>
								<th>Status</th>
								<th>Feedback</th>
								<th>Actions</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($appointments as $row): ?>
								<?php
									$statusLower = strtolower((string)$row['status']);
									$badgeClass = 'badge-pending';
									if ($statusLower === 'accepted' || $statusLower === 'approved') {
										$badgeClass = 'badge-approved';
									} elseif ($statusLower === 'declined' || $statusLower === 'rejected') {
										$badgeClass = 'badge-declined';
									} elseif ($statusLower === 'completed') {
										$badgeClass = 'badge-completed';
									}
									$fullName = htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
									$phone = htmlspecialchars((string)($row['phone'] ?? ''));
									$statusText = ucfirst($statusLower);
									// Check for feedback - handle both null and empty string cases
									$hasFeedback = isset($row['rating']) && $row['rating'] !== null && $row['rating'] !== '';
									$rating = $hasFeedback ? (int)$row['rating'] : 0;
									$feedbackComment = isset($row['feedback_comment']) && $row['feedback_comment'] !== null ? trim($row['feedback_comment']) : '';
									$feedbackDate = isset($row['feedback_submitted_at']) && $row['feedback_submitted_at'] !== null ? $row['feedback_submitted_at'] : '';
								?>
								<tr>
									<td><?php echo $fullName; ?></td>
									<td><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
									<td><?php echo $phone ?: '-'; ?></td>
									<td><?php echo htmlspecialchars($row['concern'] ?? '-'); ?></td>
									<td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
									<td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
									<td>
										<span class="<?php echo $badgeClass; ?>"><?php echo $statusText; ?></span>
									</td>
									<td>
										<?php if ($hasFeedback && $rating > 0 && $rating <= 5): ?>
											<div class="feedback-display">
												<div class="feedback-rating">
													<span class="feedback-stars">
														<?php 
															echo str_repeat('⭐', $rating);
															echo str_repeat('☆', 5 - $rating);
														?>
													</span>
													<span style="font-weight:600;color:#0ea5e9;"><?php echo $rating; ?>/5</span>
												</div>
												<?php if ($feedbackComment): ?>
													<p class="feedback-comment"><?php echo htmlspecialchars($feedbackComment); ?></p>
												<?php endif; ?>
												<?php if ($feedbackDate): ?>
													<div class="feedback-date">
														<?php 
															$date = new DateTime($feedbackDate);
															echo $date->format('M d, Y');
														?>
													</div>
												<?php endif; ?>
											</div>
										<?php else: ?>
											<span class="no-feedback">No feedback yet</span>
										<?php endif; ?>
									</td>
									<td>
										<button class="btn-approve approve-btn" 
												data-phone="<?php echo htmlspecialchars($phone); ?>" 
												data-name="<?php echo htmlspecialchars($fullName); ?>"
												data-appointment-id="<?php echo $row['appointment_id']; ?>">Approve</button>
										<button class="btn-decline decline-btn" 
												data-phone="<?php echo htmlspecialchars($phone); ?>" 
												data-name="<?php echo htmlspecialchars($fullName); ?>"
												data-appointment-id="<?php echo $row['appointment_id']; ?>">Decline</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	</main>

	<script>
		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('approve-btn') || e.target.classList.contains('decline-btn')) {
				const phone = e.target.getAttribute('data-phone') || '';
				const name = e.target.getAttribute('data-name') || '';
				const appointmentId = e.target.getAttribute('data-appointment-id') || '';
				const action = e.target.classList.contains('approve-btn') ? 'approved' : 'declined';
				const office = <?php echo json_encode($officeName); ?>;
				const message = `Hello ${name}, your appointment has been ${action} by the ${office}.`;

				// Send SMS
				fetch('send_sms.php', {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded'},
					body: `phone_number=${encodeURIComponent(phone)}&message=${encodeURIComponent(message)}`
				})
				.then(res => res.json())
				.then(data => {
					if (data.success) {
						alert('SMS sent successfully!');
						// Optionally reload the page to reflect status changes
						// window.location.reload();
					} else {
						alert('SMS failed: ' + (data.error || 'Unknown error'));
					}
				})
				.catch(() => {
					console.warn('SMS sending failed due to network error.');
				});
			}
		});
	</script>
</body>
</html>
