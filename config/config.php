<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'garden_sensors');
define('DB_USER', 'garden_user');
define('DB_PASS', 'garden_password');

// Application configuration
define('APP_NAME', 'Garden Sensors');
define('APP_URL', 'http://localhost/garden-sensors');
define('APP_VERSION', '1.0.0');

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Paths
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('LOGS_PATH', BASE_PATH . '/logs');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);

// Sensor settings
define('SENSOR_READ_INTERVAL', 300); // 5 minutes
define('SENSOR_TIMEOUT', 900); // 15 minutes

// Notification settings
define('NOTIFICATION_EMAIL', 'admin@example.com');
define('NOTIFICATION_ENABLED', true); 