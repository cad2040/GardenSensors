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

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Get action from POST data
$action = $_POST['action'] ?? '';
$plantId = isset($_POST['id']) ? (int)$_POST['id'] : null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    switch ($action) {
        case 'add':
            // Validate required fields
            $requiredFields = ['name', 'type', 'location', 'min_moisture', 'max_moisture'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Validate moisture values
            $minMoisture = (int)$_POST['min_moisture'];
            $maxMoisture = (int)$_POST['max_moisture'];
            
            if ($minMoisture < 0 || $minMoisture > 100) {
                throw new Exception('Minimum moisture must be between 0 and 100');
            }
            
            if ($maxMoisture < 0 || $maxMoisture > 100) {
                throw new Exception('Maximum moisture must be between 0 and 100');
            }
            
            if ($minMoisture >= $maxMoisture) {
                throw new Exception('Minimum moisture must be less than maximum moisture');
            }
            
            // Insert new plant
            $query = "
                INSERT INTO Plants (
                    user_id,
                    name,
                    type,
                    location,
                    min_moisture,
                    max_moisture,
                    notes
                ) VALUES (
                    :user_id,
                    :name,
                    :type,
                    :location,
                    :min_moisture,
                    :max_moisture,
                    :notes
                )
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':name' => sanitizeInput($_POST['name']),
                ':type' => sanitizeInput($_POST['type']),
                ':location' => sanitizeInput($_POST['location']),
                ':min_moisture' => $minMoisture,
                ':max_moisture' => $maxMoisture,
                ':notes' => !empty($_POST['notes']) ? sanitizeInput($_POST['notes']) : null
            ]);
            
            $plantId = $conn->lastInsertId();
            $message = 'Plant added successfully';
            break;
            
        case 'edit':
            if (!$plantId) {
                throw new Exception('Plant ID is required for editing');
            }
            
            // Validate required fields
            $requiredFields = ['name', 'type', 'location', 'min_moisture', 'max_moisture'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Validate moisture values
            $minMoisture = (int)$_POST['min_moisture'];
            $maxMoisture = (int)$_POST['max_moisture'];
            
            if ($minMoisture < 0 || $minMoisture > 100) {
                throw new Exception('Minimum moisture must be between 0 and 100');
            }
            
            if ($maxMoisture < 0 || $maxMoisture > 100) {
                throw new Exception('Maximum moisture must be between 0 and 100');
            }
            
            if ($minMoisture >= $maxMoisture) {
                throw new Exception('Minimum moisture must be less than maximum moisture');
            }
            
            // Update plant
            $query = "
                UPDATE Plants
                SET 
                    name = :name,
                    type = :type,
                    location = :location,
                    min_moisture = :min_moisture,
                    max_moisture = :max_moisture,
                    notes = :notes,
                    updated_at = NOW()
                WHERE id = :plant_id AND user_id = :user_id
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':plant_id' => $plantId,
                ':user_id' => $_SESSION['user_id'],
                ':name' => sanitizeInput($_POST['name']),
                ':type' => sanitizeInput($_POST['type']),
                ':location' => sanitizeInput($_POST['location']),
                ':min_moisture' => $minMoisture,
                ':max_moisture' => $maxMoisture,
                ':notes' => !empty($_POST['notes']) ? sanitizeInput($_POST['notes']) : null
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Plant not found or unauthorized');
            }
            
            $message = 'Plant updated successfully';
            break;
            
        case 'delete':
            if (!$plantId) {
                throw new Exception('Plant ID is required for deletion');
            }
            
            // Check if plant has associated sensors
            $query = "SELECT COUNT(*) as count FROM Sensors WHERE plant_id = :plant_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':plant_id' => $plantId,
                ':user_id' => $_SESSION['user_id']
            ]);
            $sensorCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($sensorCount > 0) {
                throw new Exception('Cannot delete plant with associated sensors');
            }
            
            // Delete plant
            $query = "DELETE FROM Plants WHERE id = :plant_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':plant_id' => $plantId,
                ':user_id' => $_SESSION['user_id']
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Plant not found or unauthorized');
            }
            
            $message = 'Plant deleted successfully';
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the action
    logSystemEvent(
        $_SESSION['user_id'],
        "plant_{$action}",
        "Plant {$action}: " . ($plantId ? "ID={$plantId}" : "new")
    );
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'plant_id' => $plantId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    logError('Error in manage_plant.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 