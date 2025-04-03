<?php
namespace App\Controllers;

use App\Models\Sensor;

class SensorController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(): void {
        $userId = $this->getUserId();
        $sensors = Sensor::where('user_id', $userId);

        $this->view('sensors/index', [
            'sensors' => $sensors,
            'types' => Sensor::getTypes(),
            'statuses' => Sensor::getStatuses(),
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function show(int $id): void {
        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        if ($this->request->isAjax()) {
            $this->json([
                'sensor' => $sensor->toArray(),
                'readings' => $sensor->readings(10)
            ]);
        } else {
            $this->view('sensors/show', [
                'sensor' => $sensor,
                'readings' => $sensor->readings(10),
                'csrf_token' => $this->getCsrfToken()
            ]);
        }
    }

    public function create(): void {
        $this->view('sensors/create', [
            'types' => Sensor::getTypes(),
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function store(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'name' => 'required|min:3|max:50',
            'type' => 'required|in:' . implode(',', Sensor::getTypes()),
            'location' => 'required|max:100'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $sensor = new Sensor([
            'user_id' => $this->getUserId(),
            'name' => $this->request->post('name'),
            'type' => $this->request->post('type'),
            'location' => $this->request->post('location'),
            'status' => Sensor::STATUS_ACTIVE,
            'battery_level' => 100,
            'firmware_version' => '1.0.0'
        ]);

        if (!$sensor->save()) {
            $this->respondWithError('Failed to create sensor');
            return;
        }

        $this->respondWithSuccess('Sensor created successfully', '/sensors/' . $sensor->sensor_id);
    }

    public function edit(int $id): void {
        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        $this->view('sensors/edit', [
            'sensor' => $sensor,
            'types' => Sensor::getTypes(),
            'statuses' => Sensor::getStatuses(),
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function update(int $id): void {
        $this->requireCsrfToken();

        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        $errors = $this->validate([
            'name' => 'required|min:3|max:50',
            'type' => 'required|in:' . implode(',', Sensor::getTypes()),
            'location' => 'required|max:100',
            'status' => 'required|in:' . implode(',', Sensor::getStatuses())
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $sensor->fill([
            'name' => $this->request->post('name'),
            'type' => $this->request->post('type'),
            'location' => $this->request->post('location'),
            'status' => $this->request->post('status')
        ]);

        if (!$sensor->save()) {
            $this->respondWithError('Failed to update sensor');
            return;
        }

        $this->respondWithSuccess('Sensor updated successfully', '/sensors/' . $sensor->sensor_id);
    }

    public function delete(int $id): void {
        $this->requireCsrfToken();

        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        if (!$sensor->delete()) {
            $this->respondWithError('Failed to delete sensor');
            return;
        }

        $this->respondWithSuccess('Sensor deleted successfully', '/sensors');
    }

    public function readings(int $id): void {
        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        $startDate = $this->request->get('start_date');
        $endDate = $this->request->get('end_date');
        
        if ($startDate && $endDate) {
            $readings = $sensor->getReadingsByDateRange($startDate, $endDate);
        } else {
            $limit = (int) $this->request->get('limit', 100);
            $readings = $sensor->readings($limit);
        }

        $this->json([
            'sensor' => $sensor->toArray(),
            'readings' => $readings
        ]);
    }

    public function addReading(int $id): void {
        $this->requireCsrfToken();

        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        $errors = $this->validate([
            'value' => 'required|numeric',
            'unit' => 'required'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $value = (float) $this->request->post('value');
        $unit = $this->request->post('unit');
        $notes = $this->request->post('notes');

        if (!$sensor->addReading($value, $unit, $notes)) {
            $this->respondWithError('Failed to add reading');
            return;
        }

        $this->respondWithSuccess('Reading added successfully');
    }

    public function updateStatus(int $id): void {
        $this->requireCsrfToken();

        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        $errors = $this->validate([
            'status' => 'required|in:' . implode(',', Sensor::getStatuses())
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid status');
            return;
        }

        $status = $this->request->post('status');
        
        if (!$sensor->updateStatus($status)) {
            $this->respondWithError('Failed to update status');
            return;
        }

        $this->respondWithSuccess('Status updated successfully');
    }

    public function updateBattery(int $id): void {
        $this->requireCsrfToken();

        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        $errors = $this->validate([
            'level' => 'required|numeric|min:0|max:100'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid battery level');
            return;
        }

        $level = (int) $this->request->post('level');
        
        if (!$sensor->updateBatteryLevel($level)) {
            $this->respondWithError('Failed to update battery level');
            return;
        }

        $this->respondWithSuccess('Battery level updated successfully');
    }

    public function updateFirmware(int $id): void {
        $this->requireCsrfToken();

        $sensor = Sensor::find($id);
        
        if (!$sensor || $sensor->user_id !== $this->getUserId()) {
            $this->respondWithError('Sensor not found', 404);
            return;
        }

        $errors = $this->validate([
            'version' => 'required|max:20'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid firmware version');
            return;
        }

        $version = $this->request->post('version');
        
        if (!$sensor->updateFirmware($version)) {
            $this->respondWithError('Failed to update firmware version');
            return;
        }

        $this->respondWithSuccess('Firmware version updated successfully');
    }
} 