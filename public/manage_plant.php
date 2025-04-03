<?php
require_once 'includes/config.php';
require_once 'includes/api_controller.php';

class PlantController extends ApiController {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->checkRateLimit('manage_plant');
    }

    public function handleRequest() {
        $action = $_POST['action'] ?? '';
        $plantId = $_POST['plant_id'] ?? null;

        try {
            $this->db->beginTransaction();

            switch ($action) {
                case 'add':
                    $this->addPlant();
                    break;
                case 'edit':
                    $this->editPlant($plantId);
                    break;
                case 'delete':
                    $this->deletePlant($plantId);
                    break;
                default:
                    $this->sendError('Invalid action');
            }

            $this->db->commit();
            $this->logAction($action, ['plant_id' => $plantId]);
            $this->sendResponse(['message' => ucfirst($action) . ' successful']);
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Plant operation failed', [
                'action' => $action,
                'error' => $e->getMessage(),
                'plant_id' => $plantId
            ]);
            $this->sendError('Operation failed: ' . $e->getMessage());
        }
    }

    private function addPlant() {
        $this->validateInput($_POST, [
            'name' => 'required|max:100',
            'type' => 'required|max:50',
            'location' => 'required|max:100',
            'min_moisture' => 'required|numeric',
            'max_moisture' => 'required|numeric',
            'notes' => 'max:500'
        ]);

        $sql = "INSERT INTO plants (user_id, name, type, location, min_moisture, max_moisture, notes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->userId,
            $_POST['name'],
            $_POST['type'],
            $_POST['location'],
            $_POST['min_moisture'],
            $_POST['max_moisture'],
            $_POST['notes'] ?? null
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to add plant');
        }

        // Clear cache
        $this->cache->delete("plants:{$this->userId}");
    }

    private function editPlant($plantId) {
        if (!$plantId) {
            $this->sendError('Plant ID is required');
        }

        $this->validateInput($_POST, [
            'name' => 'required|max:100',
            'type' => 'required|max:50',
            'location' => 'required|max:100',
            'min_moisture' => 'required|numeric',
            'max_moisture' => 'required|numeric',
            'notes' => 'max:500'
        ]);

        $sql = "UPDATE plants 
                SET name = ?, type = ?, location = ?, min_moisture = ?, max_moisture = ?, notes = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $_POST['name'],
            $_POST['type'],
            $_POST['location'],
            $_POST['min_moisture'],
            $_POST['max_moisture'],
            $_POST['notes'] ?? null,
            $plantId,
            $this->userId
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Plant not found or unauthorized');
        }

        // Clear cache
        $this->cache->delete("plants:{$this->userId}");
    }

    private function deletePlant($plantId) {
        if (!$plantId) {
            $this->sendError('Plant ID is required');
        }

        // Check for associated sensors
        $sql = "SELECT COUNT(*) as count FROM sensors WHERE plant_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$plantId, $this->userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $this->sendError('Cannot delete plant with associated sensors');
        }

        $sql = "DELETE FROM plants WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$plantId, $this->userId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Plant not found or unauthorized');
        }

        // Clear cache
        $this->cache->delete("plants:{$this->userId}");
    }
}

// Handle the request
$controller = new PlantController();
$controller->handleRequest(); 