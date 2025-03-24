<?php
class ApiController {
    protected $db;
    protected $cache;
    protected $rateLimiter;
    protected $logger;
    protected $userId;

    public function __construct() {
        $this->db = new Database();
        $this->cache = new Cache();
        $this->rateLimiter = new RateLimiter();
        $this->logger = new Logger();
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    protected function requireAuth() {
        if (!$this->userId) {
            $this->sendError('Unauthorized', 401);
        }
    }

    protected function checkRateLimit($endpoint) {
        if (!$this->rateLimiter->check($this->userId, $endpoint)) {
            $this->sendError('Rate limit exceeded', 429, [
                'reset_time' => $this->rateLimiter->getResetTime($this->userId, $endpoint)
            ]);
        }
    }

    protected function getCachedData($key, $ttl = CACHE_TTL) {
        return $this->cache->get($key);
    }

    protected function setCachedData($key, $data, $ttl = CACHE_TTL) {
        return $this->cache->set($key, $data, $ttl);
    }

    protected function sendResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }

    protected function sendError($message, $status = 400, $data = []) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'data' => $data
        ]);
        exit;
    }

    protected function validateInput($data, $rules) {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field]) && strpos($rule, 'required') !== false) {
                $errors[$field] = 'This field is required';
                continue;
            }

            if (isset($data[$field])) {
                if (strpos($rule, 'numeric') !== false && !is_numeric($data[$field])) {
                    $errors[$field] = 'Must be a number';
                }
                if (strpos($rule, 'email') !== false && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = 'Must be a valid email';
                }
                if (strpos($rule, 'min:') !== false) {
                    $min = substr($rule, strpos($rule, 'min:') + 4);
                    if (strlen($data[$field]) < $min) {
                        $errors[$field] = "Must be at least {$min} characters";
                    }
                }
                if (strpos($rule, 'max:') !== false) {
                    $max = substr($rule, strpos($rule, 'max:') + 4);
                    if (strlen($data[$field]) > $max) {
                        $errors[$field] = "Must not exceed {$max} characters";
                    }
                }
            }
        }

        if (!empty($errors)) {
            $this->sendError('Validation failed', 422, ['errors' => $errors]);
        }

        return true;
    }

    protected function logAction($action, $details = []) {
        $this->logger->info("User {$this->userId} performed {$action}", $details);
    }
} 