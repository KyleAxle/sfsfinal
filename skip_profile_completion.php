<?php
/**
 * Skip Profile Completion - Remove the flag and allow user to continue
 * User can complete profile later via the profile page
 */

require_once __DIR__ . '/config/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit();
}

// Remove the profile completion flag
unset($_SESSION['needs_profile_completion']);

// Redirect to dashboard
header('Location: proto2.html');
exit();
?>
