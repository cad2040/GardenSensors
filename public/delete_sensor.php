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
$id = sanitizeInput($_POST['id'] ?? '');
if (empty($id)) {
    sendJsonResponse(false, 'Invalid sensor ID');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Delete sensor readings first
        $query = "DELETE FROM Readings WHERE sensor_id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Delete sensor alerts
        $query = "DELETE FROM Alerts WHERE sensor_id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Delete sensor
        $query = "DELETE FROM Sensors WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $conn->commit();
            sendJsonResponse(true, 'Sensor deleted successfully');
        } else {
            throw new Exception('Failed to delete sensor');
        }
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logError('Error in delete_sensor.php: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while deleting sensor data');
} 