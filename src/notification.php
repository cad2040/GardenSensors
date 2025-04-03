<?php
class Notification {
    private $db;
    private $cache;
    private $logger;
    private $userId;

    public function __construct($userId) {
        $this->db = new Database();
        $this->cache = new Cache();
        $this->logger = new Logger();
        $this->userId = $userId;
    }

    public function create($type, $message, $data = []) {
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

        return $this->db->lastInsertId();
    }

    public function markAsRead($notificationId) {
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

    public function markAllAsRead() {
        $sql = "UPDATE notifications 
                SET read_at = NOW() 
                WHERE user_id = ? AND read_at IS NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);

        // Clear cache
        $this->cache->delete("notifications:{$this->userId}");
    }

    public function delete($notificationId) {
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

    public function getUnreadCount() {
        $cacheKey = "notifications:{$this->userId}:unread_count";
        $count = $this->cache->get($cacheKey);

        if ($count === false) {
            $sql = "SELECT COUNT(*) as count 
                    FROM notifications 
                    WHERE user_id = ? AND read_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $count = $result['count'];
            $this->cache->set($cacheKey, $count, 300); // Cache for 5 minutes
        }

        return $count;
    }

    public function getNotifications($limit = 10, $offset = 0) {
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

    public function checkAlerts() {
        // Get user settings
        $settings = $this->getUserSettings();
        if (!$settings) {
            return;
        }

        // Check low battery alerts
        if ($settings['low_battery_alert']) {
            $this->checkLowBatteryAlerts();
        }

        // Check moisture alerts
        if ($settings['moisture_alert']) {
            $this->checkMoistureAlerts();
        }

        // Check temperature alerts
        if ($settings['temperature_alert']) {
            $this->checkTemperatureAlerts();
        }
    }

    private function getUserSettings() {
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

    private function checkLowBatteryAlerts() {
        $sql = "SELECT s.*, p.name as plant_name 
                FROM sensors s 
                JOIN plants p ON s.plant_id = p.id 
                WHERE s.user_id = ? AND s.battery_level < 20 
                AND s.id NOT IN (
                    SELECT data->>'sensor_id' 
                    FROM notifications 
                    WHERE user_id = ? AND type = 'low_battery' 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                )";
        
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

    private function checkMoistureAlerts() {
        $sql = "SELECT s.*, p.name as plant_name, p.min_moisture, p.max_moisture 
                FROM sensors s 
                JOIN plants p ON s.plant_id = p.id 
                WHERE s.user_id = ? AND s.last_reading IS NOT NULL 
                AND (s.last_reading < p.min_moisture OR s.last_reading > p.max_moisture)
                AND s.id NOT IN (
                    SELECT data->>'sensor_id' 
                    FROM notifications 
                    WHERE user_id = ? AND type = 'moisture' 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId, $this->userId]);
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sensors as $sensor) {
            $status = $sensor['last_reading'] < $sensor['min_moisture'] ? 'low' : 'high';
            $this->create(
                'moisture',
                "Moisture {$status} alert for sensor {$sensor['name']} on plant {$sensor['plant_name']}",
                [
                    'sensor_id' => $sensor['id'],
                    'reading' => $sensor['last_reading'],
                    'min' => $sensor['min_moisture'],
                    'max' => $sensor['max_moisture']
                ]
            );
        }
    }

    private function checkTemperatureAlerts() {
        $sql = "SELECT s.*, p.name as plant_name 
                FROM sensors s 
                JOIN plants p ON s.plant_id = p.id 
                WHERE s.user_id = ? AND s.last_reading IS NOT NULL 
                AND (s.last_reading < 10 OR s.last_reading > 35)
                AND s.id NOT IN (
                    SELECT data->>'sensor_id' 
                    FROM notifications 
                    WHERE user_id = ? AND type = 'temperature' 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId, $this->userId]);
        $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sensors as $sensor) {
            $status = $sensor['last_reading'] < 10 ? 'low' : 'high';
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
} 