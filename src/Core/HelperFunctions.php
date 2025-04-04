<?php

namespace GardenSensors\Utils;

use DateTime;
use DateTimeZone;
use Exception;

class HelperFunctions {
    /**
     * Sanitize user input
     */
    public static function sanitizeInput(string $data): string {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken(string $token): bool {
        return !empty($_SESSION[CSRF_TOKEN_NAME]) && 
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Log errors
     */
    public static function logError(string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[$timestamp] ERROR: $message $contextStr\n";
        error_log($logMessage, 3, LOGS_PATH . '/error.log');
    }

    /**
     * Format moisture reading
     */
    public static function formatMoisture(float $value): string {
        return number_format($value, 1) . '%';
    }

    /**
     * Format temperature reading
     */
    public static function formatTemperature(float $value): string {
        return number_format($value, 1) . '°C';
    }

    /**
     * Format humidity reading
     */
    public static function formatHumidity(float $value): string {
        return number_format($value, 1) . '%';
    }

    /**
     * Format timestamp
     */
    public static function formatTimestamp(string $timestamp): string {
        return date('Y-m-d H:i:s', strtotime($timestamp));
    }

    /**
     * Display alert message
     */
    public static function displayAlert(string $message, string $type = 'info'): string {
        $class = match($type) {
            'success' => 'alert-success',
            'warning' => 'alert-warning',
            'error' => 'alert-danger',
            default => 'alert-info'
        };
        return "<div class='alert {$class}'>{$message}</div>";
    }

    /**
     * Validate sensor data
     */
    public static function validateSensorData(array $data): array {
        $errors = [];
        
        if (empty($data['sensor'])) {
            $errors[] = 'Sensor name is required';
        }
        
        if (isset($data['reading']) && !is_numeric($data['reading'])) {
            $errors[] = 'Reading must be a number';
        }
        
        if (isset($data['temperature']) && !is_numeric($data['temperature'])) {
            $errors[] = 'Temperature must be a number';
        }
        
        if (isset($data['humidity']) && !is_numeric($data['humidity'])) {
            $errors[] = 'Humidity must be a number';
        }
        
        return $errors;
    }

    /**
     * Validate plant data
     */
    public static function validatePlantData(array $data): array {
        $errors = [];
        
        if (empty($data['plant'])) {
            $errors[] = 'Plant name is required';
        }
        
        if (!isset($data['minSoilMoisture']) || !is_numeric($data['minSoilMoisture'])) {
            $errors[] = 'Minimum soil moisture must be a number';
        }
        
        if (!isset($data['maxSoilMoisture']) || !is_numeric($data['maxSoilMoisture'])) {
            $errors[] = 'Maximum soil moisture must be a number';
        }
        
        if ($data['minSoilMoisture'] >= $data['maxSoilMoisture']) {
            $errors[] = 'Minimum soil moisture must be less than maximum';
        }
        
        return $errors;
    }

    /**
     * Check if user has permission
     */
    public static function checkPermission(string $permission): bool {
        if (!isset($_SESSION['permissions']) || !in_array($permission, $_SESSION['permissions'])) {
            throw new Exception('Permission denied');
        }
        return true;
    }

    /**
     * Get user's timezone
     */
    public static function getUserTimezone(): string {
        return $_SESSION['timezone'] ?? 'UTC';
    }

    /**
     * Convert timestamp to user's timezone
     */
    public static function convertToUserTimezone(string $timestamp): string {
        $userTimezone = self::getUserTimezone();
        $date = new DateTime($timestamp, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone($userTimezone));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    /**
     * Check if user is admin
     */
    public static function isAdmin(): bool {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    /**
     * Send JSON response
     */
    public static function sendJsonResponse(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
} 