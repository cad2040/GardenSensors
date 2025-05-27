<?php
namespace GardenSensors\Models;

use GardenSensors\Core\Database;
use GardenSensors\Core\Cache;
// use GardenSensors\Core\Logger;

class Plant extends BaseModel implements \JsonSerializable {
    protected $table = 'plants';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'species',
        'min_soil_moisture',
        'max_soil_moisture',
        'watering_frequency',
        'location',
        'min_temperature',
        'max_temperature',
        'status',
        'user_id'
    ];
    protected $hidden = ['created_at', 'updated_at'];

    protected $db;
    protected $cache;
    // protected $logger;
    protected $userId;

    public function __construct($attributes = [], $db = null, $cache = null, $logger = null, $userId = null) {
        if ($attributes instanceof Database) {
            $db = $attributes;
            $attributes = [];
        }
        
        $this->db = $db ?? Database::getInstance();
        $this->cache = $cache ?? Cache::getInstance(__DIR__ . '/../../cache');
        // $this->logger = $logger ?? Logger::getInstance();
        $this->userId = $userId;
        
        parent::__construct($attributes);
    }

    public function sensors() {
        return $this->hasMany(Sensor::class, 'plant_sensors', 'plant_id', 'sensor_id');
    }

    public function addSensor(Sensor $sensor): bool {
        $sql = "
            INSERT INTO plant_sensors (plant_id, sensor_id, water_amount)
            VALUES (:plant_id, :sensor_id, :water_amount)
        ";
        
        return $this->db->execute($sql, [
            ':plant_id' => $this->id,
            ':sensor_id' => $sensor->id,
            ':water_amount' => 100  // Default water amount in ml
        ]);
    }

    public function removeSensor(Sensor $sensor): bool {
        $sql = "
            DELETE FROM plant_sensors 
            WHERE plant_id = :plant_id AND sensor_id = :sensor_id
        ";
        
        return $this->db->execute($sql, [
            ':plant_id' => $this->id,
            ':sensor_id' => $sensor->id
        ]);
    }

    public function updateWatering(): bool {
        $sql = "
            UPDATE plant_sensors 
            SET last_watered = NOW(), next_watering = DATE_ADD(NOW(), INTERVAL :hours HOUR)
            WHERE plant_id = :plant_id
        ";
        
        return $this->db->execute($sql, [
            ':plant_id' => $this->id,
            ':hours' => $this->watering_frequency
        ]);
    }

    public function needsWatering(): bool {
        $sql = "
            SELECT ps.*, p.min_soil_moisture, p.max_soil_moisture, p.watering_frequency
            FROM plant_sensors ps
            JOIN plants p ON ps.plant_id = p.id
            WHERE ps.plant_id = :plant_id
        ";
        $plant = $this->db->query($sql, [':plant_id' => $this->id]);

        if (empty($plant)) {
            return false;
        }

        // Check if next watering time has passed
        $nextWatering = strtotime($plant[0]['next_watering']);
        return $nextWatering <= time();
    }

    public function getEnvironmentalConditions(): array {
        $sql = "
            SELECT s.*, r.value, r.created_at as timestamp
            FROM plant_sensors ps
            JOIN sensors s ON ps.sensor_id = s.id
            JOIN readings r ON s.id = r.sensor_id
            WHERE ps.plant_id = :plant_id
            AND r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";
        return $this->db->query($sql, [':plant_id' => $this->id]);
    }

    public function checkHealthStatus(): string {
        $conditions = $this->getEnvironmentalConditions();
        if (empty($conditions)) {
            return 'unknown';
        }

        $moistureReadings = array_filter($conditions, function($reading) {
            return $reading['type'] === 'moisture';
        });

        if (empty($moistureReadings)) {
            return 'unknown';
        }

        $avgMoisture = array_sum(array_column($moistureReadings, 'value')) / count($moistureReadings);

        if ($avgMoisture < $this->min_soil_moisture) {
            return 'needs_water';
        } elseif ($avgMoisture > $this->max_soil_moisture) {
            return 'too_wet';
        } else {
            return 'healthy';
        }
    }

    public function save(): bool {
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        if ($this->userId) {
            $this->attributes['user_id'] = $this->userId;
        }
        
        $result = parent::save();
        
        if ($result && $this->cache) {
            $this->cache->clear("plant:{$this->id}");
        }
        
        // if ($result && $this->logger) {
        //     $this->logger->info('Plant saved', ['plant_id' => $this->id, 'user_id' => $this->userId]);
        // }
        
        return $result;
    }

    public function delete(
        $id = null
    ): bool {
        $db = $this->db ?? Database::getInstance();
        $plantId = $id ?? $this->id;
        // Delete related records first
        $db->execute("DELETE FROM plant_sensors WHERE plant_id = :plant_id", [':plant_id' => $plantId]);
        // Then delete the plant
        return parent::delete($plantId);
    }

    public static function getStatuses(): array {
        return [
            'healthy',
            'needs_water',
            'too_wet',
            'unknown'
        ];
    }

    public static function findBySensor($sensorId) {
        $db = Database::getInstance();
        $result = $db->query("
            SELECT p.*
            FROM plants p
            JOIN plant_sensors ps ON p.id = ps.plant_id
            WHERE ps.sensor_id = :sensor_id
        ", [':sensor_id' => $sensorId]);
        return !empty($result) ? new static($result[0]) : null;
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'species' => $this->species,
            'min_soil_moisture' => $this->min_soil_moisture,
            'max_soil_moisture' => $this->max_soil_moisture,
            'watering_frequency' => $this->watering_frequency,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'health_status' => $this->checkHealthStatus(),
            'needs_watering' => $this->needsWatering()
        ];
    }

    public static function add(array $data): ?self {
        $plant = new self();
        $plant->fill($data);
        if ($plant->save()) {
            return $plant;
        }
        return null;
    }

    public static function get($id): ?self {
        return self::find($id);
    }

    public function getSensors(): array {
        return $this->sensors();
    }
} 