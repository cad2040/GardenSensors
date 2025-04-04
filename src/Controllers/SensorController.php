<?php
namespace App\Controllers;

use App\Models\Sensor;
use App\Services\SensorService;

class SensorController extends BaseController {
    private $sensorService;

    public function __construct() {
        $this->sensorService = new SensorService();
    }

    public function index() {
        $sensors = $this->sensorService->getAllSensors();
        $this->render('sensors/index', ['sensors' => $sensors]);
    }

    public function show($id) {
        $sensor = $this->sensorService->getSensorById($id);
        if (!$sensor) {
            $this->redirect('/sensors');
        }
        $this->render('sensors/show', ['sensor' => $sensor]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $result = $this->sensorService->createSensor($data);
            if ($result) {
                $this->redirect('/sensors');
            }
            $this->render('sensors/create', ['error' => 'Failed to create sensor']);
        }
        $this->render('sensors/create');
    }

    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $result = $this->sensorService->updateSensor($id, $data);
            if ($result) {
                $this->redirect('/sensors');
            }
            $this->render('sensors/edit', ['error' => 'Failed to update sensor']);
        }
        $sensor = $this->sensorService->getSensorById($id);
        $this->render('sensors/edit', ['sensor' => $sensor]);
    }

    public function delete($id) {
        $result = $this->sensorService->deleteSensor($id);
        $this->json(['success' => $result]);
    }

    public function getReadings($id) {
        $readings = $this->sensorService->getSensorReadings($id);
        $this->json($readings);
    }
} 