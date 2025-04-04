<?php

/**
 * Garden Sensors Dashboard - Entry Point
 */

// Load configuration
require_once __DIR__ . '/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Include necessary files
require_once __DIR__ . '/includes/functions.php';

// Initialize database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASSWORD,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

// Fetch latest sensor readings
$latestReadings = [];
if (!isset($error)) {
    try {
        $stmt = $pdo->query("
            SELECT sensor_id, sensor_type, reading_value, reading_timestamp 
            FROM sensor_readings 
            WHERE reading_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY reading_timestamp DESC 
            LIMIT 10
        ");
        $latestReadings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Failed to fetch sensor readings: " . $e->getMessage();
    }
}

// Calculate averages
$averages = [
    'temperature' => 0,
    'humidity' => 0,
    'moisture' => 0
];

if (!empty($latestReadings)) {
    $counts = array_fill_keys(array_keys($averages), 0);
    foreach ($latestReadings as $reading) {
        if (isset($averages[$reading['sensor_type']])) {
            $averages[$reading['sensor_type']] += $reading['reading_value'];
            $counts[$reading['sensor_type']]++;
        }
    }
    foreach ($averages as $type => &$value) {
        if ($counts[$type] > 0) {
            $value = round($value / $counts[$type], 1);
        }
    }
}

// Set page title
$page_title = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="/" class="app-logo">
                    <i class="fas fa-leaf"></i>
                    <span><?php echo htmlspecialchars(APP_NAME); ?></span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="/" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/sensors.php" class="nav-link">
                    <i class="fas fa-microchip"></i>
                    <span>Sensors</span>
                </a>
                <a href="/readings.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Readings</span>
                </a>
                <a href="/settings.php" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-header">
                <h1 class="dashboard-title">Dashboard</h1>
                <p class="dashboard-subtitle">Overview of your garden's sensor data</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon temperature">
                        <i class="fas fa-thermometer-half"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $averages['temperature']; ?>°C</div>
                        <div class="stat-label">Average Temperature</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon humidity">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $averages['humidity']; ?>%</div>
                        <div class="stat-label">Average Humidity</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon moisture">
                        <i class="fas fa-water"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $averages['moisture']; ?>%</div>
                        <div class="stat-label">Average Soil Moisture</div>
                    </div>
                </div>
            </div>

            <!-- Recent Readings -->
            <section class="readings-section">
                <div class="readings-header">
                    <h2 class="readings-title">Recent Sensor Readings</h2>
                </div>
                <?php if (!empty($latestReadings)): ?>
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>Sensor ID</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestReadings as $reading): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reading['sensor_id']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($reading['sensor_type'])); ?></td>
                                    <td>
                                        <?php 
                                        echo htmlspecialchars($reading['reading_value']);
                                        echo $reading['sensor_type'] === 'temperature' ? '°C' : '%';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($reading['reading_timestamp']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No sensor readings available for the last 24 hours.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html> 