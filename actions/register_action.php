<?php
/**
 * EcoSprout – Registration Action Handler (Customer accounts only)
 * POST: full_name, email, phone, password, confirm_password
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl() . '/register.php');
}

verifyCsrf();

// ── Collect & sanitize inputs ─────────────────────────────────
$fullName        = trim($_POST['full_name']        ?? '');
$email           = strtolower(trim($_POST['email'] ?? ''));
$phone           = trim($_POST['phone']            ?? '');
$password        = $_POST['password']              ?? '';
$confirmPassword = $_POST['confirm_password']      ?? '';

// Persist old values for redisplay on error
$_SESSION['reg_old'] = compact('fullName', 'email', 'phone');

$errors = [];

// ── Validation ────────────────────────────────────────────────
if (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 120) {
    $errors[] = 'Full name must be between 2 and 120 characters.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}

if (mb_strlen($email) > 180) {
    $errors[] = 'Email address is too long.';
}

if (!empty($phone) && !preg_match('/^\+?[\d\s\-()]{6,20}$/', $phone)) {
    $errors[] = 'Phone number format is invalid.';
}

if (mb_strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters long.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match. Please try again.';
}

if (!empty($errors)) {
    foreach ($errors as $error) {
        setFlash('error', $error);
    }
    redirect(getBaseUrl() . '/register.php');
}

// ── Database operations ───────────────────────────────────────
try {
    $pdo = getPDO();

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        setFlash('error', 'An account with this email address already exists. Please log in or use a different email.');
        redirect(getBaseUrl() . '/register.php');
    }

    // Hash the password — never store plain text
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insert new customer account
    // ASSUMPTION: All public registrations create a 'customer' role account.
    // Staff and Admin accounts are created by the Administrator only.
    $insert = $pdo->prepare(
        "INSERT INTO users (full_name, email, phone, password, role, status)
         VALUES (?, ?, ?, ?, 'customer', 'enabled')"
    );
    $insert->execute([$fullName, $email, $phone ?: null, $hashedPassword]);

    // Clear old form data
    unset($_SESSION['reg_old']);

    setFlash('success', 'Account created successfully! Please log in with your credentials.');
    redirect(getBaseUrl() . '/login.php?email=' . urlencode($email));

} catch (PDOException $e) {
    error_log('[EcoSprout] Register error: ' . $e->getMessage());
    setFlash('error', 'A system error occurred. Please try again.');
    redirect(getBaseUrl() . '/register.php');
}
