<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env.test
$envFile = __DIR__ . '/../.env.test';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Load database configuration
$config = require __DIR__ . '/../src/Config/database.php';

try {
    // Drop existing user if exists
    echo "Dropping existing user if exists...\n";
    exec("sudo mysql -u root -p364828 -e \"DROP USER IF EXISTS '{$config['username']}'@'localhost'\"");
    exec("sudo mysql -u root -p364828 -e \"FLUSH PRIVILEGES\"");

    // Create user and grant privileges
    echo "Creating database user...\n";
    exec("sudo mysql -u root -p364828 -e \"CREATE USER '{$config['username']}'@'localhost' IDENTIFIED BY '{$config['password']}'\"");
    exec("sudo mysql -u root -p364828 -e \"GRANT ALL PRIVILEGES ON garden_sensors_test.* TO '{$config['username']}'@'localhost'\"");
    exec("sudo mysql -u root -p364828 -e \"FLUSH PRIVILEGES\"");

    // Test the new user connection
    echo "Testing new user connection...\n";
    $testDsn = "mysql:host={$config['host']};dbname=garden_sensors_test;charset=utf8mb4";
    $testPdo = new PDO($testDsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    echo "Connection test successful!\n";

    echo "Database user setup completed successfully.\n";
} catch (PDOException $e) {
    echo "Error setting up database user: " . $e->getMessage() . "\n";
    exit(1);
} 