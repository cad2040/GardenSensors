<?php
// Database configuration
define('DB_HOST', 'MYSQLHOST:3306');
define('DB_NAME', 'SoilSensors');
define('DB_USER', 'SoilSensors');
define('DB_PASS', 'MYSQLPASS');

// Application configuration
define('APP_NAME', 'Garden Sensors Dashboard');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://your-domain.com');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Time zone
date_default_timezone_set('UTC');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT); 