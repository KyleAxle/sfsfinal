<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Admin Login • CJC School Frontline Services</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="../assets/css/login.css">
	<style>
		.login-sidebar {
			background: linear-gradient(135deg, #5a0000 0%, #7d0000 50%, #5a0000 100%);
			position: relative;
			overflow: hidden;
		}
		.login-sidebar::before {
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
		.login-sidebar p {
			font-size: 1.2rem;
			font-weight: 500;
			letter-spacing: 0.5px;
			text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
		}
		.login-main h1 {
			font-size: 2.5rem;
			font-weight: 700;
			letter-spacing: 0.15em;
			background: linear-gradient(135deg, #5a0000 0%, #7d0000 50%, #9d0000 100%);
			-webkit-background-clip: text;
			-webkit-text-fill-color: transparent;
			background-clip: text;
			text-shadow: 0 2px 8px rgba(125, 0, 0, 0.15);
			margin-bottom: 2rem;
		}
		.login-submit {
			background: linear-gradient(135deg, #5a0000 0%, #7d0000 50%, #5a0000 100%);
			box-shadow: 0 8px 24px rgba(125, 0, 0, 0.4), 0 4px 8px rgba(0, 0, 0, 0.2);
			font-weight: 700;
			letter-spacing: 0.1em;
			text-transform: uppercase;
			font-size: 1rem;
			transition: all 0.3s ease;
		}
		.login-submit:hover {
			background: linear-gradient(135deg, #4a0000 0%, #6d0000 50%, #4a0000 100%);
			box-shadow: 0 12px 32px rgba(125, 0, 0, 0.5), 0 6px 12px rgba(0, 0, 0, 0.3);
			transform: translateY(-2px);
		}
		.login-submit:active {
			transform: translateY(0);
		}
		.input-field:focus {
			border-color: #7d0000;
			box-shadow: 0 0 0 4px rgba(125, 0, 0, 0.15);
		}
		.login-links a {
			color: #7d0000;
			font-weight: 600;
			transition: all 0.2s ease;
		}
		.login-links a:hover {
			color: #5a0000;
			text-decoration: underline;
		}
		.admin-badge {
			display: inline-block;
			background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
			color: #5a0000;
			padding: 6px 16px;
			border-radius: 20px;
			font-size: 0.75rem;
			font-weight: 700;
			letter-spacing: 0.1em;
			text-transform: uppercase;
			margin-bottom: 1rem;
			box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
		}
		.login-error {
			background: #fee2e2;
			color: #b91c1c;
			border: 2px solid #fecaca;
			border-radius: 12px;
			padding: 12px 16px;
			margin-bottom: 20px;
			font-weight: 600;
			text-align: center;
		}
		.menu-icon {
			color: rgba(255, 255, 255, 0.9);
			text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
		}
		.login-logo {
			filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
		}
	</style>
</head>
<body>
	<div class="overlay" id="overlay"></div>

	<div class="sidebar-menu" id="sidebarMenu">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
			<h2 style="margin: 0;">Quick Links</h2>
			<button id="closeSidebar" style="background: none; border: none; font-size: 1.5rem; color: var(--text); cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: background 0.2s;" title="Close">×</button>
		</div>
		<a href="https://www.cjc.edu.ph" target="_blank" rel="noopener">CJC Main Page</a>
		<a href="../login.html">Student Login</a>
		<a href="../staff_login.html">Staff Login</a>
		<a href="admin_login.php">Admin Login</a>
	</div>

	<section class="login-shell">
		<aside class="login-sidebar">
			<div class="menu-icon" id="menuTrigger">&#9776;</div>
			<p style="font-size:1.2rem;line-height:1.6;margin-top:auto;font-weight:500;">
				Welcome to the CJC School Frontline Services<br>
				<strong style="font-weight:700;font-size:1.3rem;">Administrative Portal</strong>
			</p>
			<img class="login-logo" src="../img/cjclogo.png" alt="CJC Logo">
		</aside>

		<div class="login-main">
			<div class="admin-badge">🔐 ADMINISTRATOR ACCESS</div>
			<h1>ADMIN PORTAL LOGIN</h1>

			<form class="login-form" method="POST" action="admin_login_process.php">
				<?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
					<div class="login-error">
						Invalid name, email, or password. Please try again.
					</div>
				<?php endif; ?>

				<input class="input-field" type="text" name="name" placeholder="Admin Name" required autocomplete="name">
				<input class="input-field" type="email" name="email" placeholder="Admin Email" required autocomplete="username">
				<input class="input-field" type="password" name="password" id="password" placeholder="Password" required autocomplete="current-password">

				<button class="login-submit" type="submit">Access Admin Dashboard</button>

				<div class="login-links">
					<a href="../login.html">Student Login</a>
					<a href="../staff_login.html">Staff Login</a>
				</div>
				<div class="login-links" style="gap:16px;">
					<a href="../register.html">Create Account</a>
					<a href="../proto2.html">Book Appointment</a>
				</div>
			</form>
		</div>
	</section>

	<script>
		const menuTrigger = document.getElementById('menuTrigger');
		const sidebarMenu = document.getElementById('sidebarMenu');
		const overlay = document.getElementById('overlay');
		const closeSidebar = document.getElementById('closeSidebar');

		function toggleMenu(open) {
			if (open) {
				sidebarMenu.classList.add('open');
				overlay.classList.add('show');
			} else {
				sidebarMenu.classList.remove('open');
				overlay.classList.remove('show');
			}
		}

		menuTrigger.addEventListener('click', () => toggleMenu(true));
		overlay.addEventListener('click', () => toggleMenu(false));
		if (closeSidebar) {
			closeSidebar.addEventListener('click', () => toggleMenu(false));
			closeSidebar.addEventListener('mouseenter', function() {
				this.style.background = '#f3f4f6';
			});
			closeSidebar.addEventListener('mouseleave', function() {
				this.style.background = 'none';
			});
		}

		// Password visibility toggle
		const passwordInput = document.getElementById('password');
		if (passwordInput) {
			// Add show/hide functionality if needed
			passwordInput.addEventListener('focus', function() {
				this.style.borderColor = '#7d0000';
			});
		}
	</script>
</body>
</html>
