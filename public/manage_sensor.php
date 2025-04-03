<?php
require_once 'includes/config.php';
require_once 'includes/api_controller.php';

class SensorController extends ApiController {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->checkRateLimit('manage_sensor');
    }

    public function handleRequest() {
        $action = $_POST['action'] ?? '';
        $sensorId = $_POST['sensor_id'] ?? null;

        try {
            $this->db->beginTransaction();

            switch ($action) {
                case 'add':
                    $this->addSensor();
                    break;
                case 'edit':
                    $this->editSensor($sensorId);
                    break;
                case 'delete':
                    $this->deleteSensor($sensorId);
                    break;
                case 'calibrate':
                    $this->calibrateSensor($sensorId);
                    break;
                default:
                    $this->sendError('Invalid action');
            }

            $this->db->commit();
            $this->logAction($action, ['sensor_id' => $sensorId]);
            $this->sendResponse(['message' => ucfirst($action) . ' successful']);
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Sensor operation failed', [
                'action' => $action,
                'error' => $e->getMessage(),
                'sensor_id' => $sensorId
            ]);
            $this->sendError('Operation failed: ' . $e->getMessage());
        }
    }

    private function addSensor() {
        $this->validateInput($_POST, [
            'name' => 'required|max:100',
            'plant_id' => 'required|numeric',
            'type' => 'required|max:50',
            'location' => 'required|max:100',
            'battery_level' => 'numeric',
            'last_reading' => 'numeric',
            'status' => 'required|max:20',
            'notes' => 'max:500'
        ]);

        // Verify plant exists and belongs to user
        $sql = "SELECT id FROM plants WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$_POST['plant_id'], $this->userId]);
        
        if ($stmt->rowCount() === 0) {
            $this->sendError('Invalid plant ID');
        }

        $sql = "INSERT INTO sensors (user_id, plant_id, name, type, location, battery_level, last_reading, status, notes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->userId,
            $_POST['plant_id'],
            $_POST['name'],
            $_POST['type'],
            $_POST['location'],
            $_POST['battery_level'] ?? null,
            $_POST['last_reading'] ?? null,
            $_POST['status'],
            $_POST['notes'] ?? null
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to add sensor');
        }

        // Clear cache
        $this->cache->delete("sensors:{$this->userId}");
    }

    private function editSensor($sensorId) {
        if (!$sensorId) {
            $this->sendError('Sensor ID is required');
        }

        $this->validateInput($_POST, [
            'name' => 'required|max:100',
            'plant_id' => 'required|numeric',
            'type' => 'required|max:50',
            'location' => 'required|max:100',
            'battery_level' => 'numeric',
            'last_reading' => 'numeric',
            'status' => 'required|max:20',
            'notes' => 'max:500'
        ]);

        // Verify plant exists and belongs to user
        $sql = "SELECT id FROM plants WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$_POST['plant_id'], $this->userId]);
        
        if ($stmt->rowCount() === 0) {
            $this->sendError('Invalid plant ID');
        }

        $sql = "UPDATE sensors 
                SET name = ?, plant_id = ?, type = ?, location = ?, battery_level = ?, last_reading = ?, status = ?, notes = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $_POST['name'],
            $_POST['plant_id'],
            $_POST['type'],
            $_POST['location'],
            $_POST['battery_level'] ?? null,
            $_POST['last_reading'] ?? null,
            $_POST['status'],
            $_POST['notes'] ?? null,
            $sensorId,
            $this->userId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Sensor not found or unauthorized');
        }

        // Clear cache
        $this->cache->delete("sensors:{$this->userId}");
    }

    private function deleteSensor($sensorId) {
        if (!$sensorId) {
            $this->sendError('Sensor ID is required');
        }

        $sql = "DELETE FROM sensors WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sensorId, $this->userId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Sensor not found or unauthorized');
        }

        // Clear cache
        $this->cache->delete("sensors:{$this->userId}");
    }

    private function calibrateSensor($sensorId) {
        if (!$sensorId) {
            $this->sendError('Sensor ID is required');
        }

        $this->validateInput($_POST, [
            'calibration_value' => 'required|numeric',
            'calibration_type' => 'required|max:20'
        ]);

        $sql = "UPDATE sensors 
                SET calibration_value = ?, calibration_type = ?, last_calibration = NOW(), updated_at = NOW() 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $_POST['calibration_value'],
            $_POST['calibration_type'],
            $sensorId,
            $this->userId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Sensor not found or unauthorized');
        }

        // Clear cache
        $this->cache->delete("sensors:{$this->userId}");
    }
}

// Handle the request
$controller = new SensorController();
$controller->handleRequest(); 