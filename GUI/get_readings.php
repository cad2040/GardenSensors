<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate input parameters
$sensorId = isset($_GET['sensor_id']) ? (int)$_GET['sensor_id'] : null;
$timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : '24h';

// Validate time range
$validTimeRanges = ['1h', '24h', '7d', '30d'];
if (!in_array($timeRange, $validTimeRanges)) {
    $timeRange = '24h';
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Calculate the start time based on the time range
    $startTime = date('Y-m-d H:i:s', strtotime("-{$timeRange}"));
    
    // Build the query
    $query = "
        SELECT 
            r.*,
            s.type,
            s.name as sensor_name
        FROM Readings r
        JOIN Sensors s ON r.sensor_id = s.id
        WHERE s.user_id = :user_id
    ";
    
    $params = [':user_id' => $_SESSION['user_id']];
    
    if ($sensorId) {
        $query .= " AND r.sensor_id = :sensor_id";
        $params[':sensor_id'] = $sensorId;
    }
    
    $query .= " AND r.timestamp >= :start_time ORDER BY r.timestamp ASC";
    $params[':start_time'] = $startTime;
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    foreach ($readings as &$reading) {
        $reading['value'] = formatReading($reading['value'], $reading['type']);
        $reading['timestamp'] = formatTimestamp($reading['timestamp']);
        $reading['status'] = $reading['status'] ?? 'inactive';
    }
    
    echo json_encode([
        'success' => true,
        'readings' => $readings
    ]);
    
} catch (Exception $e) {
    logError('Error fetching readings: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching readings'
    ]);
} 