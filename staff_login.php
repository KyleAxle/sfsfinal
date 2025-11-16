<?php
// filepath: c:\xampp\htdocs\sfs\staff_login_process.php
session_start();
$pdo = require __DIR__ . '/config/db.php';

$email = $_POST['email'];
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT staff_id, office_id, office_name, password FROM staff WHERE email = ?");
$stmt->execute([$email]);
$stmt->bindColumn(1, $staff_id);
$stmt->bindColumn(2, $office_id);
$stmt->bindColumn(3, $db_office_name);
$stmt->bindColumn(4, $hashed);

if ($stmt->fetch() && password_verify($password, $hashed)) {
    $_SESSION['staff_id'] = $staff_id;
    $_SESSION['office_id'] = $office_id;
    $_SESSION['office_name'] = $db_office_name;

    // Redirect based on office_name
    switch (strtolower($db_office_name)) {
        case "registrar":
            header("Location: /sfs/registrar_dashboard.html");
            break;
        case "cashier":
            header("Location: /sfs/cashier_dashboard.html");
            break;
        case "ccis office":
            header("Location: /sfs/ccisoffice_dashboard.html");
            break;
        case "dean of ccis":
            header("Location: /sfs/deanofccis_dashboard.html");
            break;
        case "guidance":
            header("Location: /sfs/guidance_dashboard.html");
            break;
        case "assessment":
            header("Location: /sfs/assessment_dashboard.html");
            break;
    }
    exit;
} else {
    // Invalid login
    echo "<script>alert('Invalid credentials');window.location.href='staff_login.html';</script>";
}
?>