<?php
require_once __DIR__ . '/../../vendor/autoload.php';

// Load environment variables from .env.test
$envFile = __DIR__ . '/../../.env.test';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Default test database configuration
$config = [
    'host' => 'localhost',
    'database' => 'garden_sensors_test',
    'username' => 'garden_user',
    'password' => 'test_password'
];

// Override with environment variables if they exist
$config['host'] = getenv('DB_HOST') ?: $config['host'];
$config['username'] = getenv('DB_USER') ?: $config['username'];
$config['password'] = getenv('DB_PASS') ?: $config['password'];

try {
    // Drop and recreate test database
    echo "Creating test database...\n";
    exec("sudo mysql -e 'DROP DATABASE IF EXISTS {$config['database']}'");
    exec("sudo mysql -e 'CREATE DATABASE {$config['database']} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'");

    // Drop and recreate test user with full privileges on test database
    echo "Setting up test user...\n";
    exec("sudo mysql -e 'DROP USER IF EXISTS \"{$config['username']}\"@\"localhost\"'");
    exec("sudo mysql -e 'CREATE USER \"{$config['username']}\"@\"localhost\" IDENTIFIED BY \"{$config['password']}\"'");
    exec("sudo mysql -e 'GRANT ALL PRIVILEGES ON {$config['database']}.* TO \"{$config['username']}\"@\"localhost\"'");
    exec("sudo mysql -e 'FLUSH PRIVILEGES'");

    // Connect to test database as test user
    echo "Connecting to test database...\n";
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']}", 
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Read and execute schema
    echo "Applying schema...\n";
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);
    
    // Split schema into statements and execute them
    $statements = array_filter(
        array_map('trim', 
            // Split on semicolons but keep CREATE TRIGGER statements intact
            preg_split("/;(?=([^']*'[^']*')*[^']*$)/", $schema)
        )
    );

    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo ".";
            } catch (PDOException $e) {
                echo "\nError executing statement: $statement\n";
                throw $e;
            }
        }
    }

    echo "\nTest database setup completed successfully\n";

} catch (Exception $e) {
    die("Error setting up test database: " . $e->getMessage() . "\n");
} 