<?php
session_start();
$pdo = require __DIR__ . '/../config/db.php';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $status = $_POST['status'] === 'Accepted' ? 'Accepted' : 'Declined';

    // Update both tables
    $stmtU1 = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ?");
    $stmtU1->execute([$status, $appointment_id]);
    $stmtU2 = $pdo->prepare("UPDATE appointment_offices SET status = ? WHERE appointment_id = ?");
    $stmtU2->execute([$status, $appointment_id]);
}

// Fetch all appointments
$sql = "SELECT a.appointment_id, a.user_id, a.appointment_date, a.appointment_time, a.status, 
               ao.office_id, u.name, u.user_type, u.student_no, a.concern
        FROM appointments a
        JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
        JOIN users u ON a.user_id = u.id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = $pdo->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - All Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
    body {
        background: linear-gradient(135deg, #e0e7ff 0%, #f8fafc 100%);
        min-height: 100vh;
    }
    .dashboard-card {
        border-radius: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.07);
        background: #fff;
    }
    .table thead th {
        background: #3b82f6;
        color: #fff;
    }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4 text-center fw-bold text-primary">All Appointments (Admin)</h2>
    <div class="card dashboard-card p-4">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Office</th>
                        <th>Name</th>
                        <th>User Type</th>
                        <th>Student No.</th>
                        <th>Concern</th>
                        <th>Date Requested</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $result->fetch()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['office_id']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['user_type']) ?></td>
                        <td><?= htmlspecialchars($row['student_no']) ?: '-' ?></td>
                        <td><?= htmlspecialchars($row['concern']) ?></td>
                        <td><?= htmlspecialchars($row['appointment_date']) ?></td>
                        <td><?= htmlspecialchars($row['appointment_time']) ?></td>
                        <td>
                            <?php if ($row['status'] == 'Pending'): ?>
                                <span class="badge bg-warning text-dark">Pending</span>
                            <?php elseif ($row['status'] == 'Accepted'): ?>
                                <span class="badge bg-success">Accepted</span>
                            <?php elseif ($row['status'] == 'Declined'): ?>
                                <span class="badge bg-danger">Declined</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($row['status']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'Pending'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="appointment_id" value="<?= $row['appointment_id'] ?>">
                                    <button name="status" value="Accepted" class="btn btn-success btn-sm">Accept</button>
                                    <button name="status" value="Declined" class="btn btn-danger btn-sm">Decline</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">No action</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>