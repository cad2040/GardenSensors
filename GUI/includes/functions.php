<?php
/**
 * Utility functions for the Garden Sensors Dashboard
 */

/**
 * Sanitize user input
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME]) || $token !== $_SESSION[CSRF_TOKEN_NAME]) {
        throw new Exception('Invalid CSRF token');
    }
    return true;
}

/**
 * Log errors
 */
function logError($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
}

/**
 * Format moisture reading
 */
function formatMoisture($value) {
    return number_format($value, 1) . '%';
}

/**
 * Format temperature reading
 */
function formatTemperature($value) {
    return number_format($value, 1) . '°C';
}

/**
 * Format humidity reading
 */
function formatHumidity($value) {
    return number_format($value, 1) . '%';
}

/**
 * Format timestamp
 */
function formatTimestamp($timestamp) {
    return date('Y-m-d H:i:s', strtotime($timestamp));
}

/**
 * Display alert message
 */
function displayAlert($message, $type = 'info') {
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
function validateSensorData($data) {
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
function validatePlantData($data) {
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
function checkPermission($permission) {
    if (!isset($_SESSION['permissions']) || !in_array($permission, $_SESSION['permissions'])) {
        throw new Exception('Permission denied');
    }
    return true;
}

/**
 * Get user's timezone
 */
function getUserTimezone() {
    return $_SESSION['timezone'] ?? 'UTC';
}

/**
 * Convert timestamp to user's timezone
 */
function convertToUserTimezone($timestamp) {
    $userTimezone = getUserTimezone();
    $date = new DateTime($timestamp, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone($userTimezone));
    return $date->format('Y-m-d H:i:s');
} 