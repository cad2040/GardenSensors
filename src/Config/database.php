<?php
$isTest = getenv('TESTING') === 'true';
$dbName = $isTest ? 'garden_sensors_test' : 'garden_sensors';

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'database' => getenv('DB_DATABASE') ?: $dbName,
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: 'newrootpassword',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5, // 5 second timeout
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET SESSION wait_timeout=5' // MySQL session timeout
    ]
]; 