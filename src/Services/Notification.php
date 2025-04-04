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
        $result = $this->db->query(
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
        $result = $this->db->query(
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
        $result = $this->db->query(
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
        $result = $this->db->query(
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
            $count = $result[0]['count'];
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
} 