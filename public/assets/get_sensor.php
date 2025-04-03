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
$id = sanitizeInput($_GET['id'] ?? '');
if (empty($id)) {
    sendJsonResponse(false, 'Invalid sensor ID');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get sensor data
    $query = "SELECT * FROM Sensors WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $sensor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sensor) {
        sendJsonResponse(true, 'Success', $sensor);
    } else {
        sendJsonResponse(false, 'Sensor not found');
    }
    
} catch (Exception $e) {
    logError('Error in get_sensor.php: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while fetching sensor data');
} 