<?php
/**
 * EcoSprout – Database Configuration
 * Uses PDO with prepared statements for all database operations.
 *
 * ASSUMPTION: Running on a localhost XAMPP/WAMP/Laragon stack.
 * Update DB_HOST, DB_USER, DB_PASS if your environment differs.
 */

// ── Database credentials ──────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecosprout');
define('DB_USER', 'root');   // Change to your MySQL username
define('DB_PASS', '');        // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO connection instance.
 * Throws a PDOException on connection failure (caught and displayed).
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error instead of displaying it.
            die('<div style="font-family:sans-serif;padding:2rem;color:#c00;">'
                . '<h2>Database Connection Error</h2>'
                . '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
                . '<p>Please check <code>config/database.php</code> and ensure '
                . 'MySQL is running with the correct credentials.</p>'
                . '</div>');
        }
    }

    return $pdo;
}
