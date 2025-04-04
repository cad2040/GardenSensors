<?php
$isTest = getenv('TESTING') === 'true';
$dbName = $isTest ? 'garden_sensors_test' : 'garden_sensors';

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'database' => $dbName,
    'username' => getenv('DB_USER') ?: 'garden_user',
    'password' => getenv('DB_PASS') ?: ($isTest ? 'test_password' : ''),
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]
]; 