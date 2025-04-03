<?php
namespace App\Models;

class Plant extends Model {
    protected static $table = 'plants';
    protected static $primaryKey = 'plant_id';
    protected static $fillable = [
        'user_id',
        'name',
        'species',
        'location',
        'planting_date',
        'last_watered',
        'watering_frequency',
        'last_fertilized',
        'fertilizing_frequency',
        'optimal_temperature',
        'optimal_humidity',
        'optimal_soil_moisture',
        'optimal_light',
        'optimal_ph',
        'notes',
        'status',
        'health_status',
        'created_at',
        'updated_at'
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ARCHIVED = 'archived';

    public const HEALTH_EXCELLENT = 'excellent';
    public const HEALTH_GOOD = 'good';
    public const HEALTH_FAIR = 'fair';
    public const HEALTH_POOR = 'poor';
    public const HEALTH_CRITICAL = 'critical';

    public function isActive(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isArchived(): bool {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function user() {
        return User::find($this->user_id);
    }

    public function sensors() {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT s.* 
            FROM sensors s
            JOIN plant_sensors ps ON s.sensor_id = ps.sensor_id
            WHERE ps.plant_id = :plant_id
        ");
        
        $stmt->execute([':plant_id' => $this->plant_id]);
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return array_map(function($result) {
            return new Sensor($result);
        }, $results);
    }

    public function addSensor(Sensor $sensor): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO plant_sensors (plant_id, sensor_id)
            VALUES (:plant_id, :sensor_id)
        ");
        
        return $stmt->execute([
            ':plant_id' => $this->plant_id,
            ':sensor_id' => $sensor->sensor_id
        ]);
    }

    public function removeSensor(Sensor $sensor): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            DELETE FROM plant_sensors 
            WHERE plant_id = :plant_id AND sensor_id = :sensor_id
        ");
        
        return $stmt->execute([
            ':plant_id' => $this->plant_id,
            ':sensor_id' => $sensor->sensor_id
        ]);
    }

    public function updateWatering(): bool {
        $this->last_watered = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function updateFertilizing(): bool {
        $this->last_fertilized = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function updateHealthStatus(string $status): bool {
        $this->health_status = $status;
        return $this->save();
    }

    public function needsWatering(): bool {
        if (!$this->last_watered || !$this->watering_frequency) {
            return false;
        }

        $lastWatered = strtotime($this->last_watered);
        $nextWatering = strtotime("+{$this->watering_frequency} days", $lastWatered);
        return time() >= $nextWatering;
    }

    public function needsFertilizing(): bool {
        if (!$this->last_fertilized || !$this->fertilizing_frequency) {
            return false;
        }

        $lastFertilized = strtotime($this->last_fertilized);
        $nextFertilizing = strtotime("+{$this->fertilizing_frequency} days", $lastFertilized);
        return time() >= $nextFertilizing;
    }

    public function getAge(): int {
        if (!$this->planting_date) {
            return 0;
        }

        $plantingDate = new \DateTime($this->planting_date);
        $now = new \DateTime();
        $interval = $plantingDate->diff($now);
        return $interval->days;
    }

    public function getEnvironmentalConditions(): array {
        $conditions = [];
        foreach ($this->sensors() as $sensor) {
            $reading = $sensor->getLatestReading();
            if ($reading) {
                $conditions[$sensor->type] = [
                    'value' => $reading['value'],
                    'unit' => $reading['unit'],
                    'timestamp' => $reading['reading_time']
                ];
            }
        }
        return $conditions;
    }

    public function checkHealthStatus(): string {
        $conditions = $this->getEnvironmentalConditions();
        $status = self::HEALTH_EXCELLENT;
        $issues = 0;

        foreach ($conditions as $type => $reading) {
            $optimal = "optimal_" . $type;
            if (property_exists($this, $optimal) && $this->$optimal !== null) {
                $difference = abs($reading['value'] - $this->$optimal);
                $threshold = $this->getThreshold($type);
                
                if ($difference > $threshold * 2) {
                    $issues += 2;
                } elseif ($difference > $threshold) {
                    $issues += 1;
                }
            }
        }

        if ($issues >= 6) {
            $status = self::HEALTH_CRITICAL;
        } elseif ($issues >= 4) {
            $status = self::HEALTH_POOR;
        } elseif ($issues >= 2) {
            $status = self::HEALTH_FAIR;
        } elseif ($issues >= 1) {
            $status = self::HEALTH_GOOD;
        }

        return $status;
    }

    private function getThreshold(string $type): float {
        $thresholds = [
            'temperature' => 2.0,    // ±2°C
            'humidity' => 5.0,       // ±5%
            'soil_moisture' => 5.0,  // ±5%
            'light' => 100.0,        // ±100 lux
            'ph' => 0.5             // ±0.5 pH
        ];

        return $thresholds[$type] ?? 1.0;
    }

    public function save(): bool {
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public function delete(): bool {
        // Delete plant-sensor associations first
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM plant_sensors WHERE plant_id = :plant_id");
        $stmt->execute([':plant_id' => $this->plant_id]);
        
        return parent::delete();
    }

    public static function getStatuses(): array {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_ARCHIVED
        ];
    }

    public static function getHealthStatuses(): array {
        return [
            self::HEALTH_EXCELLENT,
            self::HEALTH_GOOD,
            self::HEALTH_FAIR,
            self::HEALTH_POOR,
            self::HEALTH_CRITICAL
        ];
    }
} 