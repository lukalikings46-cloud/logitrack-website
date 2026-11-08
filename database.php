<?php
/**
 * Database connection settings.
 * Update these four constants to match your MySQL / phpMyAdmin setup.
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'logitracker_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Set this to true temporarily if you need to see the *real* MySQL
 * error (wrong password, unknown database, connection refused, etc.)
 * instead of the generic message below. Set back to false once your
 * site is working, so real errors aren't exposed publicly.
 */
define('DB_DEBUG', true);

/**
 * Returns a shared PDO connection. Dies with a JSON error if the
 * connection fails (safe for both API and normal page use).
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error'   => DB_DEBUG
                ? 'Database connection failed: ' . $e->getMessage()
                : 'Database connection failed. Check config/database.php.',
        ]);
        exit;
    }
}

