<?php

/**
 * Garden Sensors Dashboard - Entry Point
 */

// Define the application root path
define('APP_ROOT', dirname(__DIR__));

// Load Composer's autoloader
require APP_ROOT . '/vendor/autoload.php';

// Load environment configuration
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->load();

// Load application configuration
$config = require APP_ROOT . '/config/app.php';

// Initialize error handling
error_reporting(E_ALL);
ini_set('display_errors', $config['debug'] ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', $config['logging']['path'] . '/error.log');

// Start session
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true
]);

// Initialize the router
$router = new GardenSensors\Utils\Router();

// Define routes
$router->get('/', 'DashboardController@index');
$router->get('/sensors', 'SensorController@index');
$router->get('/sensors/{id}', 'SensorController@show');
$router->post('/sensors', 'SensorController@store');
$router->put('/sensors/{id}', 'SensorController@update');
$router->delete('/sensors/{id}', 'SensorController@delete');

$router->get('/plants', 'PlantController@index');
$router->get('/plants/{id}', 'PlantController@show');
$router->post('/plants', 'PlantController@store');
$router->put('/plants/{id}', 'PlantController@update');
$router->delete('/plants/{id}', 'PlantController@delete');

$router->get('/readings', 'ReadingController@index');
$router->get('/readings/{id}', 'ReadingController@show');
$router->post('/readings', 'ReadingController@store');

$router->get('/settings', 'SettingController@index');
$router->put('/settings', 'SettingController@update');

$router->post('/auth/login', 'AuthController@login');
$router->post('/auth/logout', 'AuthController@logout');
$router->post('/auth/reset-password', 'AuthController@resetPassword');

// Handle the request
try {
    $router->dispatch();
} catch (Exception $e) {
    if ($config['debug']) {
        throw $e;
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $config['debug'] ? $e->getMessage() : 'An unexpected error occurred'
    ]);
} 