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

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all plants with their associated sensors
    $query = "
        SELECT 
            p.*,
            GROUP_CONCAT(
                JSON_OBJECT(
                    'id', s.id,
                    'name', s.name,
                    'type', s.type
                )
            ) as sensors
        FROM Plants p
        LEFT JOIN Sensors s ON p.id = s.plant_id
        WHERE p.user_id = :user_id
        GROUP BY p.id
        ORDER BY p.name ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    foreach ($plants as &$plant) {
        // Convert sensors JSON string to array
        $plant['sensors'] = $plant['sensors'] ? json_decode('[' . $plant['sensors'] . ']', true) : [];
        
        // Format moisture values
        $plant['min_moisture'] = formatMoisture($plant['min_moisture']);
        $plant['max_moisture'] = formatMoisture($plant['max_moisture']);
    }
    
    echo json_encode([
        'success' => true,
        'plants' => $plants
    ]);
    
} catch (Exception $e) {
    logError('Error fetching plants: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching plants'
    ]);
} 