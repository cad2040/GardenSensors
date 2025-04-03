<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notification.php';

// Only allow execution from command line or cron
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

try {
    // Get all users with active alerts
    $sql = "SELECT u.id, u.email, us.settings 
            FROM users u 
            JOIN user_settings us ON u.id = us.user_id 
            WHERE us.settings->>'email_notifications' = 'true' 
            OR us.settings->>'low_battery_alert' = 'true' 
            OR us.settings->>'moisture_alert' = 'true' 
            OR us.settings->>'temperature_alert' = 'true'";
    
    $db = new Database();
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $settings = json_decode($user['settings'], true);
        
        // Skip if no alerts are enabled
        if (!$settings['email_notifications'] && 
            !$settings['low_battery_alert'] && 
            !$settings['moisture_alert'] && 
            !$settings['temperature_alert']) {
            continue;
        }

        // Initialize notification system for user
        $notification = new Notification($user['id']);
        
        // Check for alerts
        $notification->checkAlerts();
        
        // Get unread notifications
        $unreadCount = $notification->getUnreadCount();
        
        // If there are unread notifications and email notifications are enabled
        if ($unreadCount > 0 && $settings['email_notifications']) {
            // Get notifications
            $notifications = $notification->getNotifications(5);
            
            // Prepare email content
            $subject = "Garden Sensors Alert - {$unreadCount} New Notifications";
            $message = "You have {$unreadCount} new notifications:\n\n";
            
            foreach ($notifications as $notif) {
                $message .= "- {$notif['message']}\n";
                $message .= "  Time: " . date('Y-m-d H:i:s', strtotime($notif['created_at'])) . "\n\n";
            }
            
            $message .= "\nView all notifications at: " . APP_URL . "/notifications.php";
            
            // Send email
            $headers = "From: " . APP_NAME . " <noreply@" . parse_url(APP_URL, PHP_URL_HOST) . ">\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            mail($user['email'], $subject, $message, $headers);
            
            // Log email sent
            $logger = new Logger();
            $logger->info("Alert email sent", [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'notification_count' => $unreadCount
            ]);
        }
    }
    
    echo "Alert check completed successfully\n";
} catch (Exception $e) {
    $logger = new Logger();
    $logger->error("Alert check failed", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 