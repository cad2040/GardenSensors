<?php
require_once 'config.php';
require_once 'includes/functions.php';

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

// Get filter parameters
$sensor_type = isset($_GET['type']) ? $_GET['type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch readings with filters
$readings = [];
if (!isset($error)) {
    try {
        $sql = "
            SELECT 
                sr.id,
                s.name as sensor_name,
                s.type as sensor_type,
                s.location,
                sr.reading_value,
                sr.reading_timestamp
            FROM sensor_readings sr
            JOIN sensors s ON sr.sensor_id = s.id
            WHERE sr.reading_timestamp BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)
        ";
        
        $params = [$start_date, $end_date];
        
        if ($sensor_type) {
            $sql .= " AND s.type = ?";
            $params[] = $sensor_type;
        }
        
        $sql .= " ORDER BY sr.reading_timestamp DESC LIMIT 1000";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Failed to fetch readings: " . $e->getMessage();
    }
}

// Get unique sensor types for filter
$sensor_types = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT type FROM sensors ORDER BY type");
    $sensor_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Silently fail, not critical
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Readings - <?php echo htmlspecialchars(APP_NAME); ?></title>
    
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
                <a href="/" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/sensors.php" class="nav-link">
                    <i class="fas fa-microchip"></i>
                    <span>Sensors</span>
                </a>
                <a href="/readings.php" class="nav-link active">
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
                <h1 class="dashboard-title">Sensor Readings</h1>
                <p class="dashboard-subtitle">View and analyze historical sensor data</p>
            </div>

            <!-- Filter Form -->
            <section class="filter-section">
                <form action="readings.php" method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="type">Sensor Type</label>
                        <select name="type" id="type" class="form-control">
                            <option value="">All Types</option>
                            <?php foreach ($sensor_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"
                                        <?php echo $type === $sensor_type ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" 
                               name="start_date" 
                               id="start_date" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($start_date); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" 
                               name="end_date" 
                               id="end_date" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($end_date); ?>">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </section>

            <!-- Readings Table -->
            <section class="readings-section">
                <div class="readings-header">
                    <h2 class="readings-title">Historical Data</h2>
                    <div class="readings-actions">
                        <button class="btn btn-secondary" onclick="exportData()">
                            <i class="fas fa-download"></i>
                            Export CSV
                        </button>
                    </div>
                </div>
                <?php if (!empty($readings)): ?>
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>Sensor</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Value</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($readings as $reading): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reading['sensor_name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($reading['sensor_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($reading['location']); ?></td>
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
                        <p>No readings found for the selected filters.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
    function exportData() {
        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type') || '';
        const startDate = urlParams.get('start_date') || '';
        const endDate = urlParams.get('end_date') || '';
        
        window.location.href = `export_readings.php?type=${type}&start_date=${startDate}&end_date=${endDate}`;
    }
    </script>
</body>
</html> 