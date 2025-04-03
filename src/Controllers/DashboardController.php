<?php
namespace App\Controllers;

use App\Models\Sensor;
use App\Models\Plant;

class DashboardController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
    }

    public function index(): void {
        $user = $this->getUser();
        $userId = $this->getUserId();

        // Get user's sensors
        $sensors = Sensor::where('user_id', $userId);
        $activeSensors = array_filter($sensors, function($sensor) {
            return $sensor->isActive();
        });

        // Get user's plants
        $plants = Plant::where('user_id', $userId);
        $activePlants = array_filter($plants, function($plant) {
            return $plant->isActive();
        });

        // Get recent sensor readings
        $recentReadings = [];
        foreach ($activeSensors as $sensor) {
            $readings = $sensor->readings(5); // Get last 5 readings
            if (!empty($readings)) {
                $recentReadings[] = [
                    'sensor' => $sensor,
                    'readings' => $readings
                ];
            }
        }

        // Get plants that need attention
        $plantsNeedingAttention = array_filter($activePlants, function($plant) {
            return $plant->needsWatering() || $plant->needsFertilizing() || 
                   in_array($plant->health_status, [Plant::HEALTH_POOR, Plant::HEALTH_CRITICAL]);
        });

        // Get system alerts
        $alerts = $this->getSystemAlerts($activeSensors, $activePlants);

        $this->view('dashboard/index', [
            'user' => $user,
            'sensors' => [
                'total' => count($sensors),
                'active' => count($activeSensors),
                'inactive' => count($sensors) - count($activeSensors)
            ],
            'plants' => [
                'total' => count($plants),
                'active' => count($activePlants),
                'inactive' => count($plants) - count($activePlants),
                'needingAttention' => count($plantsNeedingAttention)
            ],
            'recentReadings' => $recentReadings,
            'alerts' => $alerts,
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function getTabData(): void {
        $this->requireAuth();

        $tab = $this->request->get('tab');
        $userId = $this->getUserId();

        switch ($tab) {
            case 'sensors':
                $data = $this->getSensorsData($userId);
                break;
            case 'plants':
                $data = $this->getPlantsData($userId);
                break;
            case 'settings':
                $data = $this->getSettingsData($userId);
                break;
            default:
                $data = $this->getDashboardData($userId);
        }

        $this->json($data);
    }

    private function getDashboardData(int $userId): array {
        $sensors = Sensor::where('user_id', $userId);
        $plants = Plant::where('user_id', $userId);

        $sensorStats = [
            'total' => count($sensors),
            'active' => count(array_filter($sensors, function($s) { return $s->isActive(); })),
            'maintenance' => count(array_filter($sensors, function($s) { return $s->isInMaintenance(); })),
            'error' => count(array_filter($sensors, function($s) { return $s->hasError(); }))
        ];

        $plantStats = [
            'total' => count($plants),
            'healthy' => count(array_filter($plants, function($p) { 
                return in_array($p->health_status, [Plant::HEALTH_EXCELLENT, Plant::HEALTH_GOOD]); 
            })),
            'needsAttention' => count(array_filter($plants, function($p) { 
                return in_array($p->health_status, [Plant::HEALTH_FAIR, Plant::HEALTH_POOR, Plant::HEALTH_CRITICAL]); 
            }))
        ];

        $recentReadings = [];
        foreach ($sensors as $sensor) {
            $readings = $sensor->readings(5);
            if (!empty($readings)) {
                $recentReadings[] = [
                    'sensor_name' => $sensor->name,
                    'sensor_type' => $sensor->type,
                    'readings' => array_map(function($reading) {
                        return [
                            'value' => $reading['value'],
                            'unit' => $reading['unit'],
                            'time' => $reading['reading_time']
                        ];
                    }, $readings)
                ];
            }
        }

        return [
            'sensor_stats' => $sensorStats,
            'plant_stats' => $plantStats,
            'recent_readings' => $recentReadings,
            'alerts' => $this->getSystemAlerts($sensors, $plants)
        ];
    }

    private function getSensorsData(int $userId): array {
        $sensors = Sensor::where('user_id', $userId);
        
        return array_map(function($sensor) {
            $latestReading = $sensor->getLatestReading();
            return [
                'id' => $sensor->sensor_id,
                'name' => $sensor->name,
                'type' => $sensor->type,
                'location' => $sensor->location,
                'status' => $sensor->status,
                'last_reading' => $latestReading ? [
                    'value' => $latestReading['value'],
                    'unit' => $latestReading['unit'],
                    'time' => $latestReading['reading_time']
                ] : null,
                'battery_level' => $sensor->battery_level,
                'firmware_version' => $sensor->firmware_version
            ];
        }, $sensors);
    }

    private function getPlantsData(int $userId): array {
        $plants = Plant::where('user_id', $userId);
        
        return array_map(function($plant) {
            return [
                'id' => $plant->plant_id,
                'name' => $plant->name,
                'species' => $plant->species,
                'location' => $plant->location,
                'age' => $plant->getAge(),
                'health_status' => $plant->health_status,
                'needs_water' => $plant->needsWatering(),
                'needs_fertilizer' => $plant->needsFertilizing(),
                'last_watered' => $plant->last_watered,
                'last_fertilized' => $plant->last_fertilized,
                'environmental_conditions' => $plant->getEnvironmentalConditions()
            ];
        }, $plants);
    }

    private function getSettingsData(int $userId): array {
        $user = $this->getUser();
        
        return [
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'notifications' => [
                'email_alerts' => true, // TODO: Implement notification settings
                'browser_notifications' => true
            ],
            'preferences' => [
                'temperature_unit' => 'C', // TODO: Implement user preferences
                'dashboard_refresh_rate' => 300
            ]
        ];
    }

    private function getSystemAlerts(array $sensors, array $plants): array {
        $alerts = [];

        // Check for sensors with low battery
        foreach ($sensors as $sensor) {
            if ($sensor->battery_level < 20) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => "Low battery on sensor '{$sensor->name}' ({$sensor->battery_level}%)"
                ];
            }

            if ($sensor->hasError()) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => "Sensor '{$sensor->name}' is reporting an error"
                ];
            }
        }

        // Check for plants needing attention
        foreach ($plants as $plant) {
            if ($plant->needsWatering()) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => "Plant '{$plant->name}' needs watering"
                ];
            }

            if ($plant->needsFertilizing()) {
                $alerts[] = [
                    'type' => 'info',
                    'message' => "Plant '{$plant->name}' needs fertilizing"
                ];
            }

            if (in_array($plant->health_status, [Plant::HEALTH_POOR, Plant::HEALTH_CRITICAL])) {
                $alerts[] = [
                    'type' => 'error',
                    'message' => "Plant '{$plant->name}' is in {$plant->health_status} health"
                ];
            }
        }

        return $alerts;
    }
} 