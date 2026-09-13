<?php
/**
 * EcoSprout – Login Page
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect already-logged-in users
if (isLoggedIn()) {
    $dest = match($_SESSION['user_role']) {
        'admin' => '/admin/dashboard.php',
        'staff' => '/staff/dashboard.php',
        default => '/customer/dashboard.php',
    };
    redirect(getBaseUrl() . $dest);
}

$base = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | EcoSprout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Logo -->
        <div class="auth-logo">
            <div class="logo-icon"><i class="bi bi-tree-fill"></i></div>
            <h2>Welcome Back</h2>
            <p>Sign in to your EcoSprout account</p>
        </div>

        <!-- Flash messages -->
        <?php foreach (getFlashes() as $type => $msgs): ?>
            <?php foreach ($msgs as $msg): ?>
                <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>

        <!-- Login Form -->
        <form action="<?= $base ?>/actions/login_action.php" method="POST" class="needs-validation" novalidate>
            <?php csrfField(); ?>

            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope me-1"></i> Email Address
                </label>
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="you@example.com"
                       value="<?= htmlspecialchars($_GET['email'] ?? '') ?>"
                       required autocomplete="email">
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-1"></i> Password
                </label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your password" required autocomplete="current-password">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                            title="Show/hide password" onclick="togglePwd()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Password is required.</div>
            </div>

            <button type="submit" class="btn btn-eco-primary w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>

        <p class="text-center text-muted small mb-0">
            Don't have an account?
            <a href="<?= $base ?>/register.php" class="fw-semibold">Create one free</a>
        </p>
        <p class="text-center mt-3 mb-0">
            <a href="<?= $base ?>/index.php" class="text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Homepage
            </a>
        </p>

        <!-- Demo accounts hint -->
        <div class="mt-4 p-3 rounded-3" style="background:var(--eco-gray-100);font-size:0.78rem;">
            <div class="fw-semibold mb-1" style="color:var(--eco-primary);">
                <i class="bi bi-info-circle me-1"></i> Demo Accounts
            </div>
            <div><strong>Admin:</strong> admin@ecosprout.lk / Admin@123</div>
            <div><strong>Staff:</strong> staff@ecosprout.lk / Staff@123</div>
            <div><strong>Customer:</strong> customer@ecosprout.lk / Customer@123</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function togglePwd() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>
</body>
</html>
