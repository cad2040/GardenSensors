<?php
require_once 'includes/config.php';
require_once 'includes/api_controller.php';

class ReadingController extends ApiController {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->checkRateLimit('manage_reading');
    }

    public function handleRequest() {
        $action = $_POST['action'] ?? '';
        $readingId = $_POST['reading_id'] ?? null;

        try {
            $this->db->beginTransaction();

            switch ($action) {
                case 'add':
                    $this->addReading();
                    break;
                case 'delete':
                    $this->deleteReading($readingId);
                    break;
                case 'export':
                    $this->exportReadings();
                    break;
                default:
                    $this->sendError('Invalid action');
            }

            $this->db->commit();
            $this->logAction($action, ['reading_id' => $readingId]);
            $this->sendResponse(['message' => ucfirst($action) . ' successful']);
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->logger->error('Reading operation failed', [
                'action' => $action,
                'error' => $e->getMessage(),
                'reading_id' => $readingId
            ]);
            $this->sendError('Operation failed: ' . $e->getMessage());
        }
    }

    private function addReading() {
        $this->validateInput($_POST, [
            'sensor_id' => 'required|numeric',
            'value' => 'required|numeric',
            'timestamp' => 'required|numeric',
            'battery_level' => 'numeric'
        ]);

        // Verify sensor exists and belongs to user
        $sql = "SELECT id FROM sensors WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$_POST['sensor_id'], $this->userId]);
        
        if ($stmt->rowCount() === 0) {
            $this->sendError('Invalid sensor ID');
        }

        $sql = "INSERT INTO readings (user_id, sensor_id, value, timestamp, battery_level, created_at) 
                VALUES (?, ?, ?, FROM_UNIXTIME(?), ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->userId,
            $_POST['sensor_id'],
            $_POST['value'],
            $_POST['timestamp'],
            $_POST['battery_level'] ?? null
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to add reading');
        }

        // Update sensor's last reading and battery level
        $sql = "UPDATE sensors 
                SET last_reading = ?, battery_level = ?, updated_at = NOW() 
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $_POST['value'],
            $_POST['battery_level'] ?? null,
            $_POST['sensor_id'],
            $this->userId
        ]);

        // Clear cache
        $this->cache->delete("readings:{$this->userId}:{$_POST['sensor_id']}");
    }

    private function deleteReading($readingId) {
        if (!$readingId) {
            $this->sendError('Reading ID is required');
        }

        $sql = "DELETE FROM readings WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$readingId, $this->userId]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Reading not found or unauthorized');
        }

        // Clear cache
        $this->cache->delete("readings:{$this->userId}");
    }

    private function exportReadings() {
        $this->validateInput($_POST, [
            'sensor_id' => 'required|numeric',
            'start_date' => 'required|numeric',
            'end_date' => 'required|numeric',
            'format' => 'required|max:10'
        ]);

        // Verify sensor exists and belongs to user
        $sql = "SELECT id FROM sensors WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$_POST['sensor_id'], $this->userId]);
        
        if ($stmt->rowCount() === 0) {
            $this->sendError('Invalid sensor ID');
        }

        // Get readings
        $sql = "SELECT r.*, s.name as sensor_name 
                FROM readings r 
                JOIN sensors s ON r.sensor_id = s.id 
                WHERE r.sensor_id = ? AND r.user_id = ? 
                AND r.timestamp BETWEEN FROM_UNIXTIME(?) AND FROM_UNIXTIME(?) 
                ORDER BY r.timestamp ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $_POST['sensor_id'],
            $this->userId,
            $_POST['start_date'],
            $_POST['end_date']
        ]);
        
        $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($readings)) {
            $this->sendError('No readings found for the specified period');
        }

        // Format data based on requested format
        $data = $this->formatExportData($readings, $_POST['format']);
        
        // Set appropriate headers for download
        $filename = "readings_{$_POST['sensor_id']}_{$_POST['start_date']}_{$_POST['end_date']}";
        switch ($_POST['format']) {
            case 'csv':
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                break;
            case 'json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="' . $filename . '.json"');
                break;
            default:
                $this->sendError('Unsupported export format');
        }

        echo $data;
        exit;
    }

    private function formatExportData($readings, $format) {
        switch ($format) {
            case 'csv':
                $output = fopen('php://output', 'w');
                fputcsv($output, ['Timestamp', 'Sensor', 'Value', 'Battery Level']);
                
                foreach ($readings as $reading) {
                    fputcsv($output, [
                        $reading['timestamp'],
                        $reading['sensor_name'],
                        $reading['value'],
                        $reading['battery_level']
                    ]);
                }
                
                fclose($output);
                return '';
                
            case 'json':
                return json_encode($readings, JSON_PRETTY_PRINT);
                
            default:
                throw new Exception('Unsupported export format');
        }
    }
}

// Handle the request
$controller = new ReadingController();
$controller->handleRequest(); 