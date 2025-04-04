<?php
/**
 * Garden Sensors GUI Installation Script
 * 
 * This script provides a web-based installation interface for the Garden Sensors GUI.
 * It guides users through the installation process and performs necessary setup tasks.
 */

// Define constants
define('INSTALL_DIR', __DIR__);
define('ROOT_DIR', dirname(__DIR__));
define('CONFIG_DIR', INSTALL_DIR . '/config');
define('CACHE_DIR', INSTALL_DIR . '/cache');
define('LOG_DIR', INSTALL_DIR . '/logs');

// Start session
session_start();

// Initialize variables
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';
$config = [];

// Load existing config if available
if (file_exists(CONFIG_DIR . '/config.php')) {
    include CONFIG_DIR . '/config.php';
}

// Function to check system requirements
function checkSystemRequirements() {
    $requirements = [
        'php' => [
            'required' => '7.4.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'extensions' => [
            'mysqli' => extension_loaded('mysqli'),
            'json' => extension_loaded('json'),
            'curl' => extension_loaded('curl'),
            'gd' => extension_loaded('gd'),
            'mbstring' => extension_loaded('mbstring'),
            'xml' => extension_loaded('xml')
        ],
        'directories' => [
            CONFIG_DIR => is_writable(CONFIG_DIR) || @chmod(CONFIG_DIR, 0755),
            CACHE_DIR => is_writable(CACHE_DIR) || @chmod(CACHE_DIR, 0755),
            LOG_DIR => is_writable(LOG_DIR) || @chmod(LOG_DIR, 0755)
        ]
    ];
    
    return $requirements;
}

// Function to create necessary directories
function createDirectories() {
    $directories = [
        CONFIG_DIR,
        CACHE_DIR,
        LOG_DIR
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                return "Failed to create directory: $dir";
            }
        }
    }
    
    return '';
}

// Function to test database connection
function testDatabaseConnection($host, $username, $password, $database) {
    try {
        $conn = new mysqli($host, $username, $password);
        
        if ($conn->connect_error) {
            return "Connection failed: " . $conn->connect_error;
        }
        
        // Check if database exists
        $result = $conn->query("SHOW DATABASES LIKE '$database'");
        if ($result->num_rows == 0) {
            // Create database
            if (!$conn->query("CREATE DATABASE `$database`")) {
                return "Failed to create database: " . $conn->error;
            }
        }
        
        // Select database
        if (!$conn->select_db($database)) {
            return "Failed to select database: " . $conn->error;
        }
        
        $conn->close();
        return '';
    } catch (Exception $e) {
        return "Database error: " . $e->getMessage();
    }
}

// Function to import database schema
function importDatabaseSchema($host, $username, $password, $database) {
    try {
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            return "Connection failed: " . $conn->connect_error;
        }
        
        // Check if schema file exists
        $schemaFile = ROOT_DIR . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            return "Schema file not found: $schemaFile";
        }
        
        // Read schema file
        $sql = file_get_contents($schemaFile);
        
        // Execute SQL
        if ($conn->multi_query($sql)) {
            do {
                // Process all result sets
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        }
        
        if ($conn->error) {
            return "Failed to import schema: " . $conn->error;
        }
        
        $conn->close();
        return '';
    } catch (Exception $e) {
        return "Database error: " . $e->getMessage();
    }
}

