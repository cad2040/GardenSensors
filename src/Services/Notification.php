<?php
namespace GardenSensors\Services;

use GardenSensors\Core\Database;
use GardenSensors\Core\Cache;
use GardenSensors\Core\Logger;

class Notification {
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

    public function create(string $type, string $message, array $data = []): bool {
        $result = $this->db->execute(
            "INSERT INTO notifications (user_id, type, message, data, created_at) 
            VALUES (?, ?, ?, ?, NOW())",
            [
                $this->userId,
                $type,
                $message,
                json_encode($data)
            ]
        );

        if ($result) {
            $this->cache->clear("notifications:{$this->userId}");
            $this->logger->info('Notification created', ['user_id' => $this->userId, 'type' => $type]);
        }

        return $result;
    }

    public function markAsRead(int $notificationId): bool {
        $result = $this->db->execute(
            "UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?",
            [$notificationId, $this->userId]
        );

        if ($result) {
            $this->cache->clear("notifications:{$this->userId}");
            $this->logger->info('Notification marked as read', ['notification_id' => $notificationId, 'user_id' => $this->userId]);
        }

        return $result;
    }

    public function markAllAsRead(): bool {
        $result = $this->db->execute(
            "UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL",
            [$this->userId]
        );

        if ($result) {
            $this->cache->clear("notifications:{$this->userId}");
            $this->logger->info('All notifications marked as read', ['user_id' => $this->userId]);
        }

        return $result;
    }

    public function delete(int $notificationId): bool {
        $result = $this->db->execute(
            "DELETE FROM notifications WHERE id = ? AND user_id = ?",
            [$notificationId, $this->userId]
        );

        if ($result) {
            $this->cache->clear("notifications:{$this->userId}");
            $this->logger->info('Notification deleted', ['notification_id' => $notificationId, 'user_id' => $this->userId]);
        }

        return $result;
    }

    public function getUnreadCount(): int {
        $cacheKey = "notifications:unread:{$this->userId}";
        $count = $this->cache->get($cacheKey);

        if ($count === null) {
            $result = $this->db->query(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL",
                [$this->userId]
            );
            $count = isset($result[0]['count']) ? (int)$result[0]['count'] : 0;
            $this->cache->set($cacheKey, $count, 300);
        }

        return (int) $count;
    }

    public function getNotifications(int $page = 1, int $perPage = 10): array {
        $cacheKey = "notifications:list:{$this->userId}:{$page}:{$perPage}";
        $notifications = $this->cache->get($cacheKey);

        if ($notifications === null) {
            $offset = ($page - 1) * $perPage;
            $notifications = $this->db->query(
                "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?",
                [$this->userId, $perPage, $offset]
            );
            $this->cache->set($cacheKey, $notifications, 300);
        }

        return $notifications;
    }

