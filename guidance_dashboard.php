<?php
session_start();

$pdo = require __DIR__ . '/config/db.php';
$sql = "SELECT o.office_name, a.user_id, u.last_name, u.first_name, u.email, u.phone, a.appointment_date, a.appointment_time, a.concern, a.status
        FROM appointments a
        JOIN users u ON a.user_id = u.user_id
        JOIN offices o ON a.office_id = o.office_id
        WHERE o.office_name = 'guidance'";
$result = $pdo->query($sql);

while ($row = $result->fetch()) {
    $badge = "badge-pending";
    if (strtolower($row['status']) == "approved") $badge = "badge-approved";
    if (strtolower($row['status']) == "rejected") $badge = "badge-rejected";
    $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
    $phone = htmlspecialchars($row['phone']);
    echo "<tr>
        <td>{$row['office_name']}</td>
        <td>{$row['user_id']}</td>
        <td>{$row['last_name']}</td>
        <td>{$row['first_name']}</td>
        <td>{$row['email']}</td>
        <td>{$row['phone']}</td>
        <td>{$row['appointment_date']}</td>
        <td>{$row['appointment_time']}</td>
        <td>{$row['concern']}</td>
        <td><span class='badge $badge'>{$row['status']}</span></td>
        <td>
            <button class='btn btn-success btn-sm approve-btn' data-phone='{$phone}' data-name='{$fullName}'>Approve</button>
            <button class='btn btn-danger btn-sm decline-btn' data-phone='{$phone}' data-name='{$fullName}'>Decline</button>
        </td>
    </tr>";
}

?>