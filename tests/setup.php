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
    // Connect to MySQL server
    echo "Connecting to MySQL server...\n";
    $pdo = new PDO(
        "mysql:host={$config['host']}", 
        $config['username'], 
        $config['password'], 
        $config['options']
    );

    // Drop and create test database
    echo "Creating test database...\n";
    $pdo->exec("DROP DATABASE IF EXISTS garden_sensors_test");
    $pdo->exec("CREATE DATABASE garden_sensors_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE garden_sensors_test");

    // Read schema file
    echo "Reading schema file...\n";
    $schemaFile = __DIR__ . '/database.sql';
    if (!file_exists($schemaFile)) {
        throw new Exception("Schema file not found at: $schemaFile");
    }

    $schema = file_get_contents($schemaFile);
    
    // Split schema into statements
    $createStatements = [];
    $insertStatements = [];
    $currentStatement = '';
    $inComment = false;
    $inCreateTable = false;
    $lines = explode("\n", $schema);
    $lineNumber = 0;
    
    while ($lineNumber < count($lines)) {
        $line = trim($lines[$lineNumber]);
        $lineNumber++;
        
        // Skip empty lines
        if (empty($line)) {
            continue;
        }
        
        // Handle multi-line comments
        if (!$inComment && strpos($line, '/*') !== false) {
            $inComment = true;
            continue;
        }
        if ($inComment) {
            if (strpos($line, '*/') !== false) {
                $inComment = false;
            }
            continue;
        }
        
        // Skip single-line comments
        if (strpos($line, '--') === 0) {
            continue;
        }
        
        // Skip database creation, user creation, and privilege statements
        if (preg_match('/^(CREATE DATABASE|USE|CREATE USER|GRANT|FLUSH PRIVILEGES)/i', $line)) {
            continue;
        }
        
        // Check if we're starting a CREATE TABLE statement
        if (preg_match('/^CREATE TABLE\s+(?:IF NOT EXISTS\s+)?/i', $line)) {
            $inCreateTable = true;
            $currentStatement = $line;
            
            // Read until we find the closing parenthesis and semicolon
            while ($lineNumber < count($lines) && !preg_match('/;\s*$/', $currentStatement)) {
                $nextLine = trim($lines[$lineNumber]);
                $lineNumber++;
                
                if (!empty($nextLine) && strpos($nextLine, '--') !== 0) {
                    $currentStatement .= ' ' . $nextLine;
                }
            }
            
            if (preg_match('/;\s*$/', $currentStatement)) {
                $createStatements[] = $currentStatement;
                echo "Found CREATE TABLE statement: " . substr($currentStatement, 0, 100) . "...\n";
            }
            
            $inCreateTable = false;
            $currentStatement = '';
            continue;
        }
        
        // Handle INSERT statements
        if (preg_match('/^(INSERT INTO|SET)/i', $line)) {
            $currentStatement = $line;
            
            // Read until we find a semicolon
            while ($lineNumber < count($lines) && !preg_match('/;\s*$/', $currentStatement)) {
                $nextLine = trim($lines[$lineNumber]);
                $lineNumber++;
                
                if (!empty($nextLine) && strpos($nextLine, '--') !== 0) {
                    $currentStatement .= ' ' . $nextLine;
                }
            }
            
            if (preg_match('/;\s*$/', $currentStatement)) {
                $insertStatements[] = $currentStatement;
                echo "Found INSERT statement: " . substr($currentStatement, 0, 100) . "...\n";
            }
            
            $currentStatement = '';
            continue;
        }
    }
    
    // Execute CREATE TABLE statements first
    echo "\nCreating tables...\n";
    foreach ($createStatements as $statement) {
        try {
            $pdo->exec($statement);
            echo ".";
        } catch (PDOException $e) {
            echo "\nError executing statement: $statement\n";
            throw $e;
        }
    }
    
    // Then execute INSERT statements
    echo "\nInserting data...\n";
    foreach ($insertStatements as $statement) {
        try {
            $pdo->exec($statement);
            echo ".";
        } catch (PDOException $e) {
            echo "\nError executing statement: $statement\n";
            throw $e;
        }
    }

    echo "\nTest database setup completed successfully\n";

} catch (Exception $e) {
    die("Error setting up test database: " . $e->getMessage() . "\n");
} 