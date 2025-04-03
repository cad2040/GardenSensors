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

// Validate input data
$updateInterval = isset($_POST['update_interval']) ? (int)$_POST['update_interval'] : null;
$alertThreshold = isset($_POST['alert_threshold']) ? (int)$_POST['alert_threshold'] : null;
$emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;

// Validate values
if ($updateInterval === null || $updateInterval < 1 || $updateInterval > 60) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid update interval']);
    exit;
}

if ($alertThreshold === null || $alertThreshold < 0 || $alertThreshold > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid alert threshold']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Begin transaction
    $conn->beginTransaction();
    
    // Check if settings exist
    $query = "SELECT id FROM usersettings WHERE user_id = :user_id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($settings) {
        // Update existing settings
        $query = "
            UPDATE usersettings
            SET 
                update_interval = :update_interval,
                alert_threshold = :alert_threshold,
                email_notifications = :email_notifications,
                updated_at = NOW()
            WHERE user_id = :user_id
        ";
    } else {
        // Insert new settings
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
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':update_interval' => $updateInterval,
        ':alert_threshold' => $alertThreshold,
        ':email_notifications' => $emailNotifications
    ]);
    
    // Commit transaction
    $conn->commit();
    
    // Log the update
    logSystemEvent(
        $_SESSION['user_id'],
        'settings_update',
        "Updated user settings: interval={$updateInterval}, threshold={$alertThreshold}, notifications={$emailNotifications}"
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Settings updated successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    logError('Error updating settings: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating settings'
    ]);
} 