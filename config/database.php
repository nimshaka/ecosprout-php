<?php
/**
 * EcoSprout – Database Configuration
 * Uses PDO with prepared statements for all database operations.
 *
 * LIVE VERSION — running on InfinityFree hosting.
 */

// ── Database credentials ──────────────────────────────────────
define('DB_HOST', 'sql109.infinityfree.com');
define('DB_NAME', 'if0_42905157_ecosprout');
define('DB_USER', 'if0_42905157');
define('DB_PASS', 'rOoIM0oONl0J');
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