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
$sensorId = isset($_POST['id']) ? (int)$_POST['id'] : null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    switch ($action) {
        case 'add':
            // Validate required fields
            $requiredFields = ['name', 'type', 'pin'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Validate sensor type
            $validTypes = ['moisture', 'temperature', 'humidity'];
            if (!in_array($_POST['type'], $validTypes)) {
                throw new Exception('Invalid sensor type');
            }
            
            // Validate pin number
            $pin = (int)$_POST['pin'];
            if ($pin < 0 || $pin > 13) { // Assuming Arduino Uno with 14 digital pins
                throw new Exception('Invalid pin number');
            }
            
            // Check if pin is already in use
            $query = "SELECT id FROM Sensors WHERE pin = :pin AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':pin' => $pin, ':user_id' => $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception('Pin is already in use');
            }
            
            // Insert new sensor
            $query = "
                INSERT INTO Sensors (
                    user_id,
                    name,
                    type,
                    pin,
                    plant_id,
                    status
                ) VALUES (
                    :user_id,
                    :name,
                    :type,
                    :pin,
                    :plant_id,
                    'inactive'
                )
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':name' => sanitizeInput($_POST['name']),
                ':type' => $_POST['type'],
                ':pin' => $pin,
                ':plant_id' => !empty($_POST['plant_id']) ? (int)$_POST['plant_id'] : null
            ]);
            
            $sensorId = $conn->lastInsertId();
            $message = 'Sensor added successfully';
            break;
            
        case 'edit':
            if (!$sensorId) {
                throw new Exception('Sensor ID is required for editing');
            }
            
            // Validate required fields
            $requiredFields = ['name', 'type', 'pin'];
            foreach ($requiredFields as $field) {
                if (!isset($_POST[$field]) || empty($_POST[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
            }
            
            // Validate sensor type
            $validTypes = ['moisture', 'temperature', 'humidity'];
            if (!in_array($_POST['type'], $validTypes)) {
                throw new Exception('Invalid sensor type');
            }
            
            // Validate pin number
            $pin = (int)$_POST['pin'];
            if ($pin < 0 || $pin > 13) {
                throw new Exception('Invalid pin number');
            }
            
            // Check if pin is already in use by another sensor
            $query = "SELECT id FROM Sensors WHERE pin = :pin AND user_id = :user_id AND id != :sensor_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':pin' => $pin,
                ':user_id' => $_SESSION['user_id'],
                ':sensor_id' => $sensorId
            ]);
            if ($stmt->fetch()) {
                throw new Exception('Pin is already in use');
            }
            
            // Update sensor
            $query = "
                UPDATE Sensors
                SET 
                    name = :name,
                    type = :type,
                    pin = :pin,
                    plant_id = :plant_id,
                    updated_at = NOW()
                WHERE id = :sensor_id AND user_id = :user_id
            ";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':sensor_id' => $sensorId,
                ':user_id' => $_SESSION['user_id'],
                ':name' => sanitizeInput($_POST['name']),
                ':type' => $_POST['type'],
                ':pin' => $pin,
                ':plant_id' => !empty($_POST['plant_id']) ? (int)$_POST['plant_id'] : null
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Sensor not found or unauthorized');
            }
            
            $message = 'Sensor updated successfully';
            break;
            
        case 'delete':
            if (!$sensorId) {
                throw new Exception('Sensor ID is required for deletion');
            }
            
            // Delete sensor and its readings
            $query = "DELETE FROM Sensors WHERE id = :sensor_id AND user_id = :user_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':sensor_id' => $sensorId,
                ':user_id' => $_SESSION['user_id']
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Sensor not found or unauthorized');
            }
            
            $message = 'Sensor deleted successfully';
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log the action
    logSystemEvent(
        $_SESSION['user_id'],
        "sensor_{$action}",
        "Sensor {$action}: " . ($sensorId ? "ID={$sensorId}" : "new")
    );
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'sensor_id' => $sensorId
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    logError('Error in manage_sensor.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 