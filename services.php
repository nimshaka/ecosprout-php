<?php
/**
 * EcoSprout – Services Router
 * Routes to the appropriate services page based on current session role.
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$base = getBaseUrl();

if (!isLoggedIn()) {
    redirect($base . '/index.php#services');
}

$role = getCurrentRole();

$dest = match($role) {
    'admin'    => $base . '/admin/services.php',
    'staff'    => $base . '/staff/services.php',
    'customer' => $base . '/customer/services.php',
    default    => $base . '/index.php#services',
};

redirect($dest);
