<?php
class RateLimiter {
    private $db;
    private $enabled;

    public function __construct() {
        $this->db = new Database();
        $this->enabled = RATE_LIMIT_ENABLED;
    }

    public function check($userId, $endpoint) {
        if (!$this->enabled) {
            return true;
        }

        $key = "rate_limit:{$userId}:{$endpoint}";
        $current = $this->getCurrentCount($key);

        if ($current >= RATE_LIMIT_REQUESTS) {
            return false;
        }

        $this->incrementCount($key);
        return true;
    }

    private function getCurrentCount($key) {
        $sql = "SELECT COUNT(*) as count FROM rate_limits 
                WHERE user_id = ? AND endpoint = ? 
                AND timestamp > DATE_SUB(NOW(), INTERVAL ? SECOND)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $endpoint, RATE_LIMIT_WINDOW]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'];
    }

    private function incrementCount($key) {
        $sql = "INSERT INTO rate_limits (user_id, endpoint, timestamp) 
                VALUES (?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $endpoint]);
    }

    public function getRemainingRequests($userId, $endpoint) {
        $current = $this->getCurrentCount($key);
        return max(0, RATE_LIMIT_REQUESTS - $current);
    }

    public function getResetTime($userId, $endpoint) {
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