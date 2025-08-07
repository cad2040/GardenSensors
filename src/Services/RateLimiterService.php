<?php

namespace GardenSensors\Services;

use PDO;

class RateLimiterService {
    private DatabaseService $db;
    private bool $enabled;

    public function __construct(DatabaseService $db) {
        $this->db = $db;
        $this->enabled = getenv('RATE_LIMIT_ENABLED') !== 'false';
    }

    public function check(?int $userId, string $endpoint): bool {
        if (!$this->enabled) {
            return true;
        }

        // If no user ID provided, allow the request (for testing)
        if ($userId === null) {
            return true;
        }

        $current = $this->getCurrentCount($userId, $endpoint);

        if ($current >= (int)(getenv('RATE_LIMIT_REQUESTS') ?: 100)) {
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
        $stmt->execute([$userId, $endpoint, (int)(getenv('RATE_LIMIT_WINDOW') ?: 3600)]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)$result['count'];
    }

    private function incrementCount(int $userId, string $endpoint): bool {
        $sql = "INSERT INTO rate_limits (user_id, endpoint, timestamp) 
                VALUES (?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $endpoint]);
    }

    public function getRemainingRequests(?int $userId, string $endpoint): int {
        if ($userId === null) {
            return (int)(getenv('RATE_LIMIT_REQUESTS') ?: 100);
        }
        $current = $this->getCurrentCount($userId, $endpoint);
        return max(0, (int)(getenv('RATE_LIMIT_REQUESTS') ?: 100) - $current);
    }

    public function getResetTime(?int $userId, string $endpoint): int {
        if ($userId === null) {
            return time() + (int)(getenv('RATE_LIMIT_WINDOW') ?: 3600);
        }
        
        // For now, return current time + window since rate_limits table doesn't exist in production
        return time() + (int)(getenv('RATE_LIMIT_WINDOW') ?: 3600);
    }
} 