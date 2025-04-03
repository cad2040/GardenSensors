<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Please log in to access this page']);
    exit;
}

// Validate input
$tab = sanitizeInput($_GET['tab'] ?? '');
$isDashboard = isset($_GET['dashboard']) && $_GET['dashboard'] === '1';

if (empty($tab)) {
    sendJsonResponse(false, 'Invalid tab specified');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get tab content based on the requested tab
    $content = '';
    switch ($tab) {
        case 'sensors':
            if ($isDashboard) {
                $content = getDashboardSensorsContent($conn);
            } else {
                $content = getSensorsContent($conn);
            }
            break;
            
        case 'plants':
            if ($isDashboard) {
                $content = getDashboardPlantsContent($conn);
            } else {
                $content = getPlantsContent($conn);
            }
            break;
            
        case 'readings':
            if ($isDashboard) {
                $content = getDashboardReadingsContent($conn);
            } else {
                $content = getReadingsContent($conn);
            }
            break;
            
        case 'settings':
            if ($isDashboard) {
                $content = getDashboardAlertsContent($conn);
            } else {
                $content = getSettingsContent($conn);
            }
            break;
            
        default:
            sendJsonResponse(false, 'Invalid tab specified');
            exit;
    }
    
    sendJsonResponse(true, 'Success', $content);
    
} catch (Exception $e) {
    logError('Error in get_tab_data.php: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while fetching data');
}

// Helper functions for generating tab content
function getSensorsContent($conn) {
    $query = "SELECT s.*, p.name as plant_name 
              FROM Sensors s 
              LEFT JOIN Plants p ON s.plant_id = p.id 
              ORDER BY s.name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="sensors-grid">
        <?php foreach ($sensors as $sensor): ?>
            <div class="sensor-card">
                <h3><?php echo htmlspecialchars($sensor['name']); ?></h3>
                <div class="sensor-info">
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($sensor['status']); ?></p>
                    <p><strong>Plant:</strong> <?php echo htmlspecialchars($sensor['plant_name'] ?? 'Unassigned'); ?></p>
                    <p><strong>Last Reading:</strong> <?php echo $sensor['last_reading'] ? date('M j, Y H:i', strtotime($sensor['last_reading'])) : 'No readings'; ?></p>
                </div>
                <div class="sensor-actions">
                    <button class="btn btn-primary" onclick="editSensor(<?php echo $sensor['id']; ?>)">Edit</button>
                    <button class="btn btn-danger" onclick="deleteSensor(<?php echo $sensor['id']; ?>)">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function getPlantsContent($conn) {
    $query = "SELECT p.*, COUNT(s.id) as sensor_count 
              FROM Plants p 
              LEFT JOIN Sensors s ON p.id = s.plant_id 
              GROUP BY p.id 
              ORDER BY p.name";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="plants-grid">
        <?php foreach ($plants as $plant): ?>
            <div class="plant-card">
                <h3><?php echo htmlspecialchars($plant['name']); ?></h3>
                <div class="plant-info">
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($plant['type']); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($plant['location']); ?></p>
                    <p><strong>Sensor Count:</strong> <?php echo $plant['sensor_count']; ?></p>
                </div>
                <div class="plant-actions">
                    <button class="btn btn-primary" onclick="editPlant(<?php echo $plant['id']; ?>)">Edit</button>
                    <button class="btn btn-danger" onclick="deletePlant(<?php echo $plant['id']; ?>)">Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

function getReadingsContent($conn) {
    $query = "SELECT r.*, s.name as sensor_name, p.name as plant_name 
              FROM Readings r 
              JOIN Sensors s ON r.sensor_id = s.id 
              LEFT JOIN Plants p ON s.plant_id = p.id 
              ORDER BY r.timestamp DESC 
              LIMIT 100";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="readings-table-container">
        <table class="readings-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Sensor</th>
                    <th>Plant</th>
                    <th>Moisture</th>
                    <th>Temperature</th>
                    <th>Humidity</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($readings as $reading): ?>
                    <tr>
                        <td><?php echo formatTimestamp($reading['timestamp']); ?></td>
                        <td><?php echo htmlspecialchars($reading['sensor_name']); ?></td>
                        <td><?php echo htmlspecialchars($reading['plant_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo formatMoisture($reading['moisture']); ?></td>
                        <td><?php echo formatTemperature($reading['temperature']); ?></td>
                        <td><?php echo formatHumidity($reading['humidity']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean();
}

function getSettingsContent($conn) {
    // Get system settings
    $query = "SELECT * FROM Settings ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="settings-container">
        <form id="settings-form" action="update_settings.php" method="POST">
            <?php foreach ($settings as $setting): ?>
                <div class="form-group">
                    <label class="form-label" for="<?php echo $setting['key']; ?>">
                        <?php echo htmlspecialchars($setting['name']); ?>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="<?php echo $setting['key']; ?>" 
                           name="<?php echo $setting['key']; ?>" 
                           value="<?php echo htmlspecialchars($setting['value']); ?>">
                    <small class="form-text text-muted"><?php echo htmlspecialchars($setting['description']); ?></small>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Add new functions for dashboard content
function getDashboardSensorsContent($conn) {
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
              FROM Sensors";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="dashboard-stats">
        <div class="stat-item">
            <span class="stat-label">Total Sensors</span>
            <span class="stat-value"><?php echo $stats['total']; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Active</span>
            <span class="stat-value stat-success"><?php echo $stats['active']; ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">Inactive</span>
            <span class="stat-value stat-danger"><?php echo $stats['inactive']; ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function getDashboardPlantsContent($conn) {
    $query = "SELECT 
                p.name,
                COALESCE(AVG(r.moisture), 0) as avg_moisture,
                COALESCE(AVG(r.temperature), 0) as avg_temperature,
                COALESCE(AVG(r.humidity), 0) as avg_humidity
              FROM Plants p
              LEFT JOIN Sensors s ON p.id = s.plant_id
              LEFT JOIN Readings r ON s.id = r.sensor_id
              WHERE r.timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              GROUP BY p.id, p.name
              ORDER BY p.name
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="plant-health-list">
        <?php if (empty($plants)): ?>
            <p class="no-data">No plant data available</p>
        <?php else: ?>
            <?php foreach ($plants as $plant): ?>
                <div class="plant-health-item">
                    <h4><?php echo htmlspecialchars($plant['name']); ?></h4>
                    <div class="health-metrics">
                        <div class="metric">
                            <span class="metric-label">Moisture</span>
                            <span class="metric-value"><?php echo number_format($plant['avg_moisture'], 1); ?>%</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Temperature</span>
                            <span class="metric-value"><?php echo number_format($plant['avg_temperature'], 1); ?>°C</span>
                        </div>
                        <div class="metric">
                            <span class="metric-label">Humidity</span>
                            <span class="metric-value"><?php echo number_format($plant['avg_humidity'], 1); ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function getDashboardReadingsContent($conn) {
    $query = "SELECT 
                r.*,
                s.name as sensor_name,
                p.name as plant_name
              FROM Readings r
              JOIN Sensors s ON r.sensor_id = s.id
              LEFT JOIN Plants p ON s.plant_id = p.id
              ORDER BY r.timestamp DESC
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="recent-readings-list">
        <?php if (empty($readings)): ?>
            <p class="no-data">No recent readings available</p>
        <?php else: ?>
            <?php foreach ($readings as $reading): ?>
                <div class="reading-item">
                    <div class="reading-header">
                        <span class="sensor-name"><?php echo htmlspecialchars($reading['sensor_name']); ?></span>
                        <span class="reading-time"><?php echo date('M j, Y H:i', strtotime($reading['timestamp'])); ?></span>
                    </div>
                    <div class="reading-metrics">
                        <div class="metric">
                            <i class="fas fa-tint"></i>
                            <span class="metric-value"><?php echo number_format($reading['moisture'], 1); ?>%</span>
                        </div>
                        <div class="metric">
                            <i class="fas fa-thermometer-half"></i>
                            <span class="metric-value"><?php echo number_format($reading['temperature'], 1); ?>°C</span>
                        </div>
                        <div class="metric">
                            <i class="fas fa-water"></i>
                            <span class="metric-value"><?php echo number_format($reading['humidity'], 1); ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

function getDashboardAlertsContent($conn) {
    $query = "SELECT 
                a.*,
                s.name as sensor_name,
                p.name as plant_name
              FROM Alerts a
              JOIN Sensors s ON a.sensor_id = s.id
              LEFT JOIN Plants p ON s.plant_id = p.id
              WHERE a.status = 'active'
              ORDER BY a.timestamp DESC
              LIMIT 5";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ob_start();
    ?>
    <div class="alerts-list">
        <?php if (empty($alerts)): ?>
            <p class="no-data">No active alerts</p>
        <?php else: ?>
            <?php foreach ($alerts as $alert): ?>
                <div class="alert-item alert-<?php echo htmlspecialchars($alert['severity']); ?>">
                    <div class="alert-header">
                        <span class="alert-title"><?php echo htmlspecialchars($alert['title']); ?></span>
                        <span class="alert-time"><?php echo date('M j, Y H:i', strtotime($alert['timestamp'])); ?></span>
                    </div>
                    <p class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></p>
                    <div class="alert-meta">
                        <span class="sensor-name"><?php echo htmlspecialchars($alert['sensor_name']); ?></span>
                        <?php if ($alert['plant_name']): ?>
                            <span class="plant-name"><?php echo htmlspecialchars($alert['plant_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Helper function to send JSON responses
function sendJsonResponse($success, $message, $content = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'content' => $content
    ]);
    exit;
} 