// Function to create config file
function createConfigFile($config) {
    $configContent = "<?php\n";
    $configContent .= "// Database configuration\n";
    $configContent .= "define('DB_HOST', '" . addslashes($config['db_host']) . "');\n";
    $configContent .= "define('DB_NAME', '" . addslashes($config['db_name']) . "');\n";
    $configContent .= "define('DB_USER', '" . addslashes($config['db_user']) . "');\n";
    $configContent .= "define('DB_PASS', '" . addslashes($config['db_pass']) . "');\n\n";
    
    $configContent .= "// Application settings\n";
    $configContent .= "define('APP_NAME', 'Garden Sensors Dashboard');\n";
    $configContent .= "define('APP_VERSION', '1.0.0');\n";
    $configContent .= "define('DEBUG_MODE', false);\n";
    $configContent .= "define('TIMEZONE', 'UTC');\n\n";
    
    $configContent .= "// Security settings\n";
    $configContent .= "define('SESSION_LIFETIME', 3600);\n";
    $configContent .= "define('MAX_LOGIN_ATTEMPTS', 5);\n";
    $configContent .= "define('LOCKOUT_TIME', 900);\n";
    $configContent .= "define('PASSWORD_RESET_EXPIRY', 3600);\n\n";
    
    $configContent .= "// File paths\n";
    $configContent .= "define('ROOT_PATH', '" . addslashes(ROOT_DIR) . "');\n";
    $configContent .= "define('INCLUDES_PATH', '" . addslashes(INSTALL_DIR . '/includes') . "');\n";
    $configContent .= "define('CACHE_PATH', '" . addslashes(CACHE_DIR) . "');\n";
    $configContent .= "define('LOG_PATH', '" . addslashes(LOG_DIR) . "');\n\n";
    
    $configContent .= "// Cache settings\n";
    $configContent .= "define('CACHE_ENABLED', true);\n";
    $configContent .= "define('CACHE_LIFETIME', 300);\n\n";
    
    $configContent .= "// Logging settings\n";
    $configContent .= "define('LOGGING_ENABLED', true);\n";
    $configContent .= "define('LOG_LEVEL', 'ERROR');\n\n";
    
    $configContent .= "// Email settings\n";
    $configContent .= "define('SMTP_HOST', '');\n";
    $configContent .= "define('SMTP_PORT', 587);\n";
    $configContent .= "define('SMTP_USER', '');\n";
    $configContent .= "define('SMTP_PASS', '');\n";
    $configContent .= "define('ALERT_EMAIL', '');\n\n";
    
    $configContent .= "// Sensor settings\n";
    $configContent .= "define('READING_INTERVAL', 3600);\n";
    $configContent .= "define('ALERT_THRESHOLD', 20);\n";
    $configContent .= "define('DATA_RETENTION_DAYS', 30);\n\n";
    
    $configContent .= "// Create necessary directories if they don't exist\n";
    $configContent .= "if (!file_exists(CACHE_PATH)) {\n";
    $configContent .= "    mkdir(CACHE_PATH, 0755, true);\n";
    $configContent .= "}\n\n";
    $configContent .= "if (!file_exists(LOG_PATH)) {\n";
    $configContent .= "    mkdir(LOG_PATH, 0755, true);\n";
    $configContent .= "}\n\n";
    
    $configContent .= "// Set timezone\n";
    $configContent .= "date_default_timezone_set(TIMEZONE);\n\n";
    
    $configContent .= "// Error reporting\n";
    $configContent .= "if (DEBUG_MODE) {\n";
    $configContent .= "    error_reporting(E_ALL);\n";
    $configContent .= "    ini_set('display_errors', 1);\n";
    $configContent .= "} else {\n";
    $configContent .= "    error_reporting(0);\n";
    $configContent .= "    ini_set('display_errors', 0);\n";
    $configContent .= "}\n";
    
    if (!@file_put_contents(CONFIG_DIR . '/config.php', $configContent)) {
        return "Failed to create config file";
    }
    
    return '';
}

