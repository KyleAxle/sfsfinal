<?php
require_once __DIR__ . '/config/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    $pdo = require __DIR__ . '/config/db.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Handle profile picture upload
$uploadDir = __DIR__ . '/uploads/profile_pictures/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        error_log('Failed to create upload directory: ' . $uploadDir);
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory. Please contact administrator.']);
        exit;
    }
}

// Ensure directory is writable
if (!is_writable($uploadDir)) {
    @chmod($uploadDir, 0755);
    if (!is_writable($uploadDir)) {
        error_log('Upload directory is not writable: ' . $uploadDir);
        echo json_encode(['success' => false, 'error' => 'Upload directory is not writable. Please contact administrator.']);
        exit;
    }
}

$profilePicturePath = null;
$removePicture = isset($_POST['remove_picture']) && $_POST['remove_picture'] === '1';

// Handle file upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_picture'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        $errorMsg = $errorMessages[$file['error']] ?? 'Unknown upload error';
        echo json_encode(['success' => false, 'error' => 'Upload error: ' . $errorMsg]);
        exit;
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Try to get MIME type using finfo if available
    $mimeType = null;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    } else {
        // Fallback: use file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        $mimeType = $mimeMap[$extension] ?? null;
        
        if (!$mimeType || !in_array($extension, $allowedExtensions)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
            exit;
        }
    }
    
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.']);
        exit;
    }
    
    // Validate file size (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File size must be less than 5MB.']);
        exit;
    }
    
    // Generate unique filename based on MIME type
    $extensionMap = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];
    $extension = $extensionMap[$mimeType] ?? 'jpg';
    $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    // Delete old profile picture if exists (only if column exists)
    try {
        $columns = $pdo->query("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'users'
            AND column_name = 'profile_picture'
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('profile_picture', $columns)) {
            $oldPicture = $pdo->prepare("SELECT profile_picture FROM public.users WHERE user_id = ?");
            $oldPicture->execute([$user_id]);
            $oldPictureRow = $oldPicture->fetch();
            if ($oldPictureRow && $oldPictureRow['profile_picture']) {
                $oldPath = __DIR__ . $oldPictureRow['profile_picture'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }
    } catch (PDOException $e) {
        // Column doesn't exist, skip old picture deletion
        error_log('Profile picture column check: ' . $e->getMessage());
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $profilePicturePath = '/uploads/profile_pictures/' . $filename;
    } else {
        $errorDetails = 'Failed to move uploaded file.';
        if (!is_writable($uploadDir)) {
            $errorDetails .= ' Upload directory is not writable.';
        }
        error_log('File upload failed: ' . $errorDetails . ' Target: ' . $targetPath);
        echo json_encode(['success' => false, 'error' => 'Failed to upload file: ' . $errorDetails]);
        exit;
    }
} elseif ($removePicture) {
    // Remove existing profile picture (only if column exists)
    try {
        $columns = $pdo->query("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'users'
            AND column_name = 'profile_picture'
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('profile_picture', $columns)) {
            $oldPicture = $pdo->prepare("SELECT profile_picture FROM public.users WHERE user_id = ?");
            $oldPicture->execute([$user_id]);
            $oldPictureRow = $oldPicture->fetch();
            if ($oldPictureRow && $oldPictureRow['profile_picture']) {
                $oldPath = __DIR__ . $oldPictureRow['profile_picture'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }
    } catch (PDOException $e) {
        // Column doesn't exist, skip
        error_log('Profile picture column check: ' . $e->getMessage());
    }
    $profilePicturePath = '';
}

// Get and validate input
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$middle_initial = trim($_POST['middle_initial'] ?? '');
$student_id = trim($_POST['student_id'] ?? '');
$age = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : null;
$date_of_birth = trim($_POST['date_of_birth'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');

// Validation
if (empty($first_name) || empty($last_name) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'First name, last name, and email are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if ($age !== null && ($age < 1 || $age > 150)) {
    echo json_encode(['success' => false, 'error' => 'Age must be between 1 and 150']);
    exit;
}

if ($middle_initial && strlen($middle_initial) > 10) {
    echo json_encode(['success' => false, 'error' => 'Middle initial must be 10 characters or less']);
    exit;
}

try {
    // Check if email is already taken by another user
    $checkEmail = $pdo->prepare("SELECT user_id FROM public.users WHERE email = ? AND user_id != ?");
    $checkEmail->execute([$email, $user_id]);
    if ($checkEmail->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Email is already taken by another user']);
        exit;
    }

    // Check which columns exist before updating
    try {
        $columns = $pdo->query("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'users'
        ")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log('Error checking columns: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }

    // Build update query dynamically based on available columns
    $updates = [];
    $params = [];
    
    $updates[] = "first_name = ?";
    $params[] = $first_name;
    
    $updates[] = "last_name = ?";
    $params[] = $last_name;
    
    $updates[] = "email = ?";
    $params[] = $email;
    
    if (in_array('phone', $columns)) {
        $updates[] = "phone = ?";
        $params[] = $phone ?: null;
    }
    
    if (in_array('middle_initial', $columns)) {
        $updates[] = "middle_initial = ?";
        $params[] = $middle_initial ?: null;
    }
    
    if (in_array('student_id', $columns)) {
        $updates[] = "student_id = ?";
        $params[] = $student_id ?: null;
    }
    
    if (in_array('age', $columns)) {
        $updates[] = "age = ?";
        $params[] = $age;
    }
    
    if (in_array('date_of_birth', $columns)) {
        $updates[] = "date_of_birth = ?";
        $params[] = $date_of_birth ?: null;
    }
    
    if (in_array('profile_picture', $columns) && $profilePicturePath !== null) {
        $updates[] = "profile_picture = ?";
        $params[] = $profilePicturePath;
    }
    
    $updates[] = "updated_at = now()";
    $params[] = $user_id;

    if (empty($updates)) {
        echo json_encode(['success' => false, 'error' => 'No fields to update']);
        exit;
    }

    $sql = "UPDATE public.users SET " . implode(', ', $updates) . " WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt) {
        $errorInfo = $pdo->errorInfo();
        error_log('SQL prepare error: ' . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'error' => 'Database error: ' . ($errorInfo[2] ?? 'Unknown error')]);
        exit;
    }
    
    $result = $stmt->execute($params);
    
    if (!$result) {
        $errorInfo = $stmt->errorInfo();
        error_log('SQL execute error: ' . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'error' => 'Database error: ' . ($errorInfo[2] ?? 'Unknown error')]);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} catch (PDOException $e) {
    error_log('Profile update error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Failed to update profile: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Profile update error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Failed to update profile: ' . $e->getMessage()]);
}

