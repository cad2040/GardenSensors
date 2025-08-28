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
        'description',
        'planting_date',
        'harvest_date',
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

    // Property declarations
    protected $id;
    protected $name;
    protected $species;
    protected $description;
    protected $planting_date;
    protected $harvest_date;
    protected $min_soil_moisture;
    protected $max_soil_moisture;
    protected $watering_frequency;
    protected $location;
    protected $min_temperature;
    protected $max_temperature;
    protected $status;
    protected $user_id;
    protected $created_at;
    protected $updated_at;

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
        
        // Validate required fields
        if (!empty($attributes)) {
            if (isset($attributes['name']) && empty($attributes['name'])) {
                throw new \InvalidArgumentException('Plant name cannot be empty');
            }
        }
    }
    
    public function fill(array $attributes) {
        parent::fill($attributes);
        
        // Set properties from attributes
        if (isset($attributes['id'])) $this->id = $attributes['id'];
        if (isset($attributes['name'])) $this->name = $attributes['name'];
        if (isset($attributes['species'])) $this->species = $attributes['species'];
        if (isset($attributes['description'])) $this->description = $attributes['description'];
        if (isset($attributes['planting_date'])) $this->planting_date = $attributes['planting_date'];
        if (isset($attributes['harvest_date'])) $this->harvest_date = $attributes['harvest_date'];
        if (isset($attributes['min_soil_moisture'])) $this->min_soil_moisture = $attributes['min_soil_moisture'];
        if (isset($attributes['max_soil_moisture'])) $this->max_soil_moisture = $attributes['max_soil_moisture'];
        if (isset($attributes['watering_frequency'])) $this->watering_frequency = $attributes['watering_frequency'];
        if (isset($attributes['location'])) $this->location = $attributes['location'];
        if (isset($attributes['min_temperature'])) $this->min_temperature = $attributes['min_temperature'];
        if (isset($attributes['max_temperature'])) $this->max_temperature = $attributes['max_temperature'];
        if (isset($attributes['status'])) $this->status = $attributes['status'];
        if (isset($attributes['user_id'])) $this->user_id = $attributes['user_id'];
        if (isset($attributes['created_at'])) $this->created_at = $attributes['created_at'];
        if (isset($attributes['updated_at'])) $this->updated_at = $attributes['updated_at'];
    }

    public function sensors() {
        $sql = "
            SELECT s.* 
            FROM sensors s
            JOIN fact_plants fp ON s.id = fp.sensor_id
            WHERE fp.plant_id = ?
        ";
        
        $results = $this->db->query($sql, [$this->id]);
        
        $sensors = [];
        foreach ($results as $result) {
            $sensor = new Sensor();
            $sensor->fill($result);
            $sensors[] = $sensor;
        }
        
        return $sensors;
    }

    public function addSensor(Sensor $sensor): bool {
        $sql = "
            INSERT INTO fact_plants (plant_id, sensor_id, waterAmount)
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
            DELETE FROM fact_plants 
            WHERE plant_id = :plant_id AND sensor_id = :sensor_id
        ";
        
        return $this->db->execute($sql, [
            ':plant_id' => $this->id,
            ':sensor_id' => $sensor->id
        ]);
    }

    public function updateWatering(): bool {
        $sql = "
            UPDATE fact_plants 
            SET lastWatered = NOW(), nextWatering = DATE_ADD(NOW(), INTERVAL :hours HOUR)
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
            FROM fact_plants ps
            JOIN plants p ON ps.plant_id = p.id
            WHERE ps.plant_id = :plant_id
        ";
        $plant = $this->db->query($sql, [':plant_id' => $this->id]);

        if (empty($plant)) {
            return false;
        }

        // Check if next watering time has passed
        $nextWatering = strtotime($plant[0]['nextWatering']);
        return $nextWatering <= time();
    }

    public function getEnvironmentalConditions(): array {
        $sql = "
            SELECT s.*, r.value, r.created_at as timestamp
            FROM fact_plants ps
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
        
        if ($result) {
            // Update object properties from database
            if (isset($this->attributes['id'])) $this->id = $this->attributes['id'];
            if (isset($this->attributes['created_at'])) $this->created_at = $this->attributes['created_at'];
            if (isset($this->attributes['updated_at'])) $this->updated_at = $this->attributes['updated_at'];
        }
        
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
        $db->execute("DELETE FROM fact_plants WHERE plant_id = :plant_id", [':plant_id' => $plantId]);
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
            JOIN fact_plants ps ON p.id = ps.plant_id
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
    
    // Getter methods
    public function getId(): ?int {
        return $this->id ?? null;
    }
    
    public function getName(): ?string {
        return $this->name ?? null;
    }
    
    public function getSpecies(): ?string {
        return $this->species ?? null;
    }
    
    public function getDescription(): ?string {
        return $this->description ?? null;
    }
    
    public function getPlantingDate(): ?string {
        return $this->planting_date ?? null;
    }
    
    public function getHarvestDate(): ?string {
        return $this->harvest_date ?? null;
    }
    
    public function getMinSoilMoisture(): ?int {
        return $this->min_soil_moisture ?? null;
    }
    
    public function getMaxSoilMoisture(): ?int {
        return $this->max_soil_moisture ?? null;
    }
    
    public function getWateringFrequency(): ?int {
        return $this->watering_frequency ?? null;
    }
    
    public function getLocation(): ?string {
        return $this->location ?? null;
    }
    
    public function getMinTemperature(): ?int {
        return $this->min_temperature ?? null;
    }
    
    public function getMaxTemperature(): ?int {
        return $this->max_temperature ?? null;
    }
    
    public function getStatus(): ?string {
        return $this->status ?? null;
    }
    
    public function getUserId(): ?int {
        return $this->user_id ?? null;
    }
    
    public function getCreatedAt(): ?string {
        return $this->created_at ?? null;
    }
    
    public function getUpdatedAt(): ?string {
        return $this->updated_at ?? null;
    }
    
    // Setter methods
    public function setName(string $name): void {
        $this->name = $name;
        $this->attributes['name'] = $name;
    }
    
    public function setSpecies(string $species): void {
        $this->species = $species;
        $this->attributes['species'] = $species;
    }
    
    public function setDescription(string $description): void {
        $this->description = $description;
        $this->attributes['description'] = $description;
    }
    
    public function setLocation(string $location): void {
        $this->location = $location;
        $this->attributes['location'] = $location;
    }
    
    public function setStatus(string $status): void {
        $this->status = $status;
        $this->attributes['status'] = $status;
    }
    
        // Static finder methods
    public static function findByUser(int $userId): array {
        $db = Database::getInstance();
        $results = $db->query("SELECT * FROM plants WHERE user_id = ?", [$userId]);
        
        $plants = [];
        foreach ($results as $result) {
            $plant = new Plant();
            $plant->fill($result);
            $plants[] = $plant;
        }
        return $plants;
    }

    public static function findByLocation(string $location): array {
        $db = Database::getInstance();
        $results = $db->query("SELECT * FROM plants WHERE location = ?", [$location]);
        
        $plants = [];
        foreach ($results as $result) {
            $plant = new Plant();
            $plant->fill($result);
            $plants[] = $plant;
        }
        return $plants;
    }

    public static function findByStatus(string $status): array {
        $db = Database::getInstance();
        $results = $db->query("SELECT * FROM plants WHERE status = ?", [$status]);
        
        $plants = [];
        foreach ($results as $result) {
            $plant = new Plant();
            $plant->fill($result);
            $plants[] = $plant;
        }
        return $plants;
    }

    public static function findByDateRange(string $startDate, string $endDate): array {
        $db = Database::getInstance();
        $results = $db->query("SELECT * FROM plants WHERE created_at BETWEEN ? AND ?", [$startDate, $endDate]);
        
        $plants = [];
        foreach ($results as $result) {
            $plant = new Plant();
            $plant->fill($result);
            $plants[] = $plant;
        }
        return $plants;
    }
    
    public function harvest(): bool {
        $this->status = 'harvested';
        $this->harvest_date = date('Y-m-d');
        $this->attributes['status'] = 'harvested';
        $this->attributes['harvest_date'] = date('Y-m-d');
        return $this->save();
    }
} 