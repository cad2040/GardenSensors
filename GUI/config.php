<?php
// Start session first, before any output
if (session_status() === PHP_SESSION_NONE) {
    // Set session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 0 for non-HTTPS
    
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'SoilSensors');
define('DB_USER', 'SoilSensors');
define('DB_PASS', 'SoilSensors123');

// Application configuration
define('APP_NAME', 'Garden Sensors Dashboard');
define('APP_URL', 'http://localhost/garden-sensors');
define('APP_VERSION', '1.0.0');

// File paths (only define if not already defined)
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__FILE__));
if (!defined('INCLUDES_PATH')) define('INCLUDES_PATH', ROOT_PATH . '/includes');
if (!defined('CACHE_PATH')) define('CACHE_PATH', ROOT_PATH . '/cache');
if (!defined('LOGS_PATH')) define('LOGS_PATH', ROOT_PATH . '/logs');

// Create required directories if they don't exist
$directories = [CACHE_PATH, LOGS_PATH];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Error logging configuration
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/error.log');

// Time zone
date_default_timezone_set('UTC');

// Application settings
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', true); // Enable debug mode temporarily
if (!defined('TIMEZONE')) define('TIMEZONE', 'UTC');

// Security settings
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 3600); // 1 hour
if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', 5);
if (!defined('LOCKOUT_TIME')) define('LOCKOUT_TIME', 900); // 15 minutes
if (!defined('PASSWORD_RESET_EXPIRY')) define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour
if (!defined('CSRF_TOKEN_NAME')) define('CSRF_TOKEN_NAME', 'csrf_token');
if (!defined('CSRF_TOKEN_LENGTH')) define('CSRF_TOKEN_LENGTH', 32);
if (!defined('PASSWORD_HASH_ALGO')) define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);

// Cache settings
if (!defined('CACHE_ENABLED')) define('CACHE_ENABLED', true);
if (!defined('CACHE_LIFETIME')) define('CACHE_LIFETIME', 300); // 5 minutes

// Logging settings
if (!defined('LOG_ENABLED')) define('LOG_ENABLED', true);
if (!defined('LOG_LEVEL')) define('LOG_LEVEL', 'ERROR'); // DEBUG, INFO, WARNING, ERROR

// Email settings
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USER')) define('SMTP_USER', 'your_email@gmail.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'your_app_password');
if (!defined('ALERT_EMAIL')) define('ALERT_EMAIL', 'alerts@yourdomain.com');

// Sensor settings
if (!defined('READING_INTERVAL')) define('READING_INTERVAL', 3600); // 1 hour
if (!defined('ALERT_THRESHOLD')) define('ALERT_THRESHOLD', 20); // Battery level percentage
if (!defined('DATA_RETENTION_DAYS')) define('DATA_RETENTION_DAYS', 30);

// Include required files only if they haven't been included yet
if (!function_exists('getUserById')) {
    require_once INCLUDES_PATH . '/functions.php';
}
if (!class_exists('Database')) {
    require_once INCLUDES_PATH . '/db.php';
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on debug mode
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Error reporting
ini_set('log_errors', 1);
ini_set('error_log', ROOT_PATH . '/logs/error.log'); 