<?php
/**
 * Database Configuration
 * Update these values for your database
 */

define('DB_HOST', '');
define('DB_NAME', 'emperor_browser');
define('DB_USER', '');
define('DB_PASS', '');

// Session configuration
define('SESSION_LIFETIME', 30 * 24 * 60 * 60); // 30 days in seconds

// Points configuration
define('POINTS_COMMENT', 5);
define('POINTS_MESSAGE', 2);
define('POINTS_VOTE', 1);
define('POINTS_PER_MINUTE', 0.1); // Points per minute of activity

// Admin email for notifications
define('ADMIN_EMAIL', '');

// Create database connection
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die(json_encode(['error' => 'Database connection failed']));
        }
    }
    
    return $pdo;
}

// Prevent direct access
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    die('Access denied');
}
?>