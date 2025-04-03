<?php
// Set session settings before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Start session
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'SoilSensors');
define('DB_USER', 'root');
define('DB_PASS', 'password');

// Application configuration
define('APP_NAME', 'Garden Sensors Dashboard');
define('APP_URL', 'http://localhost/garden-sensors');
define('APP_VERSION', '1.0.0');

// File paths
define('ROOT_PATH', dirname(__FILE__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('CACHE_PATH', ROOT_PATH . '/cache');
define('LOGS_PATH', ROOT_PATH . '/logs');

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
define('DEBUG_MODE', true); // Enable debug mode temporarily
define('TIMEZONE', 'UTC');

// Security settings
define('SESSION_LIFETIME', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);

// Cache settings
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 300); // 5 minutes

// Logging settings
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'ERROR'); // DEBUG, INFO, WARNING, ERROR

// Email settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('ALERT_EMAIL', 'alerts@yourdomain.com');

// Sensor settings
define('READING_INTERVAL', 3600); // 1 hour
define('ALERT_THRESHOLD', 20); // Battery level percentage
define('DATA_RETENTION_DAYS', 30);

// Include required files
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/db.php';

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