<?php
/**
 * EcoSprout – Staff & User Management Action Handler (Admin only)
 * Actions: create_user | toggle_status | change_role | delete_user
 */

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBaseUrl() . '/admin/staff.php');
}

verifyCsrf();

$action    = $_POST['action'] ?? '';
$pdo       = getPDO();
$returnUrl = getBaseUrl() . '/admin/staff.php';
$adminId   = getCurrentUserId();

try {

    // ── Create new user (Staff or Admin) ───────────────────────
    if ($action === 'create_user') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $phone    = trim($_POST['phone']  ?? '');
        $role     = $_POST['role']        ?? 'staff';
        $password = $_POST['password']    ?? '';
        $validRoles = ['staff', 'admin'];

        if (empty($fullName) || empty($email) || empty($password)) {
            setFlash('error', 'Full name, email, and password are required.');
            redirect($returnUrl);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Invalid email address.');
            redirect($returnUrl);
        }
        if (!in_array($role, $validRoles)) {
            setFlash('error', 'Invalid role.');
            redirect($returnUrl);
        }
        if (mb_strlen($password) < 8) {
            setFlash('error', 'Password must be at least 8 characters.');
            redirect($returnUrl);
        }

        // Check email uniqueness
        $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
        $check->execute([$email]);
        if ($check->fetch()) {
            setFlash('error', 'Email already in use by another account.');
            redirect($returnUrl);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, phone, password, role, status) VALUES (?,?,?,?,?,'enabled')"
        );
        $stmt->execute([$fullName, $email, $phone ?: null, $hash, $role]);
        setFlash('success', ucfirst($role) . ' account created for ' . htmlspecialchars($fullName) . '.');
        redirect($returnUrl);
    }

    // ── Toggle user enabled/disabled ──────────────────────────
    if ($action === 'toggle_status') {
        $targetId = (int) ($_POST['user_id'] ?? 0);

        // Prevent admin from disabling themselves
        if ($targetId === $adminId) {
            setFlash('error', 'You cannot disable your own account.');
            redirect($returnUrl);
        }
        if ($targetId <= 0) { setFlash('error', 'Invalid user ID.'); redirect($returnUrl); }

        $stmt = $pdo->prepare("SELECT status FROM users WHERE id=?");
        $stmt->execute([$targetId]);
        $user = $stmt->fetch();
        if (!$user) { setFlash('error', 'User not found.'); redirect($returnUrl); }

        $newStatus = $user['status'] === 'enabled' ? 'disabled' : 'enabled';
        $upd = $pdo->prepare("UPDATE users SET status=? WHERE id=?");
        $upd->execute([$newStatus, $targetId]);
        setFlash('success', 'User account ' . $newStatus . ' successfully.');
        redirect($returnUrl);
    }

    // ── Change user role ──────────────────────────────────────
    if ($action === 'change_role') {
        $targetId = (int) ($_POST['user_id'] ?? 0);
        $newRole  = $_POST['new_role'] ?? '';
        $validRoles = ['customer', 'staff', 'admin'];

        if ($targetId === $adminId) {
            setFlash('error', 'You cannot change your own role.');
            redirect($returnUrl);
        }
        if ($targetId <= 0 || !in_array($newRole, $validRoles)) {
            setFlash('error', 'Invalid input.');
            redirect($returnUrl);
        }

        $stmt = $pdo->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->execute([$newRole, $targetId]);
        setFlash('success', 'User role updated to "' . ucfirst($newRole) . '".');
        redirect($returnUrl);
    }

    // ── Delete user ───────────────────────────────────────────
    if ($action === 'delete_user') {
        $targetId = (int) ($_POST['user_id'] ?? 0);

        if ($targetId === $adminId) {
            setFlash('error', 'You cannot delete your own account.');
            redirect($returnUrl);
        }
        if ($targetId <= 0) { setFlash('error', 'Invalid user ID.'); redirect($returnUrl); }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$targetId]);
        setFlash('success', 'User account deleted.');
        redirect($returnUrl);
    }

    setFlash('error', 'Unknown action.');
    redirect($returnUrl);

} catch (PDOException $e) {
    error_log('[EcoSprout] Staff action error: ' . $e->getMessage());
    setFlash('error', 'Database error. Please try again.');
    redirect($returnUrl);
}
