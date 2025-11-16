<?php
session_start();

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

// Database connection (adjust as needed)
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

// Load appointments for the office; include optional registrar-specific fields if present
$sql = "
	select
		o.office_name,
		a.user_id,
		u.last_name,
		u.first_name,
		u.email,
		u.phone,
		a.appointment_date,
		a.appointment_time,
		a.concern,
		a.status,
		-- optional fields (some offices may not use them)
		a.paper_type,
		a.processing_days,
		a.release_date
	from appointments a
	join users u on a.user_id = u.user_id
	join offices o on a.office_id = o.office_id
	where o.office_id = ?
	order by a.appointment_date desc, a.appointment_time desc
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$officeId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo htmlspecialchars($officeName); ?> Dashboard</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
	<style>
	.badge-pending{background:#ffc107;}
	.badge-approved{background:#28a745;}
	.badge-rejected{background:#dc3545;}
	</style>
	</head>
<body class="p-3">
	<div class="container-fluid">
		<h2 class="mb-3"><?php echo htmlspecialchars($officeName); ?> Appointments</h2>

		<div class="table-responsive">
			<table class="table table-striped table-bordered align-middle">
				<thead class="table-light">
					<tr>
						<th>Office</th>
						<th>User ID</th>
						<th>Last Name</th>
						<th>First Name</th>
						<th>Email</th>
						<th>Phone</th>
						<th>Appointment Date</th>
						<th>Time</th>
						<th>Paper Type</th>
						<th>Processing Days</th>
						<th>Release Date</th>
						<th>Concern</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php while ($row = $result->fetch_assoc()): ?>
						<?php
							$statusLower = strtolower((string)$row['status']);
							$badge = $statusLower === 'approved' ? 'badge-approved' : ($statusLower === 'rejected' ? 'badge-rejected' : 'badge-pending');
							$fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
							$phone = htmlspecialchars((string)$row['phone']);
						?>
						<tr>
							<td><?php echo htmlspecialchars($row['office_name']); ?></td>
							<td><?php echo htmlspecialchars($row['user_id']); ?></td>
							<td><?php echo htmlspecialchars($row['last_name']); ?></td>
							<td><?php echo htmlspecialchars($row['first_name']); ?></td>
							<td><?php echo htmlspecialchars($row['email']); ?></td>
							<td><?php echo $phone; ?></td>
							<td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
							<td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
							<td><?php echo isset($row['paper_type']) ? htmlspecialchars((string)$row['paper_type']) : ''; ?></td>
							<td><?php echo isset($row['processing_days']) ? htmlspecialchars((string)$row['processing_days']) : ''; ?></td>
							<td><?php echo isset($row['release_date']) ? htmlspecialchars((string)$row['release_date']) : ''; ?></td>
							<td><?php echo htmlspecialchars($row['concern']); ?></td>
							<td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
							<td>
								<button class="btn btn-success btn-sm approve-btn" data-phone="<?php echo $phone; ?>" data-name="<?php echo $fullName; ?>">Approve</button>
								<button class="btn btn-danger btn-sm decline-btn" data-phone="<?php echo $phone; ?>" data-name="<?php echo $fullName; ?>">Decline</button>
							</td>
						</tr>
					<?php endwhile; ?>
				</tbody>
			</table>
		</div>
	</div>

	<script>
	document.addEventListener('click', function(e) {
		if (e.target.classList.contains('approve-btn') || e.target.classList.contains('decline-btn')) {
			const phone = e.target.getAttribute('data-phone') || '';
			const name = e.target.getAttribute('data-name') || '';
			const action = e.target.classList.contains('approve-btn') ? 'approved' : 'declined';
			const office = <?php echo json_encode($officeName); ?>;
			const message = `Hello ${name}, your appointment has been ${action} by the ${office}.`;

			fetch('send_sms.php', {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: `phone_number=${encodeURIComponent(phone)}&message=${encodeURIComponent(message)}`
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					alert('SMS sent successfully!');
				} else {
					alert('SMS failed: ' + (data.error || 'Unknown error'));
				}
			})
			.catch(() => alert('SMS sending failed due to network error.'));
		}
	});
	</script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>

