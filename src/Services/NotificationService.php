<?php

namespace GardenSensors\Services;

use PDO;
use Exception;

class NotificationService {
    private DatabaseService $db;
    private CacheService $cache;
    private LoggingService $logger;
    private int $userId;

    public function __construct(DatabaseService $db, CacheService $cache, LoggingService $logger, int $userId) {
        $this->db = $db;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->userId = $userId;
    }

    public function create(string $type, string $message, array $data = []): int {
        $sql = "INSERT INTO notifications (user_id, type, message, data, created_at, read_at) 
                VALUES (?, ?, ?, ?, NOW(), NULL)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->userId,
            $type,
            $message,
            json_encode($data)
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to create notification');
        }

        // Clear cache
        $this->cache->delete("notifications:{$this->userId}");
        
        // Log notification
        $this->logger->info("Notification created", [
            'user_id' => $this->userId,
            'type' => $type,
            'message' => $message
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function markAsRead(int $notificationId): void {
        $sql = "UPDATE notifications 
                SET read_at = NOW() 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$notificationId, $this->userId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Notification not found or unauthorized');
        }

        // Clear cache
        $this->cache->delete("notifications:{$this->userId}");
    }

    public function markAllAsRead(): void {
        $sql = "UPDATE notifications 
                SET read_at = NOW() 
                WHERE user_id = ? AND read_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);

        // Clear cache
        $this->cache->delete("notifications:{$this->userId}");
    }

    public function delete(int $notificationId): void {
        $sql = "DELETE FROM notifications 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$notificationId, $this->userId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Notification not found or unauthorized');
        }