    public function checkAlerts(array $settings = null): void {
        if ($settings === null) {
            $settings = [
                'low_battery_alert' => true,
                'moisture_alert' => true,
                'temperature_alert' => true,
                'ph_alert' => true,
                'ec_alert' => true,
            ];
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

    public function checkMoistureAlerts(): void {
        $rows = $this->db->query(
            "SELECT s.id, s.name, s.last_reading, p.name as plant_name, p.min_soil_moisture, p.max_soil_moisture
             FROM sensors s
             JOIN plant_sensors ps ON s.id = ps.sensor_id
             JOIN plants p ON ps.plant_id = p.id
             WHERE s.user_id = ? AND s.type = 'moisture' AND s.last_reading IS NOT NULL
             AND (s.last_reading < p.min_soil_moisture OR s.last_reading > p.max_soil_moisture)
             AND s.id NOT IN (
                SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.sensor_id')) AS UNSIGNED)
                FROM notifications
                WHERE user_id = ? AND type = 'moisture'
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             )",
            [$this->userId, $this->userId]
        );

        foreach ($rows as $sensor) {
            $status = $sensor['last_reading'] < $sensor['min_soil_moisture'] ? 'low' : 'high';
            $hint = $status === 'low' ? ' — plant may need water' : ' — soil may be too wet';
            $this->create(
                'moisture',
                "Moisture {$status} for sensor {$sensor['name']} on plant {$sensor['plant_name']}{$hint}",
                [
                    'sensor_id' => (int)$sensor['id'],
                    'reading' => $sensor['last_reading'],
                    'min' => $sensor['min_soil_moisture'],
                    'max' => $sensor['max_soil_moisture'],
                    'status' => $status,
                ]
            );
        }
    }

    public function checkTemperatureAlerts(): void {
        $rows = $this->db->query(
            "SELECT s.id, s.name, s.last_reading, p.name as plant_name, p.min_temperature, p.max_temperature
             FROM sensors s
             JOIN plant_sensors ps ON s.id = ps.sensor_id
             JOIN plants p ON ps.plant_id = p.id
             WHERE s.user_id = ? AND s.type = 'temperature' AND s.last_reading IS NOT NULL
             AND (
                (p.min_temperature IS NOT NULL AND s.last_reading < p.min_temperature)
                OR (p.max_temperature IS NOT NULL AND s.last_reading > p.max_temperature)
                OR (p.min_temperature IS NULL AND p.max_temperature IS NULL AND (s.last_reading < 10 OR s.last_reading > 35))
             )
             AND s.id NOT IN (
                SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.sensor_id')) AS UNSIGNED)
                FROM notifications
                WHERE user_id = ? AND type = 'temperature'
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             )",
            [$this->userId, $this->userId]
        );

        foreach ($rows as $sensor) {
            $min = $sensor['min_temperature'] ?? 10;
            $status = $sensor['last_reading'] < $min ? 'low' : 'high';
            $hint = $status === 'low' ? ' — plant may need warmth' : ' — soil may be too hot';
            $this->create(
                'temperature',
                "Temperature {$status} for sensor {$sensor['name']} on plant {$sensor['plant_name']}{$hint}",
                [
                    'sensor_id' => (int)$sensor['id'],
                    'reading' => $sensor['last_reading'],
                    'status' => $status,
                ]
            );
        }
    }

    public function checkPhAlerts(): void {
        $rows = $this->db->query(
            "SELECT s.id, s.name, s.last_reading, p.name as plant_name, p.min_ph, p.max_ph
             FROM sensors s
             JOIN plant_sensors ps ON s.id = ps.sensor_id
             JOIN plants p ON ps.plant_id = p.id
             WHERE s.user_id = ? AND s.type = 'ph' AND s.last_reading IS NOT NULL
             AND p.min_ph IS NOT NULL AND p.max_ph IS NOT NULL
             AND (s.last_reading < p.min_ph OR s.last_reading > p.max_ph)
             AND s.id NOT IN (
                SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.sensor_id')) AS UNSIGNED)
                FROM notifications
                WHERE user_id = ? AND type = 'ph'
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             )",
            [$this->userId, $this->userId]
        );

        foreach ($rows as $sensor) {
            $high = $sensor['last_reading'] > $sensor['max_ph'];
            $message = $high
                ? "Soil pH high for sensor {$sensor['name']} on plant {$sensor['plant_name']} — consider an amendment to lower pH"
                : "Soil pH low for sensor {$sensor['name']} on plant {$sensor['plant_name']} — consider an amendment to raise pH";
            $this->create('ph', $message, [
                'sensor_id' => (int)$sensor['id'],
                'reading' => $sensor['last_reading'],
                'min' => $sensor['min_ph'],
                'max' => $sensor['max_ph'],
                'status' => $high ? 'high' : 'low'
            ]);
        }
    }

    public function checkEcAlerts(): void {
        $settingsRow = $this->db->query(
            'SELECT settings FROM user_settings WHERE user_id = ?',
            [$this->userId]
        );
        $baseline = 0.3;
        if (!empty($settingsRow[0]['settings'])) {
            $decoded = json_decode($settingsRow[0]['settings'], true) ?: [];
            if (isset($decoded['baseline_water_ec'])) {
                $baseline = (float)$decoded['baseline_water_ec'];
            }
        }

        $ecService = new EcNutrientService($this->db);
        $rows = $this->db->query(
            "SELECT s.id, s.name, s.last_reading, p.name as plant_name,
                    p.ec_underfed, p.ec_balanced_min, p.ec_balanced_max, p.ec_overfed
             FROM sensors s
             JOIN plant_sensors ps ON s.id = ps.sensor_id
             JOIN plants p ON ps.plant_id = p.id
             WHERE s.user_id = ? AND s.type = 'ec' AND s.last_reading IS NOT NULL
             AND s.id NOT IN (
                SELECT CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.sensor_id')) AS UNSIGNED)
                FROM notifications
                WHERE user_id = ? AND type = 'ec'
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             )",
            [$this->userId, $this->userId]
        );

        foreach ($rows as $sensor) {
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
                'sensor_id' => (int)$sensor['id'],
                'soil_ec' => $eval['soil_ec'],
                'available_ec' => $eval['available_ec'],
                'state' => $eval['state'],
                'slope' => $eval['slope'],
            ]);
        }
    }
} 