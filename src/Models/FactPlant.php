<?php
namespace GardenSensors\Models;

class FactPlant extends BaseModel {
    protected static $table = 'fact_plants';
    protected static $primaryKey = 'id';
    protected static $fillable = [
        'sensor_id',
        'plant_id',
        'lastWatered',
        'nextWatering',
        'waterAmount'
    ];

    public function sensor() {
        return Sensor::find($this->sensor_id);
    }

    public function plant() {
        return Plant::find($this->plant_id);
    }

    public function updateWatering(): bool {
        $this->lastWatered = date('Y-m-d H:i:s');
        $this->nextWatering = date('Y-m-d H:i:s', strtotime("+{$this->plant()->wateringFrequency} hours"));
        return $this->save();
    }

    public function needsWatering(): bool {
        return $this->nextWatering === null || strtotime($this->nextWatering) <= time();
    }

    public function updateWaterAmount(int $amount): bool {
        $this->waterAmount = $amount;
        return $this->save();
    }

    public function save(): bool {
        if (!isset($this->attributes['inserted'])) {
            $this->attributes['inserted'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public static function findBySensor($sensorId) {
        $db = self::getConnection();
        $sql = "SELECT * FROM fact_plants WHERE sensor_id = :sensor_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':sensor_id' => $sensorId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findByPlant($plantId) {
        $db = self::getConnection();
        $sql = "SELECT * FROM fact_plants WHERE plant_id = :plant_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':plant_id' => $plantId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getPlantsNeedingWater() {
        $db = self::getConnection();
        $sql = "
            SELECT fp.*, p.plant, p.species
            FROM fact_plants fp
            JOIN dim_plants p ON fp.plant_id = p.id
            WHERE fp.nextWatering <= NOW()
            ORDER BY fp.nextWatering ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 