// Function to create admin user
function createAdminUser($host, $username, $password, $database, $adminUser, $adminPass) {
    try {
        $conn = new mysqli($host, $username, $password, $database);
        
        if ($conn->connect_error) {
            return "Connection failed: " . $conn->connect_error;
        }
        
        // Check if users table exists
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows == 0) {
            return "Users table does not exist";
        }
        
        // Hash password
        $hashedPassword = password_hash($adminPass, PASSWORD_DEFAULT);
        
        // Check if admin user already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $adminUser);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing admin user
            $stmt = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE username = ?");
            $stmt->bind_param("ss", $hashedPassword, $adminUser);
        } else {
            // Create new admin user
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
            $stmt->bind_param("ss", $adminUser, $hashedPassword);
        }
        
        if (!$stmt->execute()) {
            return "Failed to create/update admin user: " . $stmt->error;
        }
        
        $stmt->close();
        $conn->close();
        return '';
    } catch (Exception $e) {
        return "Database error: " . $e->getMessage();
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            // Create directories
            $error = createDirectories();
            if (empty($error)) {
                header("Location: install.php?step=3");
                exit;
            }
            break;
            
        case 3:
            // Test database connection
            $config['db_host'] = $_POST['db_host'];
            $config['db_name'] = $_POST['db_name'];
            $config['db_user'] = $_POST['db_user'];
            $config['db_pass'] = $_POST['db_pass'];
            
            $error = testDatabaseConnection(
                $config['db_host'],
                $config['db_user'],
                $config['db_pass'],
                $config['db_name']
            );
            
            if (empty($error)) {
                header("Location: install.php?step=4");
                exit;
            }
            break;
            
        case 4:
            // Import database schema
            $error = importDatabaseSchema(
                $config['db_host'],
                $config['db_user'],
                $config['db_pass'],
                $config['db_name']
            );
            
            if (empty($error)) {
                header("Location: install.php?step=5");
                exit;
            }
            break;
            
        case 5:
            // Create admin user
            $adminUser = $_POST['admin_user'];
            $adminPass = $_POST['admin_pass'];
            
            $error = createAdminUser(
                $config['db_host'],
                $config['db_user'],
                $config['db_pass'],
                $config['db_name'],
                $adminUser,
                $adminPass
            );
            
            if (empty($error)) {
                header("Location: install.php?step=6");
                exit;
            }
            break;
            
        case 6:
            // Create config file
            $error = createConfigFile($config);
            
            if (empty($error)) {
                $success = "Installation completed successfully!";
                // Redirect to homepage after 3 seconds
                header("Refresh: 3; url=index.php");
            }
            break;
    }
}

// Get system requirements
$requirements = checkSystemRequirements();

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garden Sensors GUI Installation</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .step {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .step:last-child {
            border-bottom: none;
        }
        .step-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 15px;
            color: #2563eb;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .btn {
            display: inline-block;
            background-color: #2563eb;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
        .error {
            color: #dc2626;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #fee2e2;
            border-radius: 4px;
        }
        .success {
            color: #059669;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #d1fae5;
            border-radius: 4px;
        }
        .requirement {
            margin-bottom: 10px;
        }
        .requirement-name {
            font-weight: bold;
        }
        .requirement-status {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        .status-ok {
            background-color: #d1fae5;
            color: #059669;
        }
        .status-error {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background-color: #f3f4f6;
            margin: 0 5px;
            border-radius: 4px;
            position: relative;
        }
        .progress-step.active {
            background-color: #2563eb;
            color: #fff;
        }
        .progress-step.completed {
            background-color: #059669;
            color: #fff;
        }
        .progress-step:not(:last-child):after {
            content: '';
            position: absolute;
            top: 50%;
            right: -10px;
            width: 20px;
            height: 2px;
            background-color: #d1d5db;
            z-index: 1;
        }
        .progress-step.active:not(:last-child):after,
        .progress-step.completed:not(:last-child):after {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Garden Sensors GUI Installation</h1>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="progress">
            <div class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1. Welcome</div>
            <div class="progress-step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">2. Requirements</div>
            <div class="progress-step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">3. Database</div>
            <div class="progress-step <?php echo $step >= 4 ? 'active' : ''; ?> <?php echo $step > 4 ? 'completed' : ''; ?>">4. Schema</div>
            <div class="progress-step <?php echo $step >= 5 ? 'active' : ''; ?> <?php echo $step > 5 ? 'completed' : ''; ?>">5. Admin</div>
            <div class="progress-step <?php echo $step >= 6 ? 'active' : ''; ?> <?php echo $step > 6 ? 'completed' : ''; ?>">6. Complete</div>
        </div>
        
        <?php if ($step == 1): ?>
            <div class="step">
                <div class="step-title">Welcome to the Garden Sensors GUI Installation</div>
                <p>This wizard will guide you through the installation process for the Garden Sensors GUI.</p>
                <p>Before proceeding, please make sure you have the following:</p>
                <ul>
                    <li>PHP 7.4 or higher</li>
                    <li>Apache web server</li>
                    <li>MySQL database</li>
                    <li>Required PHP extensions (mysqli, json, curl, gd, mbstring, xml)</li>
                </ul>
                <p>Click the button below to start the installation process.</p>
                <form method="get">
                    <input type="hidden" name="step" value="2">
                    <button type="submit" class="btn">Start Installation</button>
                </form>
            </div>
        <?php elseif ($step == 2): ?>
            <div class="step">
                <div class="step-title">System Requirements</div>
                <p>Please check if your system meets the following requirements:</p>
                
                <div class="requirement">
                    <span class="requirement-name">PHP Version</span>
                    <span class="requirement-status <?php echo $requirements['php']['status'] ? 'status-ok' : 'status-error'; ?>">
                        <?php echo $requirements['php']['current']; ?> (Required: <?php echo $requirements['php']['required']; ?>)
                    </span>
                </div>
                
                <div class="requirement">
                    <span class="requirement-name">PHP Extensions</span>
                </div>
                <?php foreach ($requirements['extensions'] as $ext => $installed): ?>
                    <div class="requirement">
                        <span class="requirement-name"><?php echo $ext; ?></span>
                        <span class="requirement-status <?php echo $installed ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $installed ? 'Installed' : 'Not Installed'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                
                <div class="requirement">
                    <span class="requirement-name">Directory Permissions</span>
                </div>
                <?php foreach ($requirements['directories'] as $dir => $writable): ?>
                    <div class="requirement">
                        <span class="requirement-name"><?php echo $dir; ?></span>
                        <span class="requirement-status <?php echo $writable ? 'status-ok' : 'status-error'; ?>">
                            <?php echo $writable ? 'Writable' : 'Not Writable'; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
                
                <?php
                $allRequirementsMet = $requirements['php']['status'] && 
                                     !in_array(false, $requirements['extensions']) && 
                                     !in_array(false, $requirements['directories']);
                ?>
                
                <?php if ($allRequirementsMet): ?>
                    <p>All system requirements are met. You can proceed with the installation.</p>
                    <form method="get">
                        <input type="hidden" name="step" value="3">
                        <button type="submit" class="btn">Continue</button>
                    </form>
                <?php else: ?>
                    <p>Some system requirements are not met. Please fix the issues before proceeding.</p>
                    <form method="get">
                        <input type="hidden" name="step" value="2">
                        <button type="submit" class="btn">Check Again</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php elseif ($step == 3): ?>
            <div class="step">
                <div class="step-title">Database Configuration</div>
                <p>Please enter your database connection details:</p>
                
                <form method="post">
                    <div class="form-group">
                        <label for="db_host">Database Host</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_name">Database Name</label>
                        <input type="text" id="db_name" name="db_name" value="garden_sensors" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_user">Database Username</label>
                        <input type="text" id="db_user" name="db_user" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="db_pass">Database Password</label>
                        <input type="password" id="db_pass" name="db_pass" required>
                    </div>
                    
                    <button type="submit" class="btn">Test Connection</button>
                </form>
            </div>
        <?php elseif ($step == 4): ?>
            <div class="step">
                <div class="step-title">Database Schema</div>
                <p>The database connection was successful. Click the button below to import the database schema.</p>
                
                <form method="post">
                    <button type="submit" class="btn">Import Schema</button>
                </form>
            </div>
        <?php elseif ($step == 5): ?>
            <div class="step">
                <div class="step-title">Create Admin User</div>
                <p>Please create an admin user to access the Garden Sensors GUI:</p>
                
                <form method="post">
                    <div class="form-group">
                        <label for="admin_user">Admin Username</label>
                        <input type="text" id="admin_user" name="admin_user" value="admin" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_pass">Admin Password</label>
                        <input type="password" id="admin_pass" name="admin_pass" required>
                    </div>
                    
                    <button type="submit" class="btn">Create Admin User</button>
                </form>
            </div>
        <?php elseif ($step == 6): ?>
            <div class="step">
                <div class="step-title">Installation Complete</div>
                <p>The Garden Sensors GUI has been successfully installed!</p>
                <p>You can now access the Garden Sensors GUI at: <a href="index.php">index.php</a></p>
                <p>Please delete the install.php file for security reasons.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 