<?php
namespace App\Models;

class Plant extends BaseModel {
    protected static $table = 'dim_plants';
    protected static $primaryKey = 'id';
    protected static $fillable = [
        'plant',
        'species',
        'minSoilMoisture',
        'maxSoilMoisture',
        'wateringFrequency'
    ];

    public function sensors() {
        $db = self::getConnection();
        $sql = "
            SELECT s.* 
            FROM fact_plants fp
            JOIN sensors s ON fp.sensor_id = s.id
            WHERE fp.plant_id = :plant_id
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':plant_id' => $this->id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function addSensor(Sensor $sensor): bool {
        $db = self::getConnection();
        $stmt = $db->prepare("
            INSERT INTO fact_plants (plant_id, sensor_id, waterAmount)
            VALUES (:plant_id, :sensor_id, :waterAmount)
        ");
        
        return $stmt->execute([
            ':plant_id' => $this->id,
            ':sensor_id' => $sensor->id,
            ':waterAmount' => 0  // Default water amount
        ]);
    }

    public function removeSensor(Sensor $sensor): bool {
        $db = self::getConnection();
        $stmt = $db->prepare("
            DELETE FROM fact_plants 
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
            UPDATE fact_plants 
            SET lastWatered = NOW(),
                nextWatering = DATE_ADD(NOW(), INTERVAL wateringFrequency HOUR)
            WHERE plant_id = :plant_id
        ");
        
        return $stmt->execute([':plant_id' => $this->id]);
    }

    public function needsWatering(): bool {
        $db = self::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM fact_plants
            WHERE plant_id = :plant_id
            AND (nextWatering IS NULL OR nextWatering <= NOW())
        ");
        
        $stmt->execute([':plant_id' => $this->id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    public function getEnvironmentalConditions(): array {
        $conditions = [];
        foreach ($this->sensors() as $sensor) {
            $reading = $sensor->getLatestReading();
            if ($reading) {
                $conditions[$sensor->plot_type] = [
                    'value' => $reading['reading'],
                    'temperature' => $reading['temperature'],
                    'humidity' => $reading['humidity'],
                    'timestamp' => $reading['inserted']
                ];
            }
        }
        return $conditions;
    }

    public function checkHealthStatus(): string {
        $conditions = $this->getEnvironmentalConditions();
        $moisture = $conditions['moisture']['value'] ?? null;
        
        if ($moisture === null) {
            return 'unknown';
        }
        
        if ($moisture < $this->minSoilMoisture) {
            return 'needs_water';
        } elseif ($moisture > $this->maxSoilMoisture) {
            return 'too_wet';
        } else {
            return 'healthy';
        }
    }

    public function save(): bool {
        if (!isset($this->attributes['inserted'])) {
            $this->attributes['inserted'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public function delete(): bool {
        // Delete plant-sensor associations first
        $db = self::getConnection();
        $stmt = $db->prepare("DELETE FROM fact_plants WHERE plant_id = :plant_id");
        $stmt->execute([':plant_id' => $this->id]);
        
        return parent::delete();
    }

    public static function findBySensor($sensorId) {
        $db = self::getConnection();
        $sql = "
            SELECT p.* 
            FROM dim_plants p
            JOIN fact_plants fp ON p.id = fp.plant_id
            WHERE fp.sensor_id = :sensor_id
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':sensor_id' => $sensorId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 