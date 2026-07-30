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
    'database/schema.sql' => 'Database schema',
    'database/migrations/003_add_ph_nitrogen_support.sql' => 'Legacy pH migration',
    'database/seed_test_plot_data.sql' => 'Canonical per-plant dashboard seed',
    'public/index.php' => 'Dashboard (plant tiles + plot filter)',
    'public/api/summary.php' => 'Per-plant summary API',
    'public/assets/css/style.css' => 'Dashboard stylesheet',
    'database/migrations/004_ec_nutrient_proxy.sql' => 'EC nutrient proxy migration',
    'docs/development/ec-nutrient-proxy.md' => 'EC nutrient proxy notes',
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

// Check 5: Test Files (includes pH / EC coverage)
echo "\n5. Checking Test Files...\n";
$testFiles = [
    'tests/Models/SensorTest.php',
    'tests/Models/UserTest.php',
    'tests/Models/PlantTest.php',
    'tests/Models/ReadingTest.php',
    'tests/Models/PinTest.php',
    'tests/Models/BaseModelTest.php',
    'tests/Services/NotificationTest.php',
    'tests/Services/EcNutrientServiceTest.php',
    'tests/Services/DashboardSeedTest.php',
    'tests/python/test_produce_plot.py',
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

// Check Plant model exposes pH / EC API used by alerts
echo "\n5b. Checking Plant pH/EC API...\n";
$plantSource = file_get_contents('src/Models/Plant.php');
foreach (['min_ph', 'max_ph', 'ec_underfed', 'ec_balanced_min', 'getEcZones', 'getMinPh'] as $needle) {
    if (strpos($plantSource, $needle) !== false) {
        echo "   ✓ Plant.php contains $needle\n";
    } else {
        echo "   ✗ Plant.php missing $needle\n";
        $testsOk = false;
    }
}

$notificationSource = file_get_contents('src/Services/Notification.php');
foreach (['checkPhAlerts', 'checkEcAlerts'] as $needle) {
    if (strpos($notificationSource, $needle) !== false) {
        echo "   ✓ Notification.php contains $needle\n";
    } else {
        echo "   ✗ Notification.php missing $needle\n";
        $testsOk = false;
    }
}

// Check schema columns when DB is available
echo "\n5c. Checking DB schema for pH/EC (if connected)...\n";
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $cols = $pdo->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plants'
               AND COLUMN_NAME IN ('min_ph','max_ph','ec_underfed','ec_balanced_min','ec_balanced_max','ec_overfed')"
        )->fetchAll(PDO::FETCH_COLUMN);
        foreach (['min_ph','max_ph','ec_underfed','ec_balanced_min','ec_balanced_max','ec_overfed'] as $col) {
            if (in_array($col, $cols, true)) {
                echo "   ✓ plants.$col\n";
            } else {
                echo "   ✗ plants.$col missing (run setup / migration 004)\n";
                $testsOk = false;
            }
        }
    } catch (Exception $e) {
        echo "   ✗ Schema check failed: " . $e->getMessage() . "\n";
        $testsOk = false;
    }
} else {
    echo "   - Skipped (no DB connection)\n";
}

// Check 6: Python Environment
echo "\n6. Checking Python Environment...\n";
$pythonOk = true;
if (file_exists('venv/bin/python3') || file_exists('.venv/bin/python3')) {
    echo "   ✓ Python virtual environment exists\n";
    $pip = file_exists('venv/bin/pip') ? 'venv/bin/pip' : '.venv/bin/pip';
    $pytestOutput = shell_exec("$pip list 2>/dev/null | grep -i pytest");
    if ($pytestOutput) {
        echo "   ✓ pytest is installed\n";
    } else {
        echo "   ✗ pytest is not installed\n";
        $pythonOk = false;
    }
} else {
    echo "   ✗ Python virtual environment not found\n";
    $pythonOk = false;
}

// Summary
echo "\n=== SUMMARY ===\n";
if ($envOk && $filesOk && $modelsOk && $testsOk && $pythonOk) {
    echo "✓ All checks passed! Test environment is properly configured.\n";
    echo "\nYou can now run tests with:\n";
    echo "  ./vendor/bin/phpunit\n";
    echo "  source venv/bin/activate && pytest tests/python/\n";
    echo "  sudo ./setup.sh local   # deploy + full test suite\n";
    exit(0);
} else {
    echo "✗ Some checks failed. Please fix the issues above before running tests.\n";
    exit(1);
} 