<?php
namespace GardenSensors\Models;

class FactPlant extends BaseModel {
    protected $table = 'fact_plants';
    protected $primaryKey = 'id';
    protected $fillable = [
        'sensor_id',
        'plant_id',
        'waterAmount',
        'lastWatered',
        'nextWatering',
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // Add property declarations
    protected $id;
    protected $sensor_id;
    protected $plant_id;
    protected $waterAmount;
    protected $lastWatered;
    protected $nextWatering;
    protected $created_at;
    protected $updated_at;

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
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public function findBySensor($sensorId) {
        return $this->where('sensor_id', '=', $sensorId);
    }

    public function findByPlant($plantId) {
        return $this->where('plant_id', '=', $plantId);
    }

    public function getPlantsNeedingWater() {
        $sql = "
            SELECT fp.*, p.name, p.species
            FROM fact_plants fp
            JOIN plants p ON fp.plant_id = p.id
            WHERE fp.nextWatering <= NOW()
            ORDER BY fp.nextWatering ASC
        ";
        return $this->db->query($sql);
    }
} 