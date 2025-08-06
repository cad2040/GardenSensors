<?php
/**
 * Test Configuration Checker
 * This script validates that the test environment is properly configured
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== Garden Sensors Test Configuration Check ===\n\n";

// Check 1: Environment Variables
echo "1. Checking Environment Variables...\n";
$requiredEnvVars = [
    'TESTING' => 'true',
    'DB_HOST' => 'localhost',
    'DB_DATABASE' => 'garden_sensors',
    'DB_USER' => 'root',
    'DB_PASS' => 'newrootpassword'
];

$envOk = true;
foreach ($requiredEnvVars as $var => $expected) {
    $value = getenv($var);
    if ($value === $expected) {
        echo "   ✓ $var = $value\n";
    } else {
        echo "   ✗ $var = $value (expected: $expected)\n";
        $envOk = false;
    }
}

// Check 2: Database Connection
echo "\n2. Checking Database Connection...\n";
try {
    $config = require __DIR__ . '/../src/Config/database.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    echo "   ✓ Database connection successful\n";
    echo "   ✓ Connected to: {$config['database']}\n";
    
    // Check if production database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'garden_sensors'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Production database exists\n";
    } else {
        echo "   ✗ Production database does not exist\n";
        $envOk = false;
    }
    
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    $envOk = false;
}

// Check 3: Required Files
echo "\n3. Checking Required Files...\n";
$requiredFiles = [
    'vendor/autoload.php' => 'Composer autoloader',
    'src/Config/database.php' => 'Database configuration',
    'src/Core/Database.php' => 'Database class',
    'tests/database.sql' => 'Test database schema',
    'tests/TestCase.php' => 'Base test case',
    'phpunit.xml' => 'PHPUnit configuration'
];

$filesOk = true;
foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "   ✓ $description ($file)\n";
    } else {
        echo "   ✗ $description ($file) - MISSING\n";
        $filesOk = false;
    }
}

// Check 4: Model Classes
echo "\n4. Checking Model Classes...\n";
$models = [
    'Sensor' => 'src/Models/Sensor.php',
    'User' => 'src/Models/User.php',
    'Plant' => 'src/Models/Plant.php',
    'Reading' => 'src/Models/Reading.php',
    'Pin' => 'src/Models/Pin.php',
    'FactPlant' => 'src/Models/FactPlant.php',
    'BaseModel' => 'src/Models/BaseModel.php'
];

$modelsOk = true;
foreach ($models as $model => $file) {
    if (file_exists($file)) {
        echo "   ✓ $model model ($file)\n";
    } else {
        echo "   ✗ $model model ($file) - MISSING\n";
        $modelsOk = false;
    }
}

// Check 5: Test Files
echo "\n5. Checking Test Files...\n";
$testFiles = [
    'tests/Models/SensorTest.php',
    'tests/Models/UserTest.php',
    'tests/Models/PlantTest.php',
    'tests/Models/ReadingTest.php',
    'tests/Models/PinTest.php',
    'tests/Models/FactPlantTest.php',
    'tests/Models/BaseModelTest.php'
];

$testsOk = true;
foreach ($testFiles as $testFile) {
    if (file_exists($testFile)) {
        echo "   ✓ $testFile\n";
    } else {
        echo "   ✗ $testFile - MISSING\n";
        $testsOk = false;
    }
}

// Check 6: Python Environment
echo "\n6. Checking Python Environment...\n";
if (file_exists('venv/bin/python3')) {
    echo "   ✓ Python virtual environment exists\n";
    
    // Check if pytest is available
    $pytestOutput = shell_exec('venv/bin/pip list | grep pytest');
    if ($pytestOutput) {
        echo "   ✓ pytest is installed\n";
    } else {
        echo "   ✗ pytest is not installed\n";
        $testsOk = false;
    }
} else {
    echo "   ✗ Python virtual environment not found\n";
    $testsOk = false;
}

// Summary
echo "\n=== SUMMARY ===\n";
if ($envOk && $filesOk && $modelsOk && $testsOk) {
    echo "✓ All checks passed! Test environment is properly configured.\n";
    echo "\nYou can now run tests with:\n";
    echo "  ./vendor/bin/phpunit\n";
    echo "  source venv/bin/activate && pytest\n";
    exit(0);
} else {
    echo "✗ Some checks failed. Please fix the issues above before running tests.\n";
    exit(1);
} 