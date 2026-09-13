<?php
/**
 * EcoSprout – Logout
 * Destroys the session and redirects to the homepage.
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Destroy session completely
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}
session_destroy();

// Redirect to the landing page
redirect(getBaseUrl() . '/index.php');
