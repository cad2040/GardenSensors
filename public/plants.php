<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

// Fetch all plants with their linked sensors
$plants = [];
if (!isset($error)) {
    try {
        // First try to get plants for the current user
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   COUNT(ps.sensor_id) as sensor_count,
                   GROUP_CONCAT(s.name SEPARATOR ', ') as sensor_names
            FROM plants p
            LEFT JOIN plant_sensors ps ON p.id = ps.plant_id
            LEFT JOIN sensors s ON ps.sensor_id = s.id
            WHERE p.user_id = ?
            GROUP BY p.id
            ORDER BY p.name ASC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no plants found and user is not admin, also show test plants (user_id = 1) for development
        if (empty($plants) && $_SESSION['user_id'] != 1) {
            $stmt = $pdo->prepare("
                SELECT p.*, 
                       COUNT(ps.sensor_id) as sensor_count,
                       GROUP_CONCAT(s.name SEPARATOR ', ') as sensor_names
                FROM plants p
                LEFT JOIN plant_sensors ps ON p.id = ps.plant_id
                LEFT JOIN sensors s ON ps.sensor_id = s.id
                WHERE p.user_id = 1 AND p.name IN ('Tomato Plant', 'Basil Plant', 'Lettuce Plant')
                GROUP BY p.id
                ORDER BY p.name ASC
            ");
            $stmt->execute();
            $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $error = "Failed to fetch plants: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plants - <?php echo htmlspecialchars(APP_NAME); ?></title>
    
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
                <a href="/plants.php" class="nav-link active">
                    <i class="fas fa-seedling"></i>
                    <span>Plants</span>
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
                <h1 class="dashboard-title">Plants</h1>
                <p class="dashboard-subtitle">Manage your plants and their watering thresholds</p>
            </div>

            <!-- Plants List -->
            <section class="readings-section">
                <div class="readings-header">
                    <h2 class="readings-title">Your Plants</h2>
                    <div class="readings-actions">
                        <a href="add_plant.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add New Plant
                        </a>
                    </div>
                </div>
                <?php if (!empty($plants)): ?>
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Species</th>
                                <th>Location</th>
                                <th>Moisture Range</th>
                                <th>Watering Frequency</th>
                                <th>Linked Sensors</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plants as $plant): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($plant['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($plant['species'] ?: 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($plant['location'] ?: 'N/A'); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($plant['min_soil_moisture']); ?>% - 
                                        <?php echo htmlspecialchars($plant['max_soil_moisture']); ?>%
                                    </td>
                                    <td><?php echo htmlspecialchars($plant['watering_frequency']); ?> hours</td>
                                    <td>
                                        <?php if ($plant['sensor_count'] > 0): ?>
                                            <span class="badge"><?php echo $plant['sensor_count']; ?> sensor(s)</span>
                                            <small style="display: block; color: #666; margin-top: 4px;">
                                                <?php echo htmlspecialchars($plant['sensor_names']); ?>
                                            </small>
                                        <?php else: ?>
                                            <span style="color: #999;">No sensors</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $plant['status']; ?>">
                                            <?php echo ucfirst($plant['status']); ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="edit_plant.php?id=<?php echo $plant['id']; ?>" 
                                           class="action-link" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="view_plant.php?id=<?php echo $plant['id']; ?>" 
                                           class="action-link" 
                                           title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="#" 
                                           onclick="deletePlant(<?php echo $plant['id']; ?>)" 
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
                        <p>No plants have been added yet.</p>
                        <a href="add_plant.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Your First Plant
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
    function deletePlant(plantId) {
        if (confirm('Are you sure you want to delete this plant? This action cannot be undone.')) {
            window.location.href = 'delete_plant.php?id=' + plantId;
        }
    }
    </script>
</body>
</html>

