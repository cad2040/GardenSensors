<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Please log in to access this page'], 401);
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = "
        SELECT 
            s.*,
            (
                SELECT p.name
                FROM plant_sensors ps
                JOIN plants p ON p.id = ps.plant_id
                WHERE ps.sensor_id = s.id
                ORDER BY ps.id DESC
                LIMIT 1
            ) as plant_name,
            r.value as last_reading,
            r.created_at as last_updated
        FROM sensors s
        LEFT JOIN readings r ON r.id = (
            SELECT r2.id FROM readings r2
            WHERE r2.sensor_id = s.id
            ORDER BY r2.created_at DESC
            LIMIT 1
        )
        ORDER BY s.name
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendJsonResponse(['success' => true, 'data' => $sensors]);
} catch (Exception $e) {
    logError('get_sensors failed: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Failed to fetch sensors'], 500);
}
