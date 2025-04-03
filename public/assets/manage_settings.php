<?php
require_once 'includes/config.php';
require_once 'includes/api_controller.php';

class SettingsController extends ApiController {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->checkRateLimit('manage_settings');
    }

    public function handleRequest() {
        $action = $_POST['action'] ?? '';

        try {
            $this->db->beginTransaction();

            switch ($action) {
                case 'update':
                    $this->updateSettings();
                    break;
                case 'reset':
                    $this->resetSettings();
                    break;
                default:
                    $this->sendError('Invalid action');
            }

            $this->db->commit();
            $this->logAction($action);
            $this->sendResponse(['message' => ucfirst($action) . ' successful']);
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Settings operation failed', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            $this->sendError('Operation failed: ' . $e->getMessage());
        }
    }

    private function updateSettings() {
        $this->validateInput($_POST, [
            'email_notifications' => 'boolean',
            'low_battery_alert' => 'boolean',
            'moisture_alert' => 'boolean',
            'temperature_alert' => 'boolean',
            'update_interval' => 'numeric',
            'theme' => 'max:20',
            'language' => 'max:10',
            'timezone' => 'max:50'
        ]);

        $settings = [
            'email_notifications' => $_POST['email_notifications'] ?? false,
            'low_battery_alert' => $_POST['low_battery_alert'] ?? false,
            'moisture_alert' => $_POST['moisture_alert'] ?? false,
            'temperature_alert' => $_POST['temperature_alert'] ?? false,
            'update_interval' => $_POST['update_interval'] ?? 300,
            'theme' => $_POST['theme'] ?? 'light',
            'language' => $_POST['language'] ?? 'en',
            'timezone' => $_POST['timezone'] ?? 'UTC'
        ];

        // Validate update interval
        if ($settings['update_interval'] < 60 || $settings['update_interval'] > 3600) {
            $this->sendError('Update interval must be between 60 and 3600 seconds');
        }

        // Validate timezone
        if (!in_array($settings['timezone'], DateTimeZone::listIdentifiers())) {
            $this->sendError('Invalid timezone');
        }

        // Check if settings exist
        $sql = "SELECT id FROM user_settings WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        
        if ($stmt->rowCount() === 0) {
            // Insert new settings
            $sql = "INSERT INTO user_settings (user_id, settings, created_at, updated_at) 
                    VALUES (?, ?, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->userId, json_encode($settings)]);
        } else {
            // Update existing settings
            $sql = "UPDATE user_settings 
                    SET settings = ?, updated_at = NOW() 
                    WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([json_encode($settings), $this->userId]);
        }

        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to update settings');
        }

        // Clear cache
        $this->cache->delete("settings:{$this->userId}");
    }

    private function resetSettings() {
        $defaultSettings = [
            'email_notifications' => true,
            'low_battery_alert' => true,
            'moisture_alert' => true,
            'temperature_alert' => true,
            'update_interval' => 300,
            'theme' => 'light',
            'language' => 'en',
            'timezone' => 'UTC'
        ];

        $sql = "UPDATE user_settings 
                SET settings = ?, updated_at = NOW() 
                WHERE user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([json_encode($defaultSettings), $this->userId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to reset settings');
        }

        // Clear cache
        $this->cache->delete("settings:{$this->userId}");
    }
}

// Handle the request
$controller = new SettingsController();
$controller->handleRequest(); 