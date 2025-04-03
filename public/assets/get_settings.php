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
        FROM usersettings
        WHERE user_id = :user_id
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no settings exist, create default settings
    if (!$settings) {
        $query = "
            INSERT INTO usersettings (
                user_id,
                update_interval,
                alert_threshold,
                email_notifications
            ) VALUES (
                :user_id,
                :update_interval,
                :alert_threshold,
                :email_notifications
            )
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':update_interval' => DEFAULT_UPDATE_INTERVAL,
            ':alert_threshold' => DEFAULT_ALERT_THRESHOLD,
            ':email_notifications' => true
        ]);
        
        $settings = [
            'update_interval' => DEFAULT_UPDATE_INTERVAL,
            'alert_threshold' => DEFAULT_ALERT_THRESHOLD,
            'email_notifications' => true
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