<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login - CJC School Frontline Services</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(120deg, #212529 0%, #343a40 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-login-card {
            background: rgba(255,255,255,0.92);
            border-radius: 1.5rem;
            box-shadow: 0 8px 32px 0 rgba(31,41,55,0.18);
            max-width: 400px;
            margin: 0 auto;
            padding: 2.5rem 2rem 2rem 2rem;
        }
        .admin-login-card h2 {
            font-weight: 700;
            color: #212529;
            margin-bottom: 1.5rem;
        }
        .form-label {
            color: #374151;
            font-weight: 500;
        }
        .btn-admin-login {
            background: #212529;
            color: #fff;
            font-weight: 600;
            border: none;
        }
        .btn-admin-login:hover {
            background: #343a40;
            color: #ffc107;
        }
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 0.2rem rgba(99,102,241,0.15);
        }
        .back-link {
            color: #212529;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
            color: #343a40;
        }
        @media (max-width: 500px) {
            .admin-login-card {
                padding: 1.5rem 0.5rem 1rem 0.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="admin-login-card shadow-lg">
            <h2 class="text-center mb-4">Admin Login</h2>
            <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
                <div class="alert alert-danger text-center py-2 mb-3" role="alert" style="font-size: 0.95rem;">
                    Invalid name, email, or password. Please try again.
                </div>
            <?php endif; ?>
            <form method="POST" action="admin_login_process.php">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required autocomplete="name">
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin Email</label>
                    <input type="email" name="email" class="form-control" required autocomplete="username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                            Show
                        </button>
                    </div>
                </div>
                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-admin-login btn-lg">Log In as Admin</button>
                </div>
                <div class="text-center">
                    <a href="login.html" class="back-link">&larr; Back to User Login</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('togglePassword');
            toggleBtn.addEventListener('click', function() {
                if (passwordInput.type === "password") {
                    passwordInput.type = "text";
                    toggleBtn.textContent = "Hide";
                } else {
                    passwordInput.type = "password";
                    toggleBtn.textContent = "Show";
                }
            });
        });
    </script>
</body>
</html>