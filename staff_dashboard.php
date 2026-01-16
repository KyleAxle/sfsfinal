<?php
require_once __DIR__ . '/config/session.php';

if (!isset($_SESSION['staff_id'], $_SESSION['office_id'])) {
	header("Location: staff_login.html");
	exit;
}

$pdo = require __DIR__ . '/config/db.php';

$officeId = (int)$_SESSION['office_id'];
$officeName = $_SESSION['office_name'] ?? 'Office';
$staffEmail = $_SESSION['staff_email'] ?? '';

$appointmentsStmt = $pdo->prepare("
	select
		a.appointment_id,
		ao.office_id,
		u.user_id,
		u.first_name,
		u.last_name,
		u.email,
		a.appointment_date,
		a.appointment_time,
		a.concern,
		a.status
	from public.appointments a
	join public.appointment_offices ao on ao.appointment_id = a.appointment_id
	join public.users u on u.user_id = a.user_id
	where ao.office_id = ?
	order by a.appointment_date desc, a.appointment_time desc
");
$appointmentsStmt->execute([$officeId]);
$appointments = $appointmentsStmt->fetchAll(PDO::FETCH_ASSOC);

$blockedStmt = $pdo->prepare("
	select block_id, block_date, start_time, end_time, coalesce(reason, '') as reason
	from public.office_blocked_slots
	where office_id = ?
	order by block_date asc, start_time asc
");
$blockedStmt->execute([$officeId]);
$blockedSlots = $blockedStmt->fetchAll(PDO::FETCH_ASSOC);

function normalizeStatusValue(?string $status): string {
	$normalized = strtolower((string)$status);
	if ($normalized === 'accepted') {
		return 'approved';
	}
	if ($normalized === 'rejected') {
		return 'declined';
	}
	return $normalized ?: 'pending';
}

$appointments = array_map(function (array $appt): array {
	$appt['status'] = normalizeStatusValue($appt['status'] ?? '');
	return $appt;
}, $appointments);

function summarizeStatus(array $appointments, string $status): int {
	$count = 0;
	foreach ($appointments as $appt) {
		if (strtolower((string)$appt['status']) === $status) {
			$count++;
		}
	}
	return $count;
}

$pendingCount = summarizeStatus($appointments, 'pending');
$approvedCount = summarizeStatus($appointments, 'approved');
$declinedCount = summarizeStatus($appointments, 'declined');
$completedCount = summarizeStatus($appointments, 'completed');

$timeOptions = [];
$start = new DateTime('09:00');
$end = new DateTime('16:00');
while ($start < $end) {
	$timeOptions[] = [
		'value' => $start->format('H:i:s'),
		'label' => $start->format('h:i A')
	];
	$start->modify('+30 minutes');
}

$jsonAppointments = json_encode($appointments, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$jsonBlocked = json_encode($blockedSlots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$jsonTimes = json_encode($timeOptions, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo htmlspecialchars($officeName); ?> Staff Dashboard</title>
	<link rel="stylesheet" href="assets/css/staff_dashboard.css">
</head>
<body>
	<div class="staff-shell">
		<aside class="staff-sidebar">
			<img src="img/cjclogo.png" alt="CJC Logo">
			<nav>
				<a href="staff_dashboard.php" class="active">Appointments</a>
			</nav>
			<button class="logout-btn" id="staffLogoutBtn" style="margin-top:auto;background:#fff;color:var(--primary);border:none;border-radius:24px;padding:10px 20px;cursor:pointer;">Sign Out</button>
		</aside>

		<main class="staff-main">
			<header class="staff-header">
				<div>
					<p style="letter-spacing:0.25em;text-transform:uppercase;font-weight:600;margin-bottom:6px;">Staff Access</p>
					<h1><?php echo htmlspecialchars(strtoupper($officeName)); ?></h1>
				</div>
				<div style="text-align:right;">
					<p style="font-weight:600;">Welcome, <?php echo htmlspecialchars($staffEmail ?: 'Staff'); ?></p>
					<p style="font-size:0.9rem;opacity:0.85;">Handle appointments & availability</p>
				</div>
			</header>

			<section class="summary-grid">
				<div class="summary-card">
					<span class="summary-label">Pending</span>
					<span class="summary-value" id="summaryPending"><?php echo $pendingCount; ?></span>
				</div>
				<div class="summary-card">
					<span class="summary-label">Approved</span>
					<span class="summary-value" id="summaryApproved"><?php echo $approvedCount; ?></span>
				</div>
				<div class="summary-card">
					<span class="summary-label">Declined</span>
					<span class="summary-value" id="summaryDeclined"><?php echo $declinedCount; ?></span>
				</div>
				<div class="summary-card">
					<span class="summary-label">Completed</span>
					<span class="summary-value" id="summaryCompleted"><?php echo $completedCount; ?></span>
				</div>
			</section>

			<section class="panel" id="appointmentsSection">
				<div class="filter-row">
					<h2>Appointments</h2>
					<select id="statusFilter">
						<option value="all">All</option>
						<option value="pending">Pending</option>
						<option value="approved">Approved</option>
						<option value="declined">Declined</option>
						<option value="completed">Completed</option>
					</select>
				</div>

				<div class="appointment-list" id="appointmentList"></div>
			</section>


			<section class="panel" id="blockSlotsSection">
				<h2>Block Time Slots</h2>
				<div class="block-manager">
					<form id="blockForm" class="block-form">
						<label>Date</label>
						<input type="date" name="block_date" id="blockDate" required>

						<label>Start Time</label>
						<select name="start_time" id="blockStart" required></select>

						<label>End Time</label>
						<select name="end_time" id="blockEnd" required></select>

						<label>Reason (optional)</label>
						<textarea name="reason" id="blockReason" rows="3" placeholder="Maintenance, event, etc."></textarea>

						<button type="submit">Block Slot</button>
					</form>

					<div>
						<h3 style="margin-bottom:12px;">Existing Blocks</h3>
						<div class="blocked-list" id="blockedList"></div>
					</div>
				</div>
			</section>

		</main>
	</div>

	<!-- Chat Modal -->
	<div id="chatModal" class="message-overlay">
		<div class="message-panel">
			<div class="message-header">
				<div>
					<p id="chatModalTitle">Message</p>
					<h2 id="chatModalName">User</h2>
				</div>
				<button class="message-close" id="closeChatModal">&times;</button>
			</div>
			<div class="message-body" id="chatModalMessages">
				<div class="message-empty">Loading messages...</div>
			</div>
			<div class="message-form">
				<textarea id="chatModalInput" placeholder="Type your message..." rows="3"></textarea>
				<div class="message-actions">
					<span class="message-hint">Press Enter to send, Shift+Enter for new line</span>
					<button class="message-send" id="chatModalSendBtn">Send</button>
				</div>
			</div>
		</div>
	</div>

	<script>
		const appointments = <?php echo $jsonAppointments; ?> || [];
		let blockedSlots = <?php echo $jsonBlocked; ?> || [];
		const timeOptions = <?php echo $jsonTimes; ?> || [];

		const statusFilter = document.getElementById('statusFilter');
		const appointmentList = document.getElementById('appointmentList');
		const summaryEls = {
			pending: document.getElementById('summaryPending'),
			approved: document.getElementById('summaryApproved'),
			declined: document.getElementById('summaryDeclined'),
			completed: document.getElementById('summaryCompleted')
		};

		const blockForm = document.getElementById('blockForm');
		const blockStart = document.getElementById('blockStart');
		const blockEnd = document.getElementById('blockEnd');
		const blockedList = document.getElementById('blockedList');
		const blockDateInput = document.getElementById('blockDate');
		const logoutBtn = document.getElementById('staffLogoutBtn');

		const today = new Date().toISOString().split('T')[0];
		blockDateInput.value = today;

		const normalizeStatus = (value) => {
			const raw = (value || 'pending').toString().toLowerCase();
			if (raw === 'accepted') return 'approved';
			if (raw === 'rejected') return 'declined';
			return raw || 'pending';
		};

		appointments.forEach(appt => {
			appt.status = normalizeStatus(appt.status);
		});

		function populateTimeSelects() {
			blockStart.innerHTML = '';
			blockEnd.innerHTML = '';
			timeOptions.forEach((opt, idx) => {
				const startOpt = document.createElement('option');
				startOpt.value = opt.value;
				startOpt.textContent = opt.label;
				blockStart.appendChild(startOpt);

				if (idx > 0) {
					const endOpt = document.createElement('option');
					endOpt.value = opt.value;
					endOpt.textContent = opt.label;
					blockEnd.appendChild(endOpt);
				}
			});
			// add closing slot (last + 30 mins)
			if (timeOptions.length) {
				const last = timeOptions[timeOptions.length - 1];
				const dt = new Date(`1970-01-01T${last.value}`);
				dt.setMinutes(dt.getMinutes() + 30);
				const endOpt = document.createElement('option');
				endOpt.value = dt.toTimeString().slice(0,8);
				endOpt.textContent = dt.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
				blockEnd.appendChild(endOpt);
			}
			// Ensure defaults
			if (blockStart.options.length) blockStart.selectedIndex = 0;
			if (blockEnd.options.length) blockEnd.selectedIndex = Math.min(2, blockEnd.options.length - 1);
		}

		populateTimeSelects();

		if (logoutBtn) {
			logoutBtn.addEventListener('click', async () => {
				try {
					await fetch('logout.php', { method: 'POST', credentials: 'same-origin' });
				} catch (err) {
					console.warn('Logout request failed, continuing to redirect.', err);
				} finally {
					window.location.href = 'staff_login.html';
				}
			});
		}

		statusFilter.addEventListener('change', renderAppointments);

		function renderAppointments() {
			const filter = statusFilter.value;
			if (!appointments.length) {
				appointmentList.innerHTML = '<p>No appointments yet.</p>';
				return;
			}

			const filtered = appointments.filter(appt => {
				const status = normalizeStatus(appt.status);
				return filter === 'all' ? true : status === filter;
			});

			appointmentList.innerHTML = filtered.map(appt => {
				const status = normalizeStatus(appt.status);
				const fullName = `${appt.first_name || ''} ${appt.last_name || ''}`.trim() || 'Unknown';
				return `
					<article class="staff-card">
						<header>
							<div>
								<h3>${appt.concern ? appt.concern : 'No concern provided'}</h3>
								<p style="color:var(--muted);font-size:0.9rem;">${fullName} - ${appt.email || ''}</p>
							</div>
							<span class="status-pill status-${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
						</header>
						<div class="details-grid">
							<div class="detail">
								<label>Date</label>
								<span>${appt.appointment_date}</span>
							</div>
							<div class="detail">
								<label>Time</label>
								<span>${appt.appointment_time || '--'}</span>
							</div>
						</div>
						<div class="card-actions">
							${renderActions(appt)}
						</div>
					</article>
				`;
			}).join('');
		}

		function renderActions(appt) {
			const status = normalizeStatus(appt.status);
			const id = Number(appt.appointment_id);
			const userId = Number(appt.user_id);
			const userName = `${appt.first_name || ''} ${appt.last_name || ''}`.trim() || appt.email || 'User';
			const safeUserName = userName.replace(/'/g, "\\'").replace(/"/g, '&quot;');

			const buttons = [];
			const addButton = (label, className, nextStatus, showModal = false) => {
				if (showModal) {
					buttons.push(`<button class="${className}" onclick="showMessageModal(${id}, '${nextStatus}', '${label}')">${label}</button>`);
				} else {
					buttons.push(`<button class="${className}" onclick="updateStatus(${id}, '${nextStatus}')">${label}</button>`);
				}
			};

			// Add message button - use both onclick and data attributes for maximum compatibility
			buttons.push(`<button class="btn-message message-staff-btn" data-user-id="${userId}" data-user-name="${safeUserName}" data-appointment-id="${id}" title="Message User" onclick="openChatModalFromButton(this)">💬</button>`);

			if (status === 'pending') {
				addButton('Accept', 'btn-approve', 'approved', false);
				addButton('Decline', 'btn-decline', 'declined', false);
			} else if (status === 'approved') {
				addButton('Mark Completed', 'btn-complete', 'completed', false);
				addButton('Decline', 'btn-decline', 'declined', false);
			} else if (status === 'declined') {
				addButton('Move to Pending', 'btn-pending', 'pending', false);
			}
			// Removed reopen button for completed appointments

			return buttons.join('');
		}

		async function updateStatus(id, status) {
			console.log('📝 Updating appointment status:', { appointment_id: id, status });
			
			try {
				const res = await fetch('staff_update_appointment.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ appointment_id: id, status }),
					credentials: 'same-origin'
				});
				
				console.log('📥 Response received:', {
					status: res.status,
					statusText: res.statusText,
					ok: res.ok,
					contentType: res.headers.get('content-type')
				});
				
				// Check if response is actually JSON
				const contentType = res.headers.get('content-type');
				if (!contentType || !contentType.includes('application/json')) {
					const text = await res.text();
					console.error('❌ Non-JSON response:', text);
					throw new Error('Server returned invalid response. Please check the console.');
				}
				
				const data = await res.json();
				console.log('📋 Response data:', data);
				
				if (!data.success) {
					console.error('❌ Update failed:', data.error);
					throw new Error(data.error || 'Unable to update status');
				}
				
				const appt = appointments.find(a => Number(a.appointment_id) === Number(id));
				if (appt) {
					appt.status = normalizeStatus(data.status ?? status);
				}
				updateSummaries();
				renderAppointments();
				
				// Show success popup if appointment was accepted
				if (status === 'approved' || status === 'accepted') {
					console.log('✅ Appointment accepted. SMS status:', {
						sms_sent: data.sms_sent,
						sms_error: data.sms_error || 'none'
					});
					
					// Only show SMS message if SMS was actually sent successfully
					// Check explicitly for true (not just truthy)
					if (data.sms_sent === true) {
						console.log('✅ SMS notification sent successfully!');
						alert('Appointment accepted! SMS notification has been sent to the user.');
					} else {
						// SMS was not sent (no phone number, API error, or sms_sent is false/undefined)
						console.warn('⚠️ SMS was not sent:', data.sms_error || 'Unknown reason');
						if (data.sms_error) {
							alert('Appointment accepted! However, SMS could not be sent: ' + data.sms_error);
						} else {
							alert('Appointment accepted! (SMS notification was not sent - user may not have a phone number)');
						}
					}
				}
			} catch (err) {
				console.error('❌ Update status error:', err);
				alert(err.message || 'An error occurred while updating the appointment.');
			}
		}

		function updateSummaries() {
			const counts = { pending:0, approved:0, declined:0, completed:0 };
			appointments.forEach(appt => {
				const status = normalizeStatus(appt.status);
				if (counts[status] !== undefined) counts[status]++;
			});
			Object.entries(counts).forEach(([key, value]) => {
				if (summaryEls[key]) summaryEls[key].textContent = value;
			});
		}

		blockStart.addEventListener('change', syncEndOptions);
		function syncEndOptions() {
			const startIdx = blockStart.selectedIndex;
			Array.from(blockEnd.options).forEach((opt, idx) => {
				opt.disabled = idx <= startIdx;
			});
			if (blockEnd.selectedIndex <= startIdx) {
				const nextIdx = Math.min(startIdx + 1, blockEnd.options.length - 1);
				blockEnd.selectedIndex = nextIdx;
			}
		}
		syncEndOptions();

		blockForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const formData = new FormData(blockForm);
			try {
				const res = await fetch('block_office_slot.php', {
					method: 'POST',
					body: new URLSearchParams(formData)
				});
				const data = await res.json();
				if (!data.success) throw new Error(data.error || 'Failed to block slot');
				blockedSlots.push(data.block);
				renderBlocked();
				blockForm.reset();
				blockDateInput.value = today;
				populateTimeSelects();
				syncEndOptions();
				alert('Time slot blocked successfully.');
			} catch (err) {
				alert(err.message);
			}
		});

		function renderBlocked() {
			if (!blockedSlots.length) {
				blockedList.innerHTML = '<p style="color:var(--muted);">No blocked slots.</p>';
				return;
			}
			blockedList.innerHTML = blockedSlots.map(block => `
				<div class="blocked-item">
					<div>
						<p style="font-weight:600;">${block.block_date} (${block.start_time} - ${block.end_time})</p>
						<p style="font-size:0.85rem;color:var(--muted);">${block.reason || 'No reason provided'}</p>
					</div>
					<button onclick="removeBlock(${block.block_id})">Remove</button>
				</div>
			`).join('');
		}

		async function removeBlock(id) {
			if (!confirm('Remove this block?')) return;
			try {
				const res = await fetch('delete_blocked_slot.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ block_id: id })
				});
				const data = await res.json();
				if (!data.success) throw new Error(data.error || 'Failed to remove block');
				blockedSlots = blockedSlots.filter(block => Number(block.block_id) !== Number(id));
				renderBlocked();
				alert('Block removed.');
			} catch (err) {
				alert(err.message);
			}
		}

		window.removeBlock = removeBlock;
		window.updateStatus = updateStatus;

		// Chat Modal functionality
		const chatModal = document.getElementById('chatModal');
		const closeChatModal = document.getElementById('closeChatModal');
		const chatModalMessages = document.getElementById('chatModalMessages');
		const chatModalInput = document.getElementById('chatModalInput');
		const chatModalSendBtn = document.getElementById('chatModalSendBtn');
		const chatModalName = document.getElementById('chatModalName');
		const chatModalTitle = document.getElementById('chatModalTitle');

		let currentChatUserId = null;
		let currentChatAppointmentId = null;
		let chatPollInterval = null;

		closeChatModal.addEventListener('click', () => {
			chatModal.classList.remove('active');
			if (chatPollInterval) {
				clearInterval(chatPollInterval);
				chatPollInterval = null;
			}
		});

		chatModal.addEventListener('click', (e) => {
			if (e.target === chatModal) {
				chatModal.classList.remove('active');
				if (chatPollInterval) {
					clearInterval(chatPollInterval);
					chatPollInterval = null;
				}
			}
		});

		window.openChatModal = function(userId, userName, appointmentId) {
			console.log('openChatModal called with:', userId, userName, appointmentId);
			if (!userId) {
				console.error('No user ID provided');
				alert('Error: Missing user ID');
				return;
			}
			currentChatUserId = userId;
			currentChatAppointmentId = appointmentId;
			chatModalTitle.textContent = 'Message User';
			chatModalName.textContent = userName || 'User';
			chatModalMessages.innerHTML = '<div class="message-empty">Loading...</div>';
			chatModalInput.value = '';
			// Remove any inline display:none and add active class for CSS visibility
			chatModal.style.display = '';
			chatModal.classList.add('active');
			console.log('Modal opened - active class added');
			loadChatMessages();
			startChatPolling();
		};

		// Helper function for onclick handler - must be defined after openChatModal
		window.openChatModalFromButton = function(button) {
			console.log('openChatModalFromButton called');
			const userId = button.getAttribute('data-user-id');
			const userName = button.getAttribute('data-user-name');
			const appointmentId = button.getAttribute('data-appointment-id');
			console.log('Button data:', { userId, userName, appointmentId });
			if (userId && window.openChatModal) {
				window.openChatModal(Number(userId), userName, Number(appointmentId));
			} else {
				console.error('Missing user ID or openChatModal function');
				alert('Error: Could not find user ID or function');
			}
		};

		// Helper function for onclick handler
		window.openChatModalFromButton = function(button) {
			const userId = button.getAttribute('data-user-id');
			const userName = button.getAttribute('data-user-name');
			const appointmentId = button.getAttribute('data-appointment-id');
			if (userId && window.openChatModal) {
				window.openChatModal(Number(userId), userName, Number(appointmentId));
			} else {
				console.error('Missing user ID or openChatModal function');
			}
		};

		function loadChatMessages() {
			if (!currentChatUserId) {
				console.log('No user ID, skipping loadChatMessages');
				return;
			}
			
			console.log('Loading messages for user ID:', currentChatUserId);
			fetch(`get_messages.php?other_type=user&other_id=${currentChatUserId}`)
				.then(res => {
					console.log('Response status:', res.status);
					if (!res.ok) {
						return res.text().then(text => {
							console.error('Error response:', text);
							throw new Error(`HTTP ${res.status}: ${text}`);
						});
					}
					return res.json();
				})
				.then(data => {
					console.log('Messages data:', data);
					if (data.success && data.messages) {
						if (data.messages.length === 0) {
							chatModalMessages.innerHTML = '<div class="message-empty">No messages yet. Start the conversation!</div>';
							return;
						}
						chatModalMessages.innerHTML = data.messages.map(msg => {
							const isStaff = msg.sender_type === 'staff';
							const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
							return `
								<div class="message-bubble ${isStaff ? 'staff-message' : 'user-message'}">
									<div class="message-meta">
										<span>${msg.sender_name || (isStaff ? 'You' : 'User')}</span>
										<span>${time}</span>
									</div>
									<p>${msg.message}</p>
								</div>
							`;
						}).join('');
						chatModalMessages.scrollTop = chatModalMessages.scrollHeight;
					} else if (data.error) {
						console.error('Error from server:', data.error);
						chatModalMessages.innerHTML = `<div class="message-empty" style="color:#dc2626;">Error: ${data.error}</div>`;
					}
				})
				.catch(err => {
					console.error('Error loading messages:', err);
					chatModalMessages.innerHTML = '<div class="message-empty" style="color:#dc2626;">Error loading messages. Check console for details.</div>';
				});
		}

		chatModalSendBtn.addEventListener('click', sendChatMessage);
		chatModalInput.addEventListener('keypress', (e) => {
			if (e.key === 'Enter' && !e.shiftKey) {
				e.preventDefault();
				sendChatMessage();
			}
		});

		function sendChatMessage() {
			const message = chatModalInput.value.trim();
			if (!message || !currentChatUserId) return;

			chatModalSendBtn.disabled = true;
			fetch('send_message.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					sender_type: 'staff',
					recipient_type: 'user',
					recipient_id: currentChatUserId,
					message: message
				})
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					chatModalInput.value = '';
					loadChatMessages();
				} else {
					alert('Error sending message: ' + (data.error || 'Unknown error'));
				}
			})
			.catch(err => {
				alert('Error sending message');
			})
			.finally(() => {
				chatModalSendBtn.disabled = false;
			});
		}

		function startChatPolling() {
			if (chatPollInterval) clearInterval(chatPollInterval);
			chatPollInterval = setInterval(() => {
				if (currentChatUserId) {
					loadChatMessages();
				}
			}, 5000); // Reduced frequency: 5 seconds instead of 3
		}

		renderAppointments();
		renderBlocked();
		updateSummaries();
	</script>
</body>
</html>

