<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'garden_sensors');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Garden Sensors');
define('APP_URL', 'http://localhost/GardenSensors/GUI');
define('APP_VERSION', '1.0.0');

// Security configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LENGTH', 32);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes
define('API_KEY_LENGTH', 32);

// Cache configuration
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/../cache');
define('CACHE_TTL', 300); // 5 minutes

// Rate limiting configuration
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 100); // requests per minute
define('RATE_LIMIT_WINDOW', 60); // 1 minute window

// API configuration
define('API_VERSION', 'v1');
define('API_BASE_URL', APP_URL . '/api/' . API_VERSION);
define('API_RESPONSE_FORMAT', 'json');

// Logging configuration
define('LOG_LEVEL', 'debug'); // debug, info, warning, error
define('LOG_FILE', __DIR__ . '/../logs/app.log');
define('LOG_MAX_SIZE', 5242880); // 5MB
define('LOG_MAX_FILES', 5);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE);

// Time zone
date_default_timezone_set('UTC');

// Initialize database connection
require_once __DIR__ . '/db.php';
$db = new Database();

// Initialize cache
require_once __DIR__ . '/cache.php';
$cache = new Cache();

// Initialize rate limiter
require_once __DIR__ . '/rate_limiter.php';
$rateLimiter = new RateLimiter();

// Initialize logger
require_once __DIR__ . '/logger.php';
$logger = new Logger(); 