        // Clear cache
        $this->cache->delete("notifications:{$this->userId}");
    }

    public function getUnreadCount(): int {
        $cacheKey = "notifications:{$this->userId}:unread_count";
        $count = $this->cache->get($cacheKey);

        if ($count === false) {
            $sql = "SELECT COUNT(*) as count 
                    FROM notifications 
                    WHERE user_id = ? AND read_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $count = (int)$result['count'];
            $this->cache->set($cacheKey, $count, 300); // Cache for 5 minutes
        }

        return $count;
    }

    public function getNotifications(int $limit = 10, int $offset = 0): array {
        $cacheKey = "notifications:{$this->userId}:{$limit}:{$offset}";
        $notifications = $this->cache->get($cacheKey);

        if ($notifications === false) {
            $sql = "SELECT * FROM notifications 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->userId, $limit, $offset]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->cache->set($cacheKey, $notifications, 300); // Cache for 5 minutes
        }

        return $notifications;
    }

    public function checkAlerts(): void {
        // Get user settings
        $settings = $this->getUserSettings();
        if (!$settings) {
            return;
        }

        if (!empty($settings['low_battery_alert'])) {
            $this->checkLowBatteryAlerts();
        }

        if (!empty($settings['moisture_alert'])) {
            $this->checkMoistureAlerts();
        }

        if (!empty($settings['temperature_alert'])) {
            $this->checkTemperatureAlerts();
        }

        if (!empty($settings['ph_alert'])) {
            $this->checkPhAlerts();
        }

        if (!empty($settings['ec_alert']) || !empty($settings['nitrogen_alert'])) {
            $this->checkEcAlerts();
        }
    }

    private function getUserSettings(): ?array {
        $cacheKey = "settings:{$this->userId}";
        $settings = $this->cache->get($cacheKey);

        if ($settings === false) {
            $sql = "SELECT settings FROM user_settings WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $settings = json_decode($result['settings'], true);
                $this->cache->set($cacheKey, $settings, 300); // Cache for 5 minutes
            }
        }

        return $settings;
    }

    private function recentAlertSensorIdsSql(string $type, string $intervalSql): string {
        return "SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.sensor_id')) AS UNSIGNED)
                FROM notifications
                WHERE user_id = ? AND type = '{$type}'
                AND created_at > DATE_SUB(NOW(), INTERVAL {$intervalSql})";
    }

    private function checkLowBatteryAlerts(): void {
        $sql = "SELECT s.*, p.name as plant_name
                FROM sensors s
                JOIN plant_sensors ps ON s.id = ps.sensor_id
                JOIN plants p ON ps.plant_id = p.id
                WHERE s.user_id = ? AND s.battery_level < 20
                AND s.id NOT IN (" . $this->recentAlertSensorIdsSql('low_battery', '24 HOUR') . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId, $this->userId]);
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sensors as $sensor) {
            $this->create(
                'low_battery',
                "Low battery alert for sensor {$sensor['name']} on plant {$sensor['plant_name']}",
                ['sensor_id' => $sensor['id'], 'battery_level' => $sensor['battery_level']]
            );
        }
    }

    private function checkMoistureAlerts(): void {
        $sql = "SELECT s.*, p.name as plant_name, p.min_soil_moisture, p.max_soil_moisture
                FROM sensors s
                JOIN plant_sensors ps ON s.id = ps.sensor_id
                JOIN plants p ON ps.plant_id = p.id
                WHERE s.user_id = ? AND s.type = 'moisture' AND s.last_reading IS NOT NULL
                AND (s.last_reading < p.min_soil_moisture OR s.last_reading > p.max_soil_moisture)
                AND s.id NOT IN (" . $this->recentAlertSensorIdsSql('moisture', '1 HOUR') . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId, $this->userId]);
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sensors as $sensor) {
            $status = $sensor['last_reading'] < $sensor['min_soil_moisture'] ? 'low' : 'high';
            $this->create(
                'moisture',
                "Moisture {$status} alert for sensor {$sensor['name']} on plant {$sensor['plant_name']}",
                [
                    'sensor_id' => $sensor['id'],
                    'reading' => $sensor['last_reading'],
                    'min' => $sensor['min_soil_moisture'],
                    'max' => $sensor['max_soil_moisture']
                ]
            );
        }
    }

    private function checkTemperatureAlerts(): void {
        $sql = "SELECT s.*, p.name as plant_name, p.min_temperature, p.max_temperature
                FROM sensors s
                JOIN plant_sensors ps ON s.id = ps.sensor_id
                JOIN plants p ON ps.plant_id = p.id
                WHERE s.user_id = ? AND s.type = 'temperature' AND s.last_reading IS NOT NULL
                AND (
                    (p.min_temperature IS NOT NULL AND s.last_reading < p.min_temperature)
                    OR (p.max_temperature IS NOT NULL AND s.last_reading > p.max_temperature)
                    OR (p.min_temperature IS NULL AND p.max_temperature IS NULL AND (s.last_reading < 10 OR s.last_reading > 35))
                )
                AND s.id NOT IN (" . $this->recentAlertSensorIdsSql('temperature', '1 HOUR') . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId, $this->userId]);
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sensors as $sensor) {
            $min = $sensor['min_temperature'] ?? 10;
            $status = $sensor['last_reading'] < $min ? 'low' : 'high';
            $this->create(
                'temperature',
                "Temperature {$status} alert for sensor {$sensor['name']} on plant {$sensor['plant_name']}",
                [
                    'sensor_id' => $sensor['id'],
                    'reading' => $sensor['last_reading']
                ]
            );
        }
    }

    private function checkPhAlerts(): void {
        $sql = "SELECT s.*, p.name as plant_name, p.min_ph, p.max_ph
                FROM sensors s
                JOIN plant_sensors ps ON s.id = ps.sensor_id
                JOIN plants p ON ps.plant_id = p.id
                WHERE s.user_id = ? AND s.type = 'ph' AND s.last_reading IS NOT NULL
                AND p.min_ph IS NOT NULL AND p.max_ph IS NOT NULL
                AND (s.last_reading < p.min_ph OR s.last_reading > p.max_ph)
                AND s.id NOT IN (" . $this->recentAlertSensorIdsSql('ph', '1 HOUR') . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId, $this->userId]);
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sensors as $sensor) {
            if ($sensor['last_reading'] > $sensor['max_ph']) {
                $message = "Soil pH high for sensor {$sensor['name']} on plant {$sensor['plant_name']} — consider an amendment to lower pH";
                $status = 'high';
            } else {
                $message = "Soil pH low for sensor {$sensor['name']} on plant {$sensor['plant_name']} — consider an amendment to raise pH";
                $status = 'low';
            }
            $this->create(
                'ph',
                $message,
                [
                    'sensor_id' => $sensor['id'],
                    'reading' => $sensor['last_reading'],
                    'min' => $sensor['min_ph'],
                    'max' => $sensor['max_ph'],
                    'status' => $status
                ]
            );
        }
    }

    private function checkEcAlerts(): void {
        $settings = $this->getUserSettings() ?: [];
        $baseline = isset($settings['baseline_water_ec']) ? (float)$settings['baseline_water_ec'] : 0.3;
        $ecService = new EcNutrientService();

        $sql = "SELECT s.*, p.name as plant_name, p.ec_underfed, p.ec_balanced_min, p.ec_balanced_max, p.ec_overfed
                FROM sensors s
                JOIN plant_sensors ps ON s.id = ps.sensor_id
                JOIN plants p ON ps.plant_id = p.id
                WHERE s.user_id = ? AND s.type = 'ec' AND s.last_reading IS NOT NULL
                AND s.id NOT IN (" . $this->recentAlertSensorIdsSql('ec', '1 HOUR') . ")";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId, $this->userId]);
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sensors as $sensor) {
            $zones = [
                'ec_underfed' => (float)$sensor['ec_underfed'],
                'ec_balanced_min' => (float)$sensor['ec_balanced_min'],
                'ec_balanced_max' => (float)$sensor['ec_balanced_max'],
                'ec_overfed' => (float)$sensor['ec_overfed'],
            ];
            $eval = $ecService->evaluateSensor(
                (int)$sensor['id'],
                (float)$sensor['last_reading'],
                $baseline,
                $zones
            );
            if ($eval['state'] === EcNutrientService::STATE_BALANCED) {
                continue;
            }
            $message = $ecService->alertMessage(
                $eval['state'],
                $sensor['plant_name'],
                !empty($eval['trend_driven'])
            );
            $this->create('ec', $message, [
                'sensor_id' => $sensor['id'],
                'soil_ec' => $eval['soil_ec'],
                'available_ec' => $eval['available_ec'],
                'state' => $eval['state'],
                'slope' => $eval['slope'],
            ]);
        }
    }
} 