<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

$pdo = require __DIR__ . '/config/db.php';

// Get appointment_id from GET or POST
$appointment_id = 0;
$raw_id = null;

if (isset($_GET['appointment_id'])) {
    $raw_id = $_GET['appointment_id'];
} elseif (isset($_POST['appointment_id'])) {
    $raw_id = $_POST['appointment_id'];
}

// Validate and convert to integer
if ($raw_id !== null && $raw_id !== '' && $raw_id !== 'null' && $raw_id !== 'undefined') {
    $appointment_id = (int)$raw_id;
}

$error = '';
$success = '';
$appointment = null;

// Get existing feedback if any
$existingFeedback = null;

// Verify appointment belongs to user
if ($appointment_id > 0) {
    try {
        $checkStmt = $pdo->prepare("
            SELECT a.appointment_id, a.user_id, o.office_name, a.appointment_date, a.appointment_time, a.status
            FROM appointments a
            JOIN appointment_offices ao ON a.appointment_id = ao.appointment_id
            LEFT JOIN offices o ON ao.office_id = o.office_id
            WHERE a.appointment_id = ? AND a.user_id = ?
        ");
        $checkStmt->execute([$appointment_id, $_SESSION['user_id']]);
        $appointment = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$appointment) {
            $error = 'Appointment not found or you do not have permission to provide feedback for this appointment.';
            $appointment_id = 0;
        } elseif (strtolower($appointment['status']) !== 'completed') {
            $error = 'Feedback can only be submitted for completed appointments.';
            $appointment_id = 0;
        } else {
            // Check for existing feedback
            $feedbackStmt = $pdo->prepare("SELECT rating, comment FROM feedback WHERE appointment_id = ?");
            $feedbackStmt->execute([$appointment_id]);
            $existingFeedback = $feedbackStmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Feedback page error: ' . $e->getMessage());
        $error = 'An error occurred while loading the appointment. Please try again.';
        $appointment_id = 0;
    }
} else {
    $error = 'Invalid appointment ID. Please select an appointment from your dashboard.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = trim($_POST['comment'] ?? '');
    $feedback_appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    
    if ($feedback_appointment_id > 0 && $rating > 0 && $rating <= 5) {
        try {
            // Check if feedback already exists
            $checkExisting = $pdo->prepare("SELECT feedback_id FROM feedback WHERE appointment_id = ?");
            $checkExisting->execute([$feedback_appointment_id]);
            $existing = $checkExisting->fetch();
            
            if ($existing) {
                // Update existing feedback
                $stmt = $pdo->prepare("UPDATE feedback SET rating = ?, comment = ?, submitted_at = now() WHERE appointment_id = ?");
                $stmt->execute([$rating, $comment, $feedback_appointment_id]);
                $success = 'Thank you! Your feedback has been updated successfully.';
            } else {
                // Insert new feedback
                $stmt = $pdo->prepare("INSERT INTO feedback (appointment_id, rating, comment) VALUES (?, ?, ?)");
                $stmt->execute([$feedback_appointment_id, $rating, $comment]);
                $success = 'Thank you! Your feedback has been submitted successfully.';
            }
            $appointment_id = 0; // Clear to prevent resubmission
            // Reload feedback data
            if ($feedback_appointment_id > 0) {
                $feedbackStmt = $pdo->prepare("SELECT rating, comment FROM feedback WHERE appointment_id = ?");
                $feedbackStmt->execute([$feedback_appointment_id]);
                $existingFeedback = $feedbackStmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = 'Failed to submit feedback. Please try again.';
            error_log('Feedback submission error: ' . $e->getMessage());
        }
    } else {
        $error = 'Please provide a valid rating (1-5 stars) and ensure all fields are filled.';
    }
}

// Get user info for form
$userStmt = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE user_id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);
$fullName = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
$userEmail = $user['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Submit Feedback • CJC School Frontline Services</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="assets/css/login.css">
	<style>
		body {
			background: linear-gradient(135deg, #f5f6fb 0%, #e8f0f5 100%);
		}
		.feedback-shell {
			width: min(900px, 100%);
			background: #fff;
			border-radius: 24px;
			box-shadow: 0 25px 60px rgba(15, 23, 42, 0.15);
			display: grid;
			grid-template-columns: repeat(2, minmax(0, 1fr));
			overflow: hidden;
			position: relative;
		}
		.feedback-sidebar {
			background: linear-gradient(135deg, #7d0000 0%, #6b0000 50%, #7d0000 100%);
			color: #fff;
			padding: 48px 40px;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			text-align: center;
			position: relative;
			overflow: hidden;
		}
		.feedback-sidebar::before {
			content: '';
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: 
				radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
				radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.03) 0%, transparent 50%);
			pointer-events: none;
		}
		.feedback-icon {
			width: 120px;
			height: 120px;
			background: rgba(255, 255, 255, 0.15);
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 60px;
			margin-bottom: 24px;
			position: relative;
			z-index: 1;
			box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
		}
		.feedback-sidebar h2 {
			font-size: 1.8rem;
			font-weight: 700;
			margin-bottom: 16px;
			position: relative;
			z-index: 1;
		}
		.feedback-sidebar p {
			font-size: 1rem;
			line-height: 1.6;
			opacity: 0.95;
			position: relative;
			z-index: 1;
		}
		.feedback-main {
			padding: clamp(32px, 5vw, 48px);
			display: flex;
			flex-direction: column;
			gap: 24px;
		}
		.feedback-main h1 {
			font-size: clamp(2rem, 4vw, 2.5rem);
			color: var(--text);
			letter-spacing: 0.08em;
			font-weight: 700;
		}
		.feedback-form {
			display: flex;
			flex-direction: column;
			gap: 20px;
		}
		.form-group {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}
		.form-group label {
			font-weight: 600;
			color: #374151;
			font-size: 0.9rem;
		}
		.input-field {
			width: 100%;
			padding: 16px 22px;
			border-radius: 12px;
			border: 1px solid #e5e7eb;
			background: #f9fafb;
			font-size: 1rem;
			font-family: inherit;
			transition: all 0.2s ease;
		}
		.input-field:focus {
			outline: none;
			border-color: #7d0000;
			background: #fff;
			box-shadow: 0 0 0 4px rgba(125, 0, 0, 0.1);
		}
		textarea.input-field {
			resize: vertical;
			min-height: 120px;
		}
		.rating-group {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}
		.rating-label {
			font-weight: 600;
			color: #374151;
			font-size: 0.9rem;
		}
		.star-rating {
			display: flex;
			gap: 8px;
			justify-content: flex-start;
		}
		.star-btn {
			width: 50px;
			height: 50px;
			border: 2px solid #e5e7eb;
			background: #fff;
			border-radius: 12px;
			font-size: 24px;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: all 0.2s ease;
		}
		.star-btn:hover {
			border-color: #7d0000;
			transform: scale(1.1);
		}
		.star-btn.active {
			background: #ffd700;
			border-color: #ffd700;
			color: #fff;
		}
		.submit-btn {
			border: none;
			border-radius: 999px;
			padding: 16px;
			background: linear-gradient(135deg, #7d0000 0%, #6b0000 50%, #7d0000 100%);
			color: #fff;
			font-size: 1rem;
			font-weight: 700;
			cursor: pointer;
			box-shadow: 0 20px 35px rgba(125, 0, 0, 0.35);
			transition: all 0.3s ease;
			letter-spacing: 0.05em;
			text-transform: uppercase;
		}
		.submit-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 25px 45px rgba(125, 0, 0, 0.45);
		}
		.submit-btn:active {
			transform: translateY(0);
		}
		.alert {
			padding: 16px;
			border-radius: 12px;
			font-weight: 600;
			text-align: center;
		}
		.alert-success {
			background: #d1fae5;
			color: #065f46;
			border: 2px solid #a7f3d0;
		}
		.alert-error {
			background: #fee2e2;
			color: #b91c1c;
			border: 2px solid #fecaca;
		}
		.back-link {
			text-align: center;
			margin-top: 8px;
		}
		.back-link a {
			color: #7d0000;
			text-decoration: none;
			font-weight: 600;
			transition: all 0.2s ease;
		}
		.back-link a:hover {
			color: #5a0000;
			text-decoration: underline;
		}
		.appointment-info {
			background: #f9fafb;
			border: 1px solid #e5e7eb;
			border-radius: 12px;
			padding: 16px;
			margin-bottom: 8px;
		}
		.appointment-info p {
			margin: 4px 0;
			font-size: 0.9rem;
			color: #6b7280;
		}
		.appointment-info strong {
			color: #374151;
		}
		@media (max-width: 900px) {
			.feedback-shell {
				grid-template-columns: 1fr;
			}
			.feedback-sidebar {
				min-height: 200px;
				padding: 32px 24px;
			}
			.feedback-icon {
				width: 80px;
				height: 80px;
				font-size: 40px;
			}
		}
	</style>
</head>
<body>
	<section class="feedback-shell">
		<aside class="feedback-sidebar">
			<div class="feedback-icon">💬</div>
			<h2>Share Your Experience</h2>
			<p>Your feedback helps us improve our services and provide better support to all students.</p>
		</aside>

		<div class="feedback-main">
			<h1><?= $existingFeedback ? 'EDIT FEEDBACK' : 'SUBMIT FEEDBACK' ?></h1>

			<?php if ($error): ?>
				<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
			<?php endif; ?>

			<?php if ($success): ?>
				<div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
				<div class="back-link">
					<a href="client_dashboard.html">← Back to Dashboard</a>
				</div>
			<?php elseif ($appointment_id > 0 && $appointment): ?>
				<div class="appointment-info">
					<p><strong>Office:</strong> <?= htmlspecialchars($appointment['office_name'] ?? 'N/A') ?></p>
					<p><strong>Date:</strong> <?= htmlspecialchars($appointment['appointment_date']) ?></p>
					<?php if ($appointment['appointment_time']): ?>
						<p><strong>Time:</strong> <?= htmlspecialchars($appointment['appointment_time']) ?></p>
					<?php endif; ?>
				</div>

				<?php if ($existingFeedback): ?>
					<div style="background:#e0f2fe;border:2px solid #0ea5e9;border-radius:12px;padding:16px;margin-bottom:20px;">
						<p style="margin:0;color:#0c4a6e;font-weight:600;font-size:0.9rem;">📝 Editing existing feedback</p>
					</div>
				<?php endif; ?>

				<form class="feedback-form" method="POST" action="submit_feedback.php">
					<input type="hidden" name="appointment_id" value="<?= $appointment_id ?>">
					
					<div class="form-group">
						<label>Full Name</label>
						<input class="input-field" type="text" name="full_name" value="<?= htmlspecialchars($fullName) ?>" readonly>
					</div>

					<div class="form-group">
						<label>Email Address</label>
						<input class="input-field" type="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" readonly>
					</div>

					<div class="form-group">
						<label>Your Message</label>
						<textarea class="input-field" name="comment" placeholder="Tell us about your experience with this appointment..." required><?= htmlspecialchars($existingFeedback['comment'] ?? '') ?></textarea>
					</div>

					<div class="rating-group">
						<label class="rating-label">Rating</label>
						<div class="star-rating" id="starRating">
							<button type="button" class="star-btn" data-rating="1">⭐</button>
							<button type="button" class="star-btn" data-rating="2">⭐</button>
							<button type="button" class="star-btn" data-rating="3">⭐</button>
							<button type="button" class="star-btn" data-rating="4">⭐</button>
							<button type="button" class="star-btn" data-rating="5">⭐</button>
						</div>
						<input type="hidden" name="rating" id="ratingValue" value="<?= $existingFeedback['rating'] ?? '' ?>" required>
					</div>

					<button type="submit" name="submit_feedback" class="submit-btn"><?= $existingFeedback ? 'Update Feedback' : 'Submit Feedback' ?></button>
				</form>

				<div class="back-link">
					<a href="client_dashboard.html">← Back to Dashboard</a>
				</div>
			<?php else: ?>
				<div class="back-link">
					<a href="client_dashboard.html">← Back to Dashboard</a>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<script>
		const starButtons = document.querySelectorAll('.star-btn');
		const ratingInput = document.getElementById('ratingValue');
		
		// Initialize with existing rating if available
		const existingRating = ratingInput.value ? parseInt(ratingInput.value) : 0;
		if (existingRating > 0 && existingRating <= 5) {
			starButtons.forEach((star, index) => {
				if (index < existingRating) {
					star.classList.add('active');
				}
			});
		}
		
		starButtons.forEach(btn => {
			btn.addEventListener('click', function() {
				const rating = parseInt(this.getAttribute('data-rating'));
				ratingInput.value = rating;
				
				// Remove active class from all stars first
				starButtons.forEach(star => {
					star.classList.remove('active');
				});
				
				// Add active class to all stars up to and including the clicked one
				starButtons.forEach((star, index) => {
					// index is 0-based, rating is 1-based
					// So if rating is 3, we want to highlight stars at index 0, 1, 2 (1st, 2nd, 3rd)
					if (index < rating) {
						star.classList.add('active');
					}
				});
			});
		});

		// Validate form before submission
		const form = document.querySelector('.feedback-form');
		if (form) {
			form.addEventListener('submit', function(e) {
				if (!ratingInput.value || ratingInput.value === '0') {
					e.preventDefault();
					alert('Please select a rating before submitting.');
					return false;
				}
			});
		}
	</script>
</body>
</html>
