<?php
/**
 * EcoSprout – Authentication & Authorization Helpers
 *
 * Include this file on every protected page AFTER session.php.
 * It never outputs HTML; all it does is redirect on failure.
 */

require_once __DIR__ . '/../includes/session.php';

/**
 * Ensure the visitor is logged in.
 * Redirects to login.php if no active session exists.
 */
function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        $_SESSION['flash_error'] = 'Please log in to access that page.';
        header('Location: ' . getBaseUrl() . '/login.php');
        exit;
    }
}

/**
 * Ensure the logged-in user has one of the allowed roles.
 *
 * @param string[] $allowedRoles  e.g. ['admin'] or ['admin','staff']
 */
function requireRole(array $allowedRoles): void
{
    requireLogin();

    $userRole = $_SESSION['user_role'] ?? '';

    if (!in_array($userRole, $allowedRoles, true)) {
        // Redirect to the user's own dashboard
        $dashboardMap = [
            'admin'    => getBaseUrl() . '/admin/dashboard.php',
            'staff'    => getBaseUrl() . '/staff/dashboard.php',
            'customer' => getBaseUrl() . '/customer/dashboard.php',
        ];
        $_SESSION['flash_error'] = 'You do not have permission to access that page.';
        $dest = $dashboardMap[$userRole] ?? (getBaseUrl() . '/login.php');
        header('Location: ' . $dest);
        exit;
    }
}

/**
 * Returns true if the user is currently logged in.
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Returns the current user's role, or an empty string if not logged in.
 */
function getCurrentRole(): string
{
    return $_SESSION['user_role'] ?? '';
}

/**
 * Returns the current user's ID, or 0 if not logged in.
 */
function getCurrentUserId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}

/**
 * Returns the currently logged in user record as an array, or null.
 */
function getCurrentUser(): ?array
{
    $userId = getCurrentUserId();
    if ($userId <= 0) {
        return null;
    }

    if (!function_exists('getPDO')) {
        require_once __DIR__ . '/../config/database.php';
    }

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT id, full_name, email, phone, role, status, created_at, updated_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (Exception $e) {
        error_log('[EcoSprout] getCurrentUser error: ' . $e->getMessage());
        return [
            'id'         => $userId,
            'full_name'  => $_SESSION['user_name'] ?? 'User',
            'email'      => $_SESSION['user_email'] ?? '',
            'phone'      => '',
            'role'       => $_SESSION['user_role'] ?? 'customer',
            'status'     => $_SESSION['user_status'] ?? 'enabled',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }
}

/**
 * Derive the application base URL dynamically.
 * Works with any subfolder deployment under localhost.
 */
function getBaseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Derive base path from SCRIPT_NAME (e.g. /EcoSprout/login.php or /EcoSprout/admin/dashboard.php)
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if (preg_match('#^(.*?)/(?:admin|staff|customer|actions|includes|assets|database)(?:/|$)#i', $scriptName, $matches)) {
        $subFolder = $matches[1];
    } else {
        $subFolder = dirname($scriptName);
    }

    $subFolder = str_replace('\\', '/', $subFolder);
    if ($subFolder === '/' || $subFolder === '.' || $subFolder === '\\') {
        $subFolder = '';
    }

    return rtrim($protocol . '://' . $host . $subFolder, '/');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', getBaseUrl());
}
