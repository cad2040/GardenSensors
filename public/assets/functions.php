<?php
// Include configuration file
require_once 'config.php';

// Database functions
function getDatabaseConnection() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db->getConnection();
}

// User functions
function createUser($email, $password, $name = '') {
    try {
        $conn = getDatabaseConnection();
        
        // Check if user already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            throw new Exception("User with this email already exists");
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_HASH_ALGO);
        
        // Insert new user
        $query = "INSERT INTO users (email, password, name, created_at) VALUES (:email, :password, :name, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':email' => $email,
            ':password' => $hashedPassword,
            ':name' => $name
        ]);
        
        return $conn->lastInsertId();
    } catch (Exception $e) {
        logError("Error creating user: " . $e->getMessage());
        throw $e;
    }
}

function updateUser($userId, $data) {
    try {
        $conn = getDatabaseConnection();
        
        $allowedFields = ['name', 'email', 'password', 'role'];
        $updates = [];
        $params = [':user_id' => $userId];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updates[] = "$field = :$field";
                $params[":$field"] = $value;
            }
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        return $stmt->execute($params);
    } catch (Exception $e) {
        logError("Error updating user: " . $e->getMessage());
        throw $e;
    }
}

function deleteUser($userId) {
    try {
        $conn = getDatabaseConnection();
        
        $query = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $conn->prepare($query);
        return $stmt->execute([':user_id' => $userId]);
    } catch (Exception $e) {
        logError("Error deleting user: " . $e->getMessage());
        throw $e;
    }
}

// Sensor functions
function getSensorReadings($sensorId, $limit = 100) {
    try {
        $conn = getDatabaseConnection();
        
        $query = "SELECT * FROM sensor_readings WHERE sensor_id = :sensor_id ORDER BY timestamp DESC LIMIT :limit";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':sensor_id', $sensorId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        logError("Error getting sensor readings: " . $e->getMessage());
        throw $e;
    }
}

function addSensorReading($sensorId, $moisture, $temperature, $battery) {
    try {
        $conn = getDatabaseConnection();
        
        $query = "INSERT INTO sensor_readings (sensor_id, moisture, temperature, battery, timestamp) 
                 VALUES (:sensor_id, :moisture, :temperature, :battery, NOW())";
        $stmt = $conn->prepare($query);
        return $stmt->execute([
            ':sensor_id' => $sensorId,
            ':moisture' => $moisture,
            ':temperature' => $temperature,
            ':battery' => $battery
        ]);
    } catch (Exception $e) {
        logError("Error adding sensor reading: " . $e->getMessage());
        throw $e;
    }
}

// Alert functions
function checkBatteryLevel($sensorId) {
    try {
        $conn = getDatabaseConnection();
        
        $query = "SELECT battery FROM sensor_readings WHERE sensor_id = :sensor_id ORDER BY timestamp DESC LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute([':sensor_id' => $sensorId]);
        $reading = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reading && $reading['battery'] <= ALERT_THRESHOLD) {
            sendAlert($sensorId, "Low battery level: {$reading['battery']}%");
        }
    } catch (Exception $e) {
        logError("Error checking battery level: " . $e->getMessage());
    }
}

function sendAlert($sensorId, $message) {
    try {
        $conn = getDatabaseConnection();
        
        $query = "INSERT INTO alerts (sensor_id, message, created_at) VALUES (:sensor_id, :message, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':sensor_id' => $sensorId,
            ':message' => $message
        ]);
        
        // TODO: Implement email notification
    } catch (Exception $e) {
        logError("Error sending alert: " . $e->getMessage());
    }
}

// Cache functions
function getCachedData($key) {
    if (!CACHE_ENABLED) {
        return null;
    }
    
    $file = CACHE_PATH . '/' . md5($key) . '.cache';
    if (file_exists($file) && (time() - filemtime($file) < CACHE_LIFETIME)) {
        return unserialize(file_get_contents($file));
    }
    return null;
}

function setCachedData($key, $data) {
    if (!CACHE_ENABLED) {
        return false;
    }
    
    $file = CACHE_PATH . '/' . md5($key) . '.cache';
    return file_put_contents($file, serialize($data));
}

function clearCache($key = null) {
    if ($key) {
        $file = CACHE_PATH . '/' . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    } else {
        array_map('unlink', glob(CACHE_PATH . '/*.cache'));
    }
} 