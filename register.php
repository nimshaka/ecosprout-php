<?php
/**
 * EcoSprout – Registration Page (Customer accounts only)
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect(getBaseUrl() . '/customer/dashboard.php');
}

$base = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | EcoSprout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-card" style="max-width:520px;">
        <!-- Logo -->
        <div class="auth-logo">
            <div class="logo-icon"><i class="bi bi-tree-fill"></i></div>
            <h2>Join EcoSprout</h2>
            <p>Create your free plant enthusiast account</p>
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

        <!-- Registration Form -->
        <form action="<?= $base ?>/actions/register_action.php" method="POST" class="needs-validation" novalidate>
            <?php csrfField(); ?>

            <!-- Full Name -->
            <div class="mb-3">
                <label for="full_name" class="form-label">
                    <i class="bi bi-person me-1"></i> Full Name
                </label>
                <input type="text" class="form-control" id="full_name" name="full_name"
                       placeholder="Your full name"
                       value="<?= htmlspecialchars($_SESSION['reg_old']['full_name'] ?? '') ?>"
                       required minlength="2" maxlength="120">
                <div class="invalid-feedback">Full name is required (2–120 characters).</div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label">
                    <i class="bi bi-envelope me-1"></i> Email Address
                </label>
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="you@example.com"
                       value="<?= htmlspecialchars($_SESSION['reg_old']['email'] ?? '') ?>"
                       required maxlength="180">
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <!-- Phone -->
            <div class="mb-3">
                <label for="phone" class="form-label">
                    <i class="bi bi-telephone me-1"></i> Phone Number
                    <small class="text-muted">(optional)</small>
                </label>
                <input type="tel" class="form-control" id="phone" name="phone"
                       placeholder="+94 7X XXX XXXX"
                       value="<?= htmlspecialchars($_SESSION['reg_old']['phone'] ?? '') ?>"
                       maxlength="20">
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="bi bi-lock me-1"></i> Password
                </label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Min. 8 characters"
                           required minlength="8" autocomplete="new-password">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('password','eyeIcon1')">
                        <i class="bi bi-eye" id="eyeIcon1"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Password must be at least 8 characters.</div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label for="confirm_password" class="form-label">
                    <i class="bi bi-lock-fill me-1"></i> Confirm Password
                </label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                           placeholder="Repeat your password"
                           required autocomplete="new-password">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePwd('confirm_password','eyeIcon2')">
                        <i class="bi bi-eye" id="eyeIcon2"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Please confirm your password.</div>
            </div>

            <!-- NOTE: Role is always 'customer' for public registration.
                 Staff/Admin accounts are created by Administrators only. -->

            <button type="submit" class="btn btn-eco-primary w-100 py-2 mb-3">
                <i class="bi bi-person-check me-2"></i> Create Account
            </button>
        </form>

        <p class="text-center text-muted small mb-0">
            Already have an account?
            <a href="<?= $base ?>/login.php" class="fw-semibold">Sign in</a>
        </p>
        <p class="text-center mt-3 mb-0">
            <a href="<?= $base ?>/index.php" class="text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Back to Homepage
            </a>
        </p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>/assets/js/script.js"></script>
<script>
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
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
