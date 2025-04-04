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

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all plants
    $query = "SELECT * FROM Plants ORDER BY name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse(true, 'Success', $plants);
    
} catch (Exception $e) {
    logError('Error in get_plants.php: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while fetching plants data');
} 