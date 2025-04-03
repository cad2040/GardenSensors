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
    sendJsonResponse(false, 'Invalid plant ID');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get plant data
    $query = "SELECT * FROM Plants WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    
    $plant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($plant) {
        sendJsonResponse(true, 'Success', $plant);
    } else {
        sendJsonResponse(false, 'Plant not found');
    }
    
} catch (Exception $e) {
    logError('Error in get_plant.php: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while fetching plant data');
} 