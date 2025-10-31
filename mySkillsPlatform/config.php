<?php
// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found. Please create one based on .env.example');
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set as environment variable and PHP constant
            putenv("$key=$value");
            $_ENV[$key] = $value;
            
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

// Load .env file from the same directory as config.php
loadEnv(__DIR__ . '/.env');

// Database configuration from environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Create PDO connection
function getDBConnection() {
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
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>