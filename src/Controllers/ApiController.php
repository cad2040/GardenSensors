<?php

namespace GardenSensors\Controllers;

use GardenSensors\Services\DatabaseService;
use GardenSensors\Services\CacheService;
use GardenSensors\Services\RateLimiterService;
use GardenSensors\Services\LoggingService;

class ApiController {
    protected DatabaseService $db;
    protected CacheService $cache;
    protected RateLimiterService $rateLimiter;
    protected LoggingService $logger;
    protected ?int $userId;

    public function __construct() {
        $this->db = new DatabaseService();
        $this->cache = new CacheService();
        $this->rateLimiter = new RateLimiterService();
        $this->logger = new LoggingService();
        $this->userId = $_SESSION['user_id'] ?? null;
    }

    protected function requireAuth(): void {
        if (!$this->userId) {
            $this->sendError('Unauthorized', 401);
        }
    }

    protected function checkRateLimit(string $endpoint): void {
        if (!$this->rateLimiter->check($this->userId, $endpoint)) {
            $this->sendError('Rate limit exceeded', 429, [
                'reset_time' => $this->rateLimiter->getResetTime($this->userId, $endpoint)
            ]);
        }
    }

    protected function getCachedData(string $key, int $ttl = CACHE_TTL): mixed {
        return $this->cache->get($key);
    }

    protected function setCachedData(string $key, mixed $data, int $ttl = CACHE_TTL): bool {
        return $this->cache->set($key, $data, $ttl);
    }

    protected function sendResponse(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }

    protected function sendError(string $message, int $status = 400, array $data = []): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'data' => $data
        ]);
        exit;
    }

    protected function validateInput(array $data, array $rules): bool {
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

    protected function logAction(string $action, array $details = []): void {
        $this->logger->info("User {$this->userId} performed {$action}", $details);
    }
} 