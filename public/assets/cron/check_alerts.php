<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cache.php';
require_once __DIR__ . '/../includes/logger.php';
require_once __DIR__ . '/../includes/notification.php';
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use GardenSensors\Services\MailService;

// Only allow execution from command line or cron
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line');
}

try {
    $sql = "SELECT u.id, u.email, us.settings
            FROM users u
            JOIN user_settings us ON u.id = us.user_id";

    $db = new Database();
    $db->getConnection();
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $mailer = new MailService();
    $logger = new Logger();

    foreach ($users as $user) {
        $settings = json_decode($user['settings'] ?? '{}', true) ?: [];

        if (empty($settings['email_notifications']) &&
            empty($settings['low_battery_alert']) &&
            empty($settings['moisture_alert']) &&
            empty($settings['temperature_alert']) &&
            empty($settings['ph_alert']) &&
            empty($settings['ec_alert']) &&
            empty($settings['nitrogen_alert'])) {
            continue;
        }

        $notification = new Notification($user['id']);
        $notification->checkAlerts();

        $unreadCount = $notification->getUnreadCount();
        if ($unreadCount > 0 && !empty($settings['email_notifications'])) {
            $notifications = $notification->getNotifications(5);
            $subject = "Garden Sensors Alert - {$unreadCount} New Notifications";
            $message = "You have {$unreadCount} new notifications:\n\n";
            foreach ($notifications as $notif) {
                $message .= "- {$notif['message']}\n";
                $message .= '  Time: ' . date('Y-m-d H:i:s', strtotime($notif['created_at'])) . "\n\n";
            }
            $message .= "\nView dashboard: " . (defined('APP_URL') ? APP_URL : 'http://localhost') . "/\n";

            $to = !empty($settings['notification_email']) ? $settings['notification_email'] : $user['email'];
            $result = $mailer->send($to, $subject, $message);
            $logger->info($result['ok'] ? 'Alert email sent' : 'Alert email failed', [
                'user_id' => $user['id'],
                'email' => $to,
                'method' => $result['method'],
                'error' => $result['error'],
                'notification_count' => $unreadCount,
            ]);
        }
    }

    echo "Alert check completed successfully\n";
} catch (Exception $e) {
    $logger = new Logger();
    $logger->error('Alert check failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    echo 'Error: ' . $e->getMessage() . "\n";
    exit(1);
}
