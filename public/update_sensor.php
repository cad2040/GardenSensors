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
$name = sanitizeInput($_POST['name'] ?? '');
$plant_id = sanitizeInput($_POST['plant_id'] ?? null);
$status = sanitizeInput($_POST['status'] ?? '');

if (empty($id) || empty($name) || empty($status)) {
    sendJsonResponse(false, 'Missing required fields');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Update sensor data
    $query = "UPDATE Sensors SET 
              name = :name,
              plant_id = :plant_id,
              status = :status,
              updated_at = NOW()
              WHERE id = :id";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':plant_id', $plant_id);
    $stmt->bindParam(':status', $status);
    
    if ($stmt->execute()) {
        sendJsonResponse(true, 'Sensor updated successfully');
    } else {
        sendJsonResponse(false, 'Failed to update sensor');
    }
    
} catch (Exception $e) {
    logError('Error in update_sensor.php: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while updating sensor data');
} 