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

// Database configuration from environment variables (avoid duplicate definitions)
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: '');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: '');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') ?: '');

// Create or reuse PDO connection
function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

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

        // Ensure tables exist
        ensureDatabaseTables($pdo);

        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        die("Database connection failed: " . htmlspecialchars($e->getMessage()));
    }
}

// Auto-run schema if users table does not exist
function ensureDatabaseTables($pdo) {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if (!$check && file_exists(__DIR__ . '/schema.sql')) {
            $sql = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec($sql);
        }
    } catch (Exception $e) {
        error_log("Error verifying database tables: " . $e->getMessage());
    }
}

// Ensure uploads directory exists
$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
?>