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

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all sensors with their latest readings and associated plant information
    $query = "
        SELECT 
            s.*,
            p.name as plant_name,
            r.value as last_reading,
            r.timestamp as last_updated,
            r.status
        FROM Sensors s
        LEFT JOIN Plants p ON s.plant_id = p.id
        LEFT JOIN (
            SELECT 
                sensor_id,
                value,
                timestamp,
                status,
                ROW_NUMBER() OVER (PARTITION BY sensor_id ORDER BY timestamp DESC) as rn
            FROM Readings
        ) r ON s.id = r.sensor_id AND r.rn = 1
        WHERE s.user_id = :user_id
        ORDER BY s.name ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    foreach ($sensors as &$sensor) {
        $sensor['last_reading'] = formatReading($sensor['last_reading'], $sensor['type']);
        $sensor['last_updated'] = formatTimestamp($sensor['last_updated']);
        $sensor['status'] = $sensor['status'] ?? 'inactive';
    }
    
    echo json_encode([
        'success' => true,
        'sensors' => $sensors
    ]);
    
} catch (Exception $e) {
    logError('Error fetching sensors: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching sensors'
    ]);
} 