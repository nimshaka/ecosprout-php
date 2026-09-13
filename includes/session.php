<?php
/**
 * EcoSprout – Session Initializer
 * Must be included before any output on every page.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie
    session_set_cookie_params([
        'lifetime' => 0,          // Cookie expires when browser closes
        'path'     => '/',
        'secure'   => false,      // Set to true when serving over HTTPS
        'httponly' => true,        // JavaScript cannot access the cookie
        'samesite' => 'Lax',
    ]);
    session_start();
}
