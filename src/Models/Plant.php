<?php
namespace GardenSensors\Models;

class Plant extends BaseModel implements \JsonSerializable {
    protected static $table = 'plants';
    protected static $primaryKey = 'id';
    protected static $fillable = [
        'name',
        'species',
        'min_soil_moisture',
        'max_soil_moisture',
        'watering_frequency'
    ];

    public function sensors() {
        return $this->hasMany(Sensor::class, 'plant_sensors', 'plant_id', 'sensor_id');
    }

    public function addSensor(Sensor $sensor): bool {
        $db = self::getConnection();
        $stmt = $db->prepare("
            INSERT INTO plant_sensors (plant_id, sensor_id, water_amount)
            VALUES (:plant_id, :sensor_id, :water_amount)
        ");
        
        return $stmt->execute([
            ':plant_id' => $this->id,
            ':sensor_id' => $sensor->id,
            ':water_amount' => 100  // Default water amount in ml
        ]);
    }

    public function removeSensor(Sensor $sensor): bool {
        $db = self::getConnection();
        $stmt = $db->prepare("
            DELETE FROM plant_sensors 
            WHERE plant_id = :plant_id AND sensor_id = :sensor_id
        ");
        
        return $stmt->execute([
            ':plant_id' => $this->id,
            ':sensor_id' => $sensor->id
        ]);
    }

    public function updateWatering(): bool {
        $db = self::getConnection();
        $stmt = $db->prepare("
            UPDATE plant_sensors 
            SET last_watered = NOW(), next_watering = DATE_ADD(NOW(), INTERVAL :hours HOUR)
            WHERE plant_id = :plant_id
        ");
        
        return $stmt->execute([
            ':plant_id' => $this->id,
            ':hours' => $this->watering_frequency
        ]);
    }

    public function needsWatering(): bool {
        $db = self::getConnection();
        $stmt = $db->prepare("
            SELECT ps.*, p.min_soil_moisture, p.max_soil_moisture, p.watering_frequency
            FROM plant_sensors ps
            JOIN plants p ON ps.plant_id = p.id
            WHERE ps.plant_id = :plant_id
        ");
        $stmt->execute([':plant_id' => $this->id]);
        $plant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$plant) {
            return false;
        }

        // Check if next watering time has passed
        $nextWatering = strtotime($plant['next_watering']);
        return $nextWatering <= time();
    }

    public function getEnvironmentalConditions(): array {
        $db = self::getConnection();
        $stmt = $db->prepare("
            SELECT s.*, r.value, r.created_at as timestamp
            FROM plant_sensors ps
            JOIN sensors s ON ps.sensor_id = s.id
            JOIN readings r ON s.id = r.sensor_id
            WHERE ps.plant_id = :plant_id
            AND r.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([':plant_id' => $this->id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
        
        return parent::save();
    }

    public static function delete($id): bool {
        $db = self::getConnection();
        
        // Delete related records first
        $stmt = $db->prepare("DELETE FROM plant_sensors WHERE plant_id = ?");
        $stmt->execute([$id]);
        
        // Then delete the plant
        return parent::delete($id);
    }

    public static function findBySensor($sensorId) {
        $db = self::getConnection();
        $stmt = $db->prepare("
            SELECT p.*
            FROM plants p
            JOIN plant_sensors ps ON p.id = ps.plant_id
            WHERE ps.sensor_id = :sensor_id
        ");
        $stmt->execute([':sensor_id' => $sensorId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
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