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
$type = sanitizeInput($_POST['type'] ?? '');
$location = sanitizeInput($_POST['location'] ?? '');

if (empty($id) || empty($name) || empty($type) || empty($location)) {
    sendJsonResponse(false, 'Missing required fields');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Update plant data
    $query = "UPDATE Plants SET 
              name = :name,
              type = :type,
              location = :location,
              updated_at = NOW()
              WHERE id = :id";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':location', $location);
    
    if ($stmt->execute()) {
        sendJsonResponse(true, 'Plant updated successfully');
    } else {
        sendJsonResponse(false, 'Failed to update plant');
    }
    
} catch (Exception $e) {
    logError('Error in update_plant.php: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while updating plant data');
} 