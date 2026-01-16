<?php
require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
	echo json_encode(['success' => false, 'error' => 'Not logged in']);
	exit;
}

$pdo = require __DIR__ . '/config/db.php';

$user_id = intval($_SESSION['user_id']);

// Check which columns exist
$columns = $pdo->query("
	SELECT column_name 
	FROM information_schema.columns 
	WHERE table_schema = 'public' 
	AND table_name = 'users'
")->fetchAll(PDO::FETCH_COLUMN);

$selectFields = ['first_name', 'last_name', 'email'];
$optionalFields = [
	'phone' => '',
	'middle_initial' => '',
	'student_id' => '',
	'age' => 0,
	'date_of_birth' => '',
	'profile_picture' => ''
];

foreach ($optionalFields as $field => $default) {
	if (in_array($field, $columns)) {
		if ($field === 'date_of_birth') {
			$selectFields[] = "COALESCE({$field}::text, '') as {$field}";
		} elseif ($field === 'age') {
			$selectFields[] = "COALESCE({$field}, 0) as {$field}";
		} else {
			$selectFields[] = "COALESCE({$field}, '') as {$field}";
		}
	}
}

$sql = "SELECT " . implode(', ', $selectFields) . " FROM public.users WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if ($row) {
	$response = [
		'success' => true,
		'first_name' => $row['first_name'],
		'last_name' => $row['last_name'] ?? '',
		'email' => $row['email']
	];
	
	// Add optional fields if they exist in the result
	foreach ($optionalFields as $field => $default) {
		if (in_array($field, $columns)) {
			$response[$field] = $row[$field] ?? $default;
		} else {
			$response[$field] = $default;
		}
	}
	
	echo json_encode($response);
} else {
	echo json_encode(['success' => false, 'error' => 'User not found']);
}
?>