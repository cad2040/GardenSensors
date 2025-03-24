<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'User not authenticated');
    exit;
}

// Validate input
$tab = sanitizeInput($_GET['tab'] ?? '');
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
            $content = getSensorsContent($conn);
            break;
            
        case 'plants':
            $content = getPlantsContent($conn);
            break;
            
        case 'readings':
            $content = getReadingsContent($conn);
            break;
            
        case 'settings':
            $content = getSettingsContent($conn);
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
                    <p><strong>Last Reading:</strong> <?php echo formatTimestamp($sensor['last_reading']); ?></p>
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
    $query = "SELECT * FROM SystemSettings";
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
                        <?php echo htmlspecialchars($setting['description']); ?>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="<?php echo $setting['key']; ?>" 
                           name="<?php echo $setting['key']; ?>" 
                           value="<?php echo htmlspecialchars($setting['value']); ?>">
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Save Settings</button>
        </form>
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