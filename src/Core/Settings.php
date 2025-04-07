<?php
namespace GardenSensors\Core;

class Settings {
    private $db;
    private $cache;
    private $logger;
    private $userId;

    public function __construct(Database $db, Cache $cache, Logger $logger, int $userId) {
        $this->db = $db;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->userId = $userId;
    }

    public function update(array $settings): bool {
        // Validate settings
        if (isset($settings['update_interval']) && ($settings['update_interval'] < 60 || $settings['update_interval'] > 3600)) {
            throw new \InvalidArgumentException('Update interval must be between 60 and 3600 seconds');
        }

        if (isset($settings['timezone']) && !in_array($settings['timezone'], \DateTimeZone::listIdentifiers())) {
            throw new \InvalidArgumentException('Invalid timezone');
        }

        // Check if settings exist
        $result = $this->db->query("SELECT id FROM user_settings WHERE user_id = ?", [$this->userId]);
        if (empty($result)) {
            // Create new settings
            $sql = "INSERT INTO user_settings (
                user_id, email_notifications, low_battery_alerts, moisture_alerts,
                temperature_alerts, update_interval, theme, language, timezone, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $params = [
                $this->userId,
                $settings['email_notifications'] ?? true,
                $settings['low_battery_alerts'] ?? true,
                $settings['moisture_alerts'] ?? true,
                $settings['temperature_alerts'] ?? true,
                $settings['update_interval'] ?? 300,
                $settings['theme'] ?? 'light',
                $settings['language'] ?? 'en',
                $settings['timezone'] ?? 'UTC'
            ];
            
            $this->db->execute($sql, $params);
        } else {
            // Update existing settings
            $sql = "UPDATE user_settings SET 
                email_notifications = ?,
                low_battery_alerts = ?,
                moisture_alerts = ?,
                temperature_alerts = ?,
                update_interval = ?,
                theme = ?,
                language = ?,
                timezone = ?,
                updated_at = NOW()
                WHERE user_id = ?";
            
            $params = [
                $settings['email_notifications'] ?? true,
                $settings['low_battery_alerts'] ?? true,
                $settings['moisture_alerts'] ?? true,
                $settings['temperature_alerts'] ?? true,
                $settings['update_interval'] ?? 300,
                $settings['theme'] ?? 'light',
                $settings['language'] ?? 'en',
                $settings['timezone'] ?? 'UTC',
                $this->userId
            ];
            
            $this->db->execute($sql, $params);
        }

        // Clear cache
        $this->cache->clear("settings:{$this->userId}");
        
        // Log update
        $this->logger->info('Settings updated', ['user_id' => $this->userId]);
        
        return true;
    }

    public function get(): array {
        // Try to get from cache first
        $settings = $this->cache->get("settings:{$this->userId}");
        if ($settings !== null) {
            return $settings;
        }

        // Get from database
        $settings = $this->db->query("SELECT * FROM user_settings WHERE user_id = ?", [$this->userId]);
        if (empty($settings)) {
            // Return default settings
            $settings = [
                'user_id' => $this->userId,
                'email_notifications' => true,
                'low_battery_alerts' => true,
                'moisture_alerts' => true,
                'temperature_alerts' => true,
                'update_interval' => 300,
                'theme' => 'light',
                'language' => 'en',
                'timezone' => 'UTC'
            ];
        } else {
            $settings = $settings[0];
        }

        // Cache settings
        $this->cache->set("settings:{$this->userId}", $settings, 3600);

        return $settings;
    }

    public function reset(): bool {
        $defaultSettings = [
            'email_notifications' => true,
            'low_battery_alerts' => true,
            'moisture_alerts' => true,
            'temperature_alerts' => true,
            'update_interval' => 300,
            'theme' => 'light',
            'language' => 'en',
            'timezone' => 'UTC'
        ];

        return $this->update($defaultSettings);
    }
} 