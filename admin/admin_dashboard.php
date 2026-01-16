<?php
require_once __DIR__ . '/../config/session.php';
$pdo = require __DIR__ . '/../config/db.php';

// Handle appointment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_appointment_id'])) {
    $appointment_id = intval($_POST['delete_appointment_id']);
    if ($appointment_id > 0) {
        try {
            $pdo->beginTransaction();
            // Delete from appointment_offices first (due to foreign key)
            $stmt1 = $pdo->prepare("DELETE FROM appointment_offices WHERE appointment_id = ?");
            $stmt1->execute([$appointment_id]);
            // Then delete from appointments
            $stmt2 = $pdo->prepare("DELETE FROM appointments WHERE appointment_id = ?");
            $stmt2->execute([$appointment_id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Failed to delete appointment: ' . $e->getMessage());
        }
    }
    // Redirect to prevent resubmission
    header('Location: admin_dashboard.php');
    exit;
}

// Fetch all appointments
$sql = "SELECT a.appointment_id, a.user_id, a.appointment_date, a.appointment_time, a.status, 
               ao.office_id, o.office_name, u.first_name, u.last_name, u.email, a.concern
        FROM appointments a
        JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
        JOIN public.users u ON a.user_id = u.user_id
        LEFT JOIN public.offices o ON ao.office_id = o.office_id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$result = $pdo->query($sql);
$appointments = $result->fetchAll(PDO::FETCH_ASSOC);

// Fetch all unique offices for the filter
$officesSql = "SELECT DISTINCT o.office_id, o.office_name 
               FROM appointments a
               JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
               JOIN public.offices o ON ao.office_id = o.office_id
               ORDER BY o.office_name";
$officesResult = $pdo->query($officesSql);
$offices = $officesResult->fetchAll(PDO::FETCH_ASSOC);

// Get min and max dates for the date range filter
$dateRangeSql = "SELECT MIN(appointment_date) as min_date, MAX(appointment_date) as max_date FROM appointments";
$dateRangeResult = $pdo->query($dateRangeSql);
$dateRange = $dateRangeResult->fetch(PDO::FETCH_ASSOC);
$minDate = $dateRange['min_date'] ?? date('Y-m-d');
$maxDate = $dateRange['max_date'] ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard • CJC School Frontline Services</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
    body {
            background: #f5f6fb;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-y: auto;
        }
        .sidebar {
            background: #7d0000;
            color: #fff;
            min-height: 100vh;
            padding: 20px;
            position: fixed;
            width: 260px;
            left: 0;
            top: 0;
            overflow-y: auto;
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
            flex: 1;
        }
        .sidebar nav a {
            color: #fff;
            text-decoration: none;
            padding: 12px 16px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1rem;
            text-align: center;
            transition: all 0.2s;
        }
        .sidebar nav a:hover {
            background: #fff;
            color: #7d0000;
        }
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
            overflow-y: auto;
        }
        .header {
            background: #7d0000;
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
            background: #fff;
            border-radius: 20px;
            padding: 24px;
            padding-right: 28px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
            overflow: visible;
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
        .filter-controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-controls select,
        .filter-controls input[type="date"] {
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            min-width: 200px;
            font-family: inherit;
            font-size: 0.9rem;
        }
        .filter-controls select:focus,
        .filter-controls input[type="date"]:focus {
            outline: none;
            border-color: #7d0000;
            box-shadow: 0 0 0 3px rgba(125, 0, 0, 0.1);
        }
        .date-range-container {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .date-range-inputs {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .btn-clear-date {
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
        background: #fff;
            color: #6b7280;
            font-size: 0.85rem;
            cursor: pointer;
        }
        .btn-clear-date:hover {
            background: #f9fafb;
            color: #7d0000;
            border-color: #7d0000;
        }
        .btn-manage {
            background: #7d0000;
            color: #fff;
            padding: 10px 20px;
            border-radius: 24px;
            text-decoration: none;
            font-weight: 600;
            border: none;
        }
        .btn-manage:hover {
            background: #5a0000;
            color: #fff;
        }
        .table-responsive {
            border-radius: 12px;
            overflow-x: auto;
            overflow-y: visible;
            padding: 0 20px 0 0;
        }
        .table thead {
            background: #f9fafb;
    }
    .table thead th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.08em;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
            padding: 12px 16px;
        }
        .table thead th:last-child {
            width: 160px !important;
            min-width: 160px !important;
            white-space: nowrap;
            text-align: center;
            padding: 12px 20px 12px 8px;
        }
        .table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
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
        .btn-remove {
            background: #dc2626;
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            white-space: nowrap;
            min-width: 80px;
            width: auto;
            display: inline-block;
            box-sizing: border-box;
        }
        .btn-remove:hover {
            background: #b91c1c;
        }
        .table tbody td:last-child {
            width: 160px !important;
            min-width: 160px !important;
            white-space: nowrap;
            padding: 12px 20px 12px 8px;
            text-align: center;
            overflow: visible;
        }
        .table tbody td:last-child form {
            display: block;
            margin: 0;
            width: 100%;
        }
        .table tbody td:last-child form button {
            width: 100%;
            min-width: 100px;
            padding: 8px 16px;
            box-sizing: border-box;
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
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 10;
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
        .admin-table {
            background: #fff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.08);
            overflow-x: auto;
            width: 100%;
            min-height: 400px;
            position: relative;
        }
        .admin-table table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
            min-width: 1120px;
        }
        .admin-table thead th:nth-child(1) { width: 140px; }
        .admin-table thead th:nth-child(2) { width: 150px; }
        .admin-table thead th:nth-child(3) { width: 200px; }
        .admin-table thead th:nth-child(4) { width: 180px; }
        .admin-table thead th:nth-child(5) { width: 120px; }
        .admin-table thead th:nth-child(6) { width: 100px; }
        .admin-table thead th:nth-child(7) { width: 120px; }
        .admin-table thead th:nth-child(8) { width: 110px; }
        .admin-table thead th {
            text-align: left;
            padding: 12px 16px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            font-weight: 600;
            border-bottom: 2px solid #f1f5f9;
        }
        .admin-table tbody td {
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text);
            vertical-align: middle;
            word-wrap: break-word;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .admin-table tbody td:nth-child(3),
        .admin-table tbody td:nth-child(4) {
            white-space: normal;
            word-break: break-word;
        }
        .admin-table tbody tr:hover {
            background: #f9fafb;
        }
        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }
        .badge-pending { background: #fff3cd; color: #8a6d3b; padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .badge-accepted { background: #d1fae5; color: #065f46; padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .badge-declined { background: #fee2e2; color: #b91c1c; padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .badge-completed { background: #dbeafe; color: #1d4ed8; padding: 6px 14px; border-radius: 999px; font-size: 0.8rem; font-weight: 600; }
        .btn-remove {
            border: none;
            border-radius: 999px;
            padding: 8px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            background: #dc2626;
            color: #fff;
            white-space: nowrap;
            min-width: 80px;
            width: auto;
            display: inline-block;
            box-sizing: border-box;
        }
        .btn-remove:hover {
            background: #b91c1c;
        }
        .admin-table tbody td:last-child {
            text-align: center;
            white-space: nowrap;
            width: auto;
            min-width: 100px;
            padding: 12px 16px;
        }
        .admin-table tbody td:last-child form {
            display: inline-block;
            margin: 0;
            width: 100%;
        }
        .admin-table tbody td:last-child form button {
            width: 100%;
        }
        .admin-table thead th:last-child {
            width: auto;
            min-width: 100px;
            white-space: nowrap;
            text-align: center;
        }
        .btn-manage { background: var(--primary); color: #fff; padding: 10px 20px; border-radius: 24px; text-decoration: none; font-weight: 600; display: inline-block; }
        .panel {
            overflow-x: auto;
            overflow-y: visible;
            width: 100%;
            min-width: 0;
            padding-right: 0;
        }
        .admin-table tbody {
            display: table-row-group;
        }
        .filter-row {
            flex-wrap: wrap;
            gap: 12px;
        }
        .filter-row h2 {
            margin: 0;
            flex: 1;
            min-width: 200px;
        }
        .filter-row select,
        .filter-row input[type="date"] {
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            min-width: 200px;
            font-family: inherit;
            font-size: 0.9rem;
            cursor: pointer;
        }
        .filter-row select:focus,
        .filter-row input[type="date"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(125, 0, 0, 0.1);
        }
        .date-range-container {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .date-range-container label {
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 500;
            white-space: nowrap;
        }
        .date-range-inputs {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .date-range-inputs input[type="date"] {
            min-width: 160px;
        }
        .btn-clear-date {
            padding: 10px 16px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: var(--muted);
            font-family: inherit;
            font-size: 0.85rem;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-clear-date:hover {
            background: #f9fafb;
            color: var(--primary);
            border-color: var(--primary);
        }
        @media (max-width: 1024px) {
            .staff-shell {
                width: 100%;
                border-radius: 0;
            }
            body {
                padding: 0;
            }
            .admin-table {
                padding: 16px;
            }
            .admin-table table {
                min-width: 700px;
            }
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <aside class="sidebar">
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

        <main class="main-content flex-grow-1">
            <div class="header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p style="letter-spacing:0.25em;text-transform:uppercase;font-weight:600;margin-bottom:6px;font-size:0.9rem;">Admin Access</p>
                        <h1>ADMIN DASHBOARD</h1>
                    </div>
                    <div class="text-end">
                        <p style="font-weight:600;margin:0;">Welcome, Admin</p>
                        <p style="font-size:0.9rem;opacity:0.85;margin:0;">Manage all appointments & offices</p>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="filter-section">
                    <h2>All Appointments</h2>
                    <div class="filter-controls">
                        <select id="officeFilter" class="form-select">
                            <option value="all">All Offices</option>
                            <?php foreach ($offices as $office): ?>
                                <option value="<?= htmlspecialchars($office['office_name']); ?>"><?= htmlspecialchars($office['office_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="date-range-container">
                            <label style="font-size:0.85rem;color:#6b7280;font-weight:500;white-space:nowrap;">Date Range:</label>
                            <div class="date-range-inputs">
                                <input type="date" id="dateFrom" class="form-control" min="<?= htmlspecialchars($minDate); ?>" max="<?= htmlspecialchars($maxDate); ?>">
                                <span style="color:#6b7280;">to</span>
                                <input type="date" id="dateTo" class="form-control" min="<?= htmlspecialchars($minDate); ?>" max="<?= htmlspecialchars($maxDate); ?>">
                                <button type="button" class="btn-clear-date" id="clearDateRange">Clear</button>
                            </div>
                        </div>
                        <a href="manage_offices.php" class="btn-manage">Manage Offices</a>
                    </div>
                </div>

        <div class="table-responsive">
                    <?php if (empty($appointments)): ?>
                        <p class="text-center text-muted py-5">No appointments found.</p>
                    <?php else: ?>
                        <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Office</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Concern</th>
                                    <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                                    <th>Remove</th>
                    </tr>
                </thead>
                            <tbody id="appointmentsTableBody">
                                <?php foreach ($appointments as $row): ?>
                                    <tr data-office="<?= htmlspecialchars($row['office_name'] ?? 'N/A'); ?>" data-date="<?= htmlspecialchars($row['appointment_date']); ?>">
                                        <td><?= htmlspecialchars($row['office_name'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')); ?></td>
                                        <td><?= htmlspecialchars($row['email'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['concern'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['appointment_date']); ?></td>
                                        <td><?= htmlspecialchars($row['appointment_time']); ?></td>
                                        <td>
                                            <?php
                                            $status = strtolower($row['status'] ?? 'pending');
                                            if ($status === 'pending'): ?>
                                                <span class="badge-pending">Pending</span>
                                            <?php elseif ($status === 'accepted' || $status === 'approved'): ?>
                                                <span class="badge-accepted">Approved</span>
                                            <?php elseif ($status === 'declined' || $status === 'rejected'): ?>
                                                <span class="badge-declined">Declined</span>
                                            <?php elseif ($status === 'completed'): ?>
                                                <span class="badge-completed">Completed</span>
                            <?php else: ?>
                                                <span class="badge-pending"><?= htmlspecialchars(ucfirst($status)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" onsubmit="return confirm('Are you sure you want to remove this appointment? This action cannot be undone.');">
                                <input type="hidden" name="delete_appointment_id" value="<?= $row['appointment_id'] ?>">
                                <button type="submit" class="btn-remove">Remove</button>
                            </form>
                        </td>
                    </tr>
                                <?php endforeach; ?>
                </tbody>
            </table>
                    <?php endif; ?>
                </div>
        </div>
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

        // Filter functionality (Office and Date Range)
        const officeFilter = document.getElementById('officeFilter');
        const dateFrom = document.getElementById('dateFrom');
        const dateTo = document.getElementById('dateTo');
        const clearDateRange = document.getElementById('clearDateRange');
        const appointmentsTableBody = document.getElementById('appointmentsTableBody');
        
        // Ensure dateTo is not before dateFrom
        if (dateFrom && dateTo) {
            dateFrom.addEventListener('change', function() {
                if (this.value && dateTo.value && this.value > dateTo.value) {
                    dateTo.value = this.value;
                }
                dateTo.min = this.value || dateTo.min;
                applyFilters();
            });
            
            dateTo.addEventListener('change', function() {
                if (this.value && dateFrom.value && this.value < dateFrom.value) {
                    dateFrom.value = this.value;
                }
                dateFrom.max = this.value || dateFrom.max;
                applyFilters();
            });
        }
        
        // Clear date range
        if (clearDateRange) {
            clearDateRange.addEventListener('click', function() {
                if (dateFrom) dateFrom.value = '';
                if (dateTo) dateTo.value = '';
                applyFilters();
            });
        }
        
        function applyFilters() {
            if (!appointmentsTableBody) return;
            
            const selectedOffice = officeFilter ? officeFilter.value : 'all';
            const fromDate = dateFrom ? dateFrom.value : '';
            const toDate = dateTo ? dateTo.value : '';
            const rows = appointmentsTableBody.querySelectorAll('tr:not(#noResultsMsg)');
            let visibleCount = 0;
            
            // Preserve scroll position
            const tableContainer = document.querySelector('.admin-table');
            const scrollLeft = tableContainer ? tableContainer.scrollLeft : 0;
            
            rows.forEach(row => {
                const officeName = row.getAttribute('data-office');
                const appointmentDate = row.getAttribute('data-date');
                
                // Office filter
                const officeMatch = selectedOffice === 'all' || officeName === selectedOffice;
                
                // Date range filter
                let dateMatch = true;
                if (fromDate || toDate) {
                    if (fromDate && toDate) {
                        // Both dates selected - check if appointment date is within range
                        dateMatch = appointmentDate >= fromDate && appointmentDate <= toDate;
                    } else if (fromDate) {
                        // Only from date selected
                        dateMatch = appointmentDate >= fromDate;
                    } else if (toDate) {
                        // Only to date selected
                        dateMatch = appointmentDate <= toDate;
                    }
                }
                
                if (officeMatch && dateMatch) {
                    row.style.display = 'table-row';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            let noResultsMsg = document.getElementById('noResultsMsg');
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('tr');
                noResultsMsg.id = 'noResultsMsg';
                noResultsMsg.style.display = 'none';
                appointmentsTableBody.appendChild(noResultsMsg);
            }
            
            const hasFilters = selectedOffice !== 'all' || fromDate || toDate;
            if (visibleCount === 0 && hasFilters) {
                noResultsMsg.innerHTML = '<td colspan="8" style="text-align:center;padding:40px;color:var(--muted);">No appointments found for the selected filters.</td>';
                noResultsMsg.style.display = 'table-row';
            } else {
                noResultsMsg.style.display = 'none';
            }
            
            // Restore scroll position
            if (tableContainer) {
                tableContainer.scrollLeft = scrollLeft;
            }
        }
        
        if (officeFilter) {
            officeFilter.addEventListener('change', applyFilters);
        }
        
        if (dateFrom) {
            dateFrom.addEventListener('change', applyFilters);
        }
        
        if (dateTo) {
            dateTo.addEventListener('change', applyFilters);
        }
        
        // Apply filters on page load
        applyFilters();
    </script>
</body>
</html>
