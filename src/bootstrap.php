<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GardenSensors\Config\AppConfig;
use GardenSensors\Services\DatabaseService;
use GardenSensors\Services\CacheService;
use GardenSensors\Services\LoggingService;
use GardenSensors\Services\RateLimiterService;

// Initialize application configuration
AppConfig::initialize();

// Load Python configuration
$pythonConfig = require __DIR__ . '/Config/python.php';
AppConfig::load(['python' => $pythonConfig]);

// Start session
session_start();

// Initialize services
$db = new DatabaseService();
$cache = new CacheService();
$logger = new LoggingService();
$rateLimiter = new RateLimiterService($db);

// Set up error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logger) {
    $logger->error("PHP Error: [$errno] $errstr in $errfile on line $errline");
    return true;
});

// Set up exception handling
set_exception_handler(function($exception) use ($logger) {
    $logger->error("Uncaught Exception: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $exception->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode([
            'error' => 'Internal Server Error',
            'message' => 'An unexpected error occurred'
        ]);
    }
});

// Set up fatal error handling
register_shutdown_function(function() use ($logger) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $logger->error("Fatal Error: " . $error['message'], [
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
}); 