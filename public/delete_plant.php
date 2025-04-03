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
    sendJsonResponse(false, 'Invalid plant ID');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Update sensors to remove plant association
        $query = "UPDATE Sensors SET plant_id = NULL WHERE plant_id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        // Delete plant
        $query = "DELETE FROM Plants WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $conn->commit();
            sendJsonResponse(true, 'Plant deleted successfully');
        } else {
            throw new Exception('Failed to delete plant');
        }
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    logError('Error in delete_plant.php: ' . $e->getMessage());
    sendJsonResponse(false, 'An error occurred while deleting plant data');
} 