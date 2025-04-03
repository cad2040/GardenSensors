<?php
/**
 * Utility functions for the Garden Sensors Dashboard
 */

// Check if functions are already defined to prevent redeclaration
if (!function_exists('sanitizeInput')) {
    /**
     * Sanitize user input
     */
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

if (!function_exists('generateCSRFToken')) {
    /**
     * Generate CSRF token
     */
    function generateCSRFToken() {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }
}

if (!function_exists('validateCSRFToken')) {
    /**
     * Validate CSRF token
     */
    function validateCSRFToken($token) {
        return !empty($_SESSION[CSRF_TOKEN_NAME]) && 
               hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
}

if (!function_exists('logError')) {
    /**
     * Log errors
     */
    function logError($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? json_encode($context) : '';
        $logMessage = "[$timestamp] ERROR: $message $contextStr\n";
        error_log($logMessage, 3, LOGS_PATH . '/error.log');
    }
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

/**
 * User functions
 */

if (!function_exists('getDbConnection')) {
    /**
     * Get database connection
     */
    function getDbConnection() {
        static $conn = null;
        if ($conn === null) {
            try {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
                if ($conn->connect_error) {
                    throw new Exception("Connection failed: " . $conn->connect_error);
                }
                $conn->set_charset("utf8mb4");
            } catch (Exception $e) {
                logError("Database connection error: " . $e->getMessage());
                throw $e;
            }
        }
        return $conn;
    }
}

if (!function_exists('getUserById')) {
    /**
     * Get user by ID
     */
    function getUserById($user_id) {
        try {
            $conn = getDbConnection();
            $user_id = sanitizeInput($user_id);
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            logError("Error getting user by ID: " . $e->getMessage(), ['user_id' => $user_id]);
            return null;
        }
    }
}

if (!function_exists('getUserByEmail')) {
    /**
     * Get user by email
     */
    function getUserByEmail($email) {
        try {
            $conn = getDbConnection();
            $email = sanitizeInput($email);
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            logError("Error getting user by email: " . $e->getMessage(), ['email' => $email]);
            return null;
        }
    }
}

if (!function_exists('isLoggedIn')) {
    /**
     * Check if user is logged in
     */
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('requireLogin')) {
    /**
     * Require user to be logged in
     */
    function requireLogin() {
        if (!isLoggedIn()) {
            header('Location: ' . APP_URL . '/login.php');
            exit();
        }
    }
}

if (!function_exists('isAdmin')) {
    /**
     * Check if user is admin
     */
    function isAdmin() {
        if (!isLoggedIn()) return false;
        $user = getUserById($_SESSION['user_id']);
        return $user && $user['role'] === 'admin';
    }
}

if (!function_exists('requireAdmin')) {
    /**
     * Require user to be admin
     */
    function requireAdmin() {
        if (!isAdmin()) {
            header('Location: ' . APP_URL . '/index.php');
            exit();
        }
    }
}

/**
 * Response helpers
 */

if (!function_exists('sendJsonResponse')) {
    /**
     * Send JSON response
     */
    function sendJsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit();
    }
} 