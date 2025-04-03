<?php
namespace App\Controllers;

use App\Models\Plant;
use App\Models\Sensor;

class PlantController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(): void {
        $userId = $this->getUserId();
        $plants = Plant::where('user_id', $userId);

        $this->view('plants/index', [
            'plants' => $plants,
            'statuses' => Plant::getStatuses(),
            'healthStatuses' => Plant::getHealthStatuses(),
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function show(int $id): void {
        $plant = Plant::find($id);
        
        if (!$plant || $plant->user_id !== $this->getUserId()) {
            $this->respondWithError('Plant not found', 404);
            return;
        }

        if ($this->request->isAjax()) {
            $this->json([
                'plant' => $plant->toArray(),
                'sensors' => $plant->sensors(),
                'environmental_conditions' => $plant->getEnvironmentalConditions()
            ]);
        } else {
            $this->view('plants/show', [
                'plant' => $plant,
                'sensors' => $plant->sensors(),
                'environmental_conditions' => $plant->getEnvironmentalConditions(),
                'csrf_token' => $this->getCsrfToken()
            ]);
        }
    }

    public function create(): void {
        $userId = $this->getUserId();
        $availableSensors = Sensor::where('user_id', $userId);

        $this->view('plants/create', [
            'sensors' => $availableSensors,
            'statuses' => Plant::getStatuses(),
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function store(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'name' => 'required|min:3|max:50',
            'species' => 'required|max:100',
            'location' => 'required|max:100',
            'planting_date' => 'required|date',
            'watering_frequency' => 'required|numeric|min:1',
            'fertilizing_frequency' => 'required|numeric|min:1',
            'optimal_temperature' => 'numeric',
            'optimal_humidity' => 'numeric',
            'optimal_soil_moisture' => 'numeric',
            'optimal_light' => 'numeric',
            'optimal_ph' => 'numeric',
            'sensors' => 'array'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $plant = new Plant([
            'user_id' => $this->getUserId(),
            'name' => $this->request->post('name'),
            'species' => $this->request->post('species'),
            'location' => $this->request->post('location'),
            'planting_date' => $this->request->post('planting_date'),
            'watering_frequency' => $this->request->post('watering_frequency'),
            'fertilizing_frequency' => $this->request->post('fertilizing_frequency'),
            'optimal_temperature' => $this->request->post('optimal_temperature'),
            'optimal_humidity' => $this->request->post('optimal_humidity'),
            'optimal_soil_moisture' => $this->request->post('optimal_soil_moisture'),
            'optimal_light' => $this->request->post('optimal_light'),
            'optimal_ph' => $this->request->post('optimal_ph'),
            'notes' => $this->request->post('notes'),
            'status' => Plant::STATUS_ACTIVE,
            'health_status' => Plant::HEALTH_GOOD
        ]);

        if (!$plant->save()) {
            $this->respondWithError('Failed to create plant');
            return;
        }

        // Associate sensors with the plant
        $sensorIds = $this->request->post('sensors', []);
        foreach ($sensorIds as $sensorId) {
            $sensor = Sensor::find($sensorId);
            if ($sensor && $sensor->user_id === $this->getUserId()) {
                $plant->addSensor($sensor);
            }
        }

        $this->respondWithSuccess('Plant created successfully', '/plants/' . $plant->plant_id);
    }

    public function edit(int $id): void {
        $plant = Plant::find($id);
        
        if (!$plant || $plant->user_id !== $this->getUserId()) {
            $this->respondWithError('Plant not found', 404);
            return;
        }

        $userId = $this->getUserId();
        $availableSensors = Sensor::where('user_id', $userId);
        $plantSensors = $plant->sensors();

        $this->view('plants/edit', [
            'plant' => $plant,
            'availableSensors' => $availableSensors,
            'plantSensors' => $plantSensors,
            'statuses' => Plant::getStatuses(),
            'healthStatuses' => Plant::getHealthStatuses(),
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function update(int $id): void {
        $this->requireCsrfToken();

        $plant = Plant::find($id);
        
        if (!$plant || $plant->user_id !== $this->getUserId()) {
            $this->respondWithError('Plant not found', 404);
            return;
        }

        $errors = $this->validate([
            'name' => 'required|min:3|max:50',
            'species' => 'required|max:100',
            'location' => 'required|max:100',
            'planting_date' => 'required|date',
            'watering_frequency' => 'required|numeric|min:1',
            'fertilizing_frequency' => 'required|numeric|min:1',
            'optimal_temperature' => 'numeric',
            'optimal_humidity' => 'numeric',
            'optimal_soil_moisture' => 'numeric',
            'optimal_light' => 'numeric',
            'optimal_ph' => 'numeric',
            'status' => 'required|in:' . implode(',', Plant::getStatuses()),
            'health_status' => 'required|in:' . implode(',', Plant::getHealthStatuses()),
            'sensors' => 'array'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $plant->fill([
            'name' => $this->request->post('name'),
            'species' => $this->request->post('species'),
            'location' => $this->request->post('location'),
            'planting_date' => $this->request->post('planting_date'),
            'watering_frequency' => $this->request->post('watering_frequency'),
            'fertilizing_frequency' => $this->request->post('fertilizing_frequency'),
            'optimal_temperature' => $this->request->post('optimal_temperature'),
            'optimal_humidity' => $this->request->post('optimal_humidity'),
            'optimal_soil_moisture' => $this->request->post('optimal_soil_moisture'),
            'optimal_light' => $this->request->post('optimal_light'),
            'optimal_ph' => $this->request->post('optimal_ph'),
            'notes' => $this->request->post('notes'),
            'status' => $this->request->post('status'),
            'health_status' => $this->request->post('health_status')
        ]);

        if (!$plant->save()) {
            $this->respondWithError('Failed to update plant');
            return;
        }

        // Update sensor associations
        $currentSensors = $plant->sensors();
        $newSensorIds = $this->request->post('sensors', []);

        // Remove sensors that are no longer associated
        foreach ($currentSensors as $sensor) {
            if (!in_array($sensor->sensor_id, $newSensorIds)) {
                $plant->removeSensor($sensor);
            }
        }

        // Add new sensor associations
        foreach ($newSensorIds as $sensorId) {
            $sensor = Sensor::find($sensorId);
            if ($sensor && $sensor->user_id === $this->getUserId()) {
                $plant->addSensor($sensor);
            }
        }

        $this->respondWithSuccess('Plant updated successfully', '/plants/' . $plant->plant_id);
    }

    public function delete(int $id): void {
        $this->requireCsrfToken();

        $plant = Plant::find($id);
        
        if (!$plant || $plant->user_id !== $this->getUserId()) {
            $this->respondWithError('Plant not found', 404);
            return;
        }

        if (!$plant->delete()) {
            $this->respondWithError('Failed to delete plant');
            return;
        }

        $this->respondWithSuccess('Plant deleted successfully', '/plants');
    }

    public function updateWatering(int $id): void {
        $this->requireCsrfToken();

        $plant = Plant::find($id);
        
        if (!$plant || $plant->user_id !== $this->getUserId()) {
            $this->respondWithError('Plant not found', 404);
            return;
        }

        if (!$plant->updateWatering()) {
            $this->respondWithError('Failed to update watering status');
            return;
        }

        $this->respondWithSuccess('Watering status updated successfully');
    }

    public function updateFertilizing(int $id): void {
        $this->requireCsrfToken();

        $plant = Plant::find($id);
        
        if (!$plant || $plant->user_id !== $this->getUserId()) {
            $this->respondWithError('Plant not found', 404);
            return;
        }

        if (!$plant->updateFertilizing()) {
            $this->respondWithError('Failed to update fertilizing status');
            return;
        }

        $this->respondWithSuccess('Fertilizing status updated successfully');
    }

    public function updateHealthStatus(int $id): void {
        $this->requireCsrfToken();

        $plant = Plant::find($id);
        
        if (!$plant || $plant->user_id !== $this->getUserId()) {
            $this->respondWithError('Plant not found', 404);
            return;
        }

        $errors = $this->validate([
            'health_status' => 'required|in:' . implode(',', Plant::getHealthStatuses())
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid health status');
            return;
        }

        $status = $this->request->post('health_status');
        
        if (!$plant->updateHealthStatus($status)) {
            $this->respondWithError('Failed to update health status');
            return;
        }

        $this->respondWithSuccess('Health status updated successfully');
    }

    public function checkHealth(int $id): void {
        $plant = Plant::find($id);
        
        if (!$plant || $plant->user_id !== $this->getUserId()) {
            $this->respondWithError('Plant not found', 404);
            return;
        }

        $healthStatus = $plant->checkHealthStatus();
        $plant->updateHealthStatus($healthStatus);

        $this->json([
            'health_status' => $healthStatus,
            'environmental_conditions' => $plant->getEnvironmentalConditions()
        ]);
    }
} 