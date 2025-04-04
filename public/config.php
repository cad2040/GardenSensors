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

// Include the main configuration file
require_once __DIR__ . '/includes/config.php';

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'garden_sensors');
define('DB_USER', 'garden_user');
define('DB_PASS', '');

// Application paths
define('APP_ROOT', dirname(__DIR__));
define('INCLUDES_PATH', APP_ROOT . '/public/includes');

// Include required files
require_once(INCLUDES_PATH . '/functions.php');

// Application configuration
define('APP_NAME', 'Garden Sensors');
define('APP_VERSION', '1.0.0');

// File paths (only define if not already defined)
if (!defined('ROOT_PATH')) define('ROOT_PATH', '/var/www/html/garden-sensors');
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

// Security configuration
define('SESSION_LIFETIME', 3600);
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900);

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_DIR', APP_ROOT . '/cache');
define('CACHE_TTL', 300);

// Logging configuration
define('LOG_LEVEL', 'debug');
define('LOG_FILE', APP_ROOT . '/logs/app.log');
define('LOG_MAX_SIZE', 5242880);
define('LOG_MAX_FILES', 5);

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