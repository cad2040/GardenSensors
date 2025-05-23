<?php

namespace GardenSensors\Services;

use PDO;

class RateLimiterService {
    private DatabaseService $db;
    private bool $enabled;

    public function __construct(DatabaseService $db) {
        $this->db = $db;
        $this->enabled = RATE_LIMIT_ENABLED;
    }

    public function check(int $userId, string $endpoint): bool {
        if (!$this->enabled) {
            return true;
        }

        $current = $this->getCurrentCount($userId, $endpoint);

        if ($current >= RATE_LIMIT_REQUESTS) {
            return false;
        }

        $this->incrementCount($userId, $endpoint);
        return true;
    }

    private function getCurrentCount(int $userId, string $endpoint): int {
        $sql = "SELECT COUNT(*) as count FROM rate_limits 
                WHERE user_id = ? AND endpoint = ? 
                AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $endpoint, RATE_LIMIT_WINDOW]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['count'];
    }

    private function incrementCount(int $userId, string $endpoint): bool {
        $sql = "INSERT INTO rate_limits (user_id, endpoint, timestamp) 
                VALUES (?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $endpoint]);
    }

    public function getRemainingRequests(int $userId, string $endpoint): int {
        $current = $this->getCurrentCount($userId, $endpoint);
        return max(0, RATE_LIMIT_REQUESTS - $current);
    }

    public function getResetTime(int $userId, string $endpoint): int {
        $sql = "SELECT MAX(timestamp) as last_request 
                FROM rate_limits 
                WHERE user_id = ? AND endpoint = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $endpoint]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['last_request']) {
            return strtotime($result['last_request']) + RATE_LIMIT_WINDOW;
        }
        
        return time();
    }
} 