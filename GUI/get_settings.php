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
    
    // Get user settings
    $query = "
        SELECT 
            update_interval,
            alert_threshold,
            email_notifications
        FROM UserSettings
        WHERE user_id = :user_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no settings exist, create default settings
    if (!$settings) {
        $query = "
            INSERT INTO UserSettings (
                user_id,
                update_interval,
                alert_threshold,
                email_notifications
            ) VALUES (
                :user_id,
                15,
                20,
                1
            )
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        
        $settings = [
            'update_interval' => 15,
            'alert_threshold' => 20,
            'email_notifications' => 1
        ];
    }
    
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
    
} catch (Exception $e) {
    logError('Error fetching settings: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while fetching settings'
    ]);
} 