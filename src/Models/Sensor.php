<?php
namespace GardenSensors\Models;

use GardenSensors\Core\Database;
use GardenSensors\Core\Cache;
use GardenSensors\Core\Logger;

class Sensor extends BaseModel {
    protected $table = 'sensors';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'type',
        'location',
        'description',
        'status',
        'last_reading',
        'min_threshold',
        'max_threshold',
        'unit',
        'plot_url',
        'plot_type',
        'user_id'
    ];
    protected $hidden = ['created_at', 'updated_at'];

    // Add property declarations
    protected $id;
    protected $name;
    protected $type;
    protected $location;
    protected $description;
    protected $status;
    protected $last_reading;
    protected $min_threshold;
    protected $max_threshold;
    protected $unit;
    protected $plot_url;
    protected $plot_type;
    protected $user_id;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_NORMAL = 'normal';
    public const STATUS_BELOW_THRESHOLD = 'below_threshold';
    public const STATUS_ABOVE_THRESHOLD = 'above_threshold';

    public const PLOT_TYPE_MOISTURE = 'moisture';
    public const PLOT_TYPE_TEMPERATURE = 'temperature';
    public const PLOT_TYPE_HUMIDITY = 'humidity';

    private $cache;
    private $logger;
    private $userId;

    public function __construct(array $attributes = [], ?Database $db = null, ?Cache $cache = null, ?Logger $logger = null, ?int $userId = null) {
        parent::__construct($attributes, $db);
        $this->cache = $cache;
        $this->logger = $logger;
        $this->userId = $userId;
    }

    public function isActive(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isInMaintenance(): bool {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getType(): ?string {
        return $this->type;
    }

    public function getLocation(): ?string {
        return $this->location;
    }

    public function getMinThreshold(): ?float {
        return $this->min_threshold;
    }

    public function getMaxThreshold(): ?float {
        return $this->max_threshold;
    }

    public function getUnit(): ?string {
        return $this->unit;
    }

    public function getLastReading(): ?float {
        return $this->last_reading;
    }

    public function getLastReadingTime(): ?string {
        return $this->last_reading;
    }

    public function calculateStatus(float $reading): string {
        if ($reading < $this->min_threshold) {
            return self::STATUS_BELOW_THRESHOLD;
        } elseif ($reading > $this->max_threshold) {
            return self::STATUS_ABOVE_THRESHOLD;
        }
        return self::STATUS_NORMAL;
    }

    public function updateReading(float $value, string $timestamp): bool {
        $this->last_reading = $timestamp;
        return $this->save();
    }

    public function pins() {
        return $this->db->query("SELECT * FROM pins WHERE sensor_id = :sensor_id", [':sensor_id' => $this->id]);
    }

    public function readings(int $limit = null) {
        $sql = "SELECT * FROM readings WHERE sensor_id = :sensor_id ORDER BY created_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }
        
        return $this->db->query($sql, [
            ':sensor_id' => $this->id,
            ':limit' => $limit
        ]);
    }

    public function getLatestReading() {
        $readings = $this->readings(1);
        return !empty($readings) ? $readings[0] : null;
    }

    public function addReading(float $value, ?float $temperature = null, ?float $humidity = null): bool {
        $sql = "
            INSERT INTO readings (sensor_id, value, unit, temperature, humidity)
            VALUES (:sensor_id, :value, :unit, :temperature, :humidity)
        ";
        
        $result = $this->db->execute($sql, [
            ':sensor_id' => $this->id,
            ':value' => $value,
            ':unit' => $this->type === self::PLOT_TYPE_TEMPERATURE ? '°C' : '%',
            ':temperature' => $temperature,
            ':humidity' => $humidity
        ]);

        if ($result) {
                    $this->last_reading = date('Y-m-d H:i:s');
            $this->save();
        }

        return $result;
    }

    public function getReadingsByDateRange(string $startDate, string $endDate) {
        return $this->db->query("
            SELECT * FROM readings 
            WHERE sensor_id = :sensor_id 
            AND created_at BETWEEN :start_date AND :end_date
            ORDER BY created_at ASC
        ", [
            ':sensor_id' => $this->id,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
    }

    public function getAverageReading(string $startDate, string $endDate) {
        $result = $this->db->query("
            SELECT AVG(value) as average
            FROM readings 
            WHERE sensor_id = :sensor_id 
            AND created_at BETWEEN :start_date AND :end_date
        ", [
            ':sensor_id' => $this->id,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $result[0]['average'] ?? null;
    }

    public function updateStatus(string $status): bool {
        $this->status = $status;
        return $this->save();
    }

    public function plants() {
        return $this->db->query("
            SELECT p.* 
            FROM plant_sensors ps
            JOIN plants p ON ps.plant_id = p.id
            WHERE ps.sensor_id = :sensor_id
        ", [':sensor_id' => $this->id]);
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
            $this->cache->clear("sensor:{$this->id}");
        }
        
        if ($result && $this->logger) {
            $this->logger->info('Sensor saved', ['sensor_id' => $this->id, 'user_id' => $this->userId]);
        }
        
        return $result;
    }

    public function delete(
        $id = null
    ): bool {
        $db = $this->db ?? Database::getInstance();
        $sensorId = $id ?? $this->id;
        // Delete all readings first
        $db->execute("DELETE FROM readings WHERE sensor_id = :sensor_id", [':sensor_id' => $sensorId]);
        // Delete all pins
        $db->execute("DELETE FROM pins WHERE sensor_id = :sensor_id", [':sensor_id' => $sensorId]);
        // Delete plant associations
        $db->execute("DELETE FROM plant_sensors WHERE sensor_id = :sensor_id", [':sensor_id' => $sensorId]);
        return parent::delete($sensorId);
    }

    public static function getPlotTypes(): array {
        return [
            self::PLOT_TYPE_MOISTURE,
            self::PLOT_TYPE_TEMPERATURE,
            self::PLOT_TYPE_HUMIDITY
        ];
    }

    public static function getStatuses(): array {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_MAINTENANCE,
            self::STATUS_NORMAL,
            self::STATUS_BELOW_THRESHOLD,
            self::STATUS_ABOVE_THRESHOLD
        ];
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'location' => $this->location,
            'description' => $this->description,
            'status' => $this->status,
            'last_reading' => $this->last_reading,

            'min_threshold' => $this->min_threshold,
            'max_threshold' => $this->max_threshold,
            'unit' => $this->unit,
            'plot_url' => $this->plot_url,
            'plot_type' => $this->plot_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
} 