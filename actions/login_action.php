<?php
/**
 * EcoSprout – Login Action Handler
 * POST: email, password
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl() . '/login.php');
}

// CSRF check
verifyCsrf();

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

// Basic server-side validation
if (empty($email) || empty($password)) {
    setFlash('error', 'Please enter your email and password.');
    redirect(getBaseUrl() . '/login.php');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Invalid email address format.');
    redirect(getBaseUrl() . '/login.php');
}

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, role, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Generic error message to avoid user enumeration
    $genericError = 'Invalid email address or password. Please try again.';

    if (!$user) {
        setFlash('error', $genericError);
        redirect(getBaseUrl() . '/login.php');
    }

    // Verify password hash
    if (!password_verify($password, $user['password'])) {
        setFlash('error', $genericError);
        redirect(getBaseUrl() . '/login.php');
    }

    // Check account status
    if ($user['status'] === 'disabled') {
        setFlash('error', 'Your account has been disabled. Please contact the administrator at info@ecosprout.lk.');
        redirect(getBaseUrl() . '/login.php');
    }

    // ── Authentication successful ──
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']     = (int) $user['id'];
    $_SESSION['user_name']   = $user['full_name'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['user_status'] = $user['status'];

    // Role-based redirect
    $dest = match($user['role']) {
        'admin' => '/admin/dashboard.php',
        'staff' => '/staff/dashboard.php',
        default => '/customer/dashboard.php',
    };

    setFlash('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');
    redirect(getBaseUrl() . $dest);

} catch (PDOException $e) {
    // Log error in production; show generic message to user
    error_log('[EcoSprout] Login error: ' . $e->getMessage());
    setFlash('error', 'A system error occurred. Please try again.');
    redirect(getBaseUrl() . '/login.php');
}
