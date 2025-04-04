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

// Fetch all sensors with their latest readings
$sensors = [];
if (!isset($error)) {
    try {
        $stmt = $pdo->query("
            SELECT s.*, 
                   COALESCE(lr.reading_value, 'N/A') as last_reading,
                   COALESCE(lr.reading_timestamp, 'Never') as last_reading_time
            FROM sensors s
            LEFT JOIN (
                SELECT sensor_id, reading_value, reading_timestamp
                FROM sensor_readings sr1
                WHERE reading_timestamp = (
                    SELECT MAX(reading_timestamp)
                    FROM sensor_readings sr2
                    WHERE sr1.sensor_id = sr2.sensor_id
                )
            ) lr ON s.id = lr.sensor_id
            ORDER BY s.id ASC
        ");
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Failed to fetch sensors: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensors - <?php echo htmlspecialchars(APP_NAME); ?></title>
    
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
                <a href="/sensors.php" class="nav-link active">
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
                <h1 class="dashboard-title">Sensors</h1>
                <p class="dashboard-subtitle">Manage and monitor your garden sensors</p>
            </div>

            <!-- Sensors List -->
            <section class="readings-section">
                <div class="readings-header">
                    <h2 class="readings-title">Available Sensors</h2>
                    <div class="readings-actions">
                        <a href="add_sensor.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add New Sensor
                        </a>
                    </div>
                </div>
                <?php if (!empty($sensors)): ?>
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Last Reading</th>
                                <th>Last Update</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sensors as $sensor): ?>
                                <?php
                                $status = 'active';
                                $statusClass = 'status-active';
                                if ($sensor['last_reading_time'] === 'Never' || 
                                    strtotime($sensor['last_reading_time']) < strtotime('-1 hour')) {
                                    $status = 'inactive';
                                    $statusClass = 'status-inactive';
                                }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sensor['id']); ?></td>
                                    <td><?php echo htmlspecialchars($sensor['name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($sensor['type'])); ?></td>
                                    <td><?php echo htmlspecialchars($sensor['location']); ?></td>
                                    <td>
                                        <?php 
                                        if ($sensor['last_reading'] !== 'N/A') {
                                            echo htmlspecialchars($sensor['last_reading']);
                                            echo $sensor['type'] === 'temperature' ? '°C' : '%';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($sensor['last_reading_time'] !== 'Never') {
                                            echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($sensor['last_reading_time'])));
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_sensor.php?id=<?php echo $sensor['id']; ?>" 
                                           class="action-link" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_sensor.php?id=<?php echo $sensor['id']; ?>" 
                                           class="action-link" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#" 
                                           onclick="deleteSensor(<?php echo $sensor['id']; ?>)" 
                                           class="action-link text-danger" 
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No sensors have been added yet.</p>
                        <a href="add_sensor.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Your First Sensor
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
    function deleteSensor(sensorId) {
        if (confirm('Are you sure you want to delete this sensor? This action cannot be undone.')) {
            window.location.href = 'delete_sensor.php?id=' + sensorId;
        }
    }
    </script>
</body>
</html> 