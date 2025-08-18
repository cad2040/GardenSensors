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

    public function fill(array $attributes) {
        parent::fill($attributes);
        
        // Set properties from attributes
        if (isset($attributes['id'])) $this->id = $attributes['id'];
        if (isset($attributes['sensor_id'])) $this->sensor_id = $attributes['sensor_id'];
        if (isset($attributes['plant_id'])) $this->plant_id = $attributes['plant_id'];
        if (isset($attributes['waterAmount'])) $this->waterAmount = $attributes['waterAmount'];
        if (isset($attributes['lastWatered'])) $this->lastWatered = $attributes['lastWatered'];
        if (isset($attributes['nextWatering'])) $this->nextWatering = $attributes['nextWatering'];
        if (isset($attributes['created_at'])) $this->created_at = $attributes['created_at'];
        if (isset($attributes['updated_at'])) $this->updated_at = $attributes['updated_at'];
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
    
    // Getter methods
    public function getId(): ?int {
        return $this->id;
    }
    
    public function getSensorId(): ?int {
        return $this->sensor_id;
    }
    
    public function getPlantId(): ?int {
        return $this->plant_id;
    }
    
    public function getWaterAmount(): ?int {
        return $this->waterAmount;
    }
    
    public function getLastWatered(): ?string {
        return $this->lastWatered;
    }
    
    public function getNextWatering(): ?string {
        return $this->nextWatering;
    }
    
    public function getCreatedAt(): ?string {
        return $this->created_at;
    }
    
    public function getUpdatedAt(): ?string {
        return $this->updated_at;
    }
    
    // Setter methods
    public function setSensorId(int $sensorId): void {
        $this->sensor_id = $sensorId;
        $this->attributes['sensor_id'] = $sensorId;
    }
    
    public function setPlantId(int $plantId): void {
        $this->plant_id = $plantId;
        $this->attributes['plant_id'] = $plantId;
    }
    
    public function setWaterAmount(int $waterAmount): void {
        $this->waterAmount = $waterAmount;
        $this->attributes['waterAmount'] = $waterAmount;
    }
    
    public function setLastWatered(string $lastWatered): void {
        $this->lastWatered = $lastWatered;
        $this->attributes['lastWatered'] = $lastWatered;
    }
    
    public function setNextWatering(string $nextWatering): void {
        $this->nextWatering = $nextWatering;
        $this->attributes['nextWatering'] = $nextWatering;
    }
} 