<?php
namespace GardenSensors\Models;

class Sensor extends BaseModel {
    protected static $table = 'sensors';
    protected static $primaryKey = 'id';
    protected static $fillable = [
        'name',
        'type',
        'location',
        'description',
        'status',
        'last_reading',
        'plot_url',
        'plot_type'
    ];
    protected static $hidden = ['created_at', 'updated_at'];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MAINTENANCE = 'maintenance';

    public const PLOT_TYPE_MOISTURE = 'moisture';
    public const PLOT_TYPE_TEMPERATURE = 'temperature';
    public const PLOT_TYPE_HUMIDITY = 'humidity';

    public function isActive(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isInMaintenance(): bool {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    public function getId(): int {
        return $this->id;
    }

    public function calculateStatus(): string {
        $latestReading = $this->getLatestReading();
        if (!$latestReading) {
            return self::STATUS_INACTIVE;
        }

        $lastReadingTime = strtotime($latestReading['created_at']);
        $now = time();
        $hoursSinceLastReading = ($now - $lastReadingTime) / 3600;

        if ($hoursSinceLastReading > 24) {
            return self::STATUS_MAINTENANCE;
        }

        return self::STATUS_ACTIVE;
    }

    public function updateReading(float $value, ?float $temperature = null, ?float $humidity = null): bool {
        return $this->addReading($value, $temperature, $humidity);
    }

    public function pins() {
        $db = self::getConnection();
        $sql = "SELECT * FROM pins WHERE sensor_id = :sensor_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':sensor_id' => $this->id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function readings(int $limit = null) {
        $db = self::getConnection();
        $sql = "SELECT * FROM readings WHERE sensor_id = :sensor_id ORDER BY created_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':sensor_id' => $this->id,
            ':limit' => $limit
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLatestReading() {
        $readings = $this->readings(1);
        return $readings[0] ?? null;
    }

    public function addReading(float $value, ?float $temperature = null, ?float $humidity = null): bool {
        $db = self::getConnection();
        $stmt = $db->prepare("
            INSERT INTO readings (sensor_id, value, unit, temperature, humidity)
            VALUES (:sensor_id, :value, :unit, :temperature, :humidity)
        ");
        
        $result = $stmt->execute([
            ':sensor_id' => $this->id,
            ':value' => $value,
            ':unit' => $this->type === self::PLOT_TYPE_TEMPERATURE ? '°C' : '%',
            ':temperature' => $temperature,
            ':humidity' => $humidity
        ]);

        if ($result) {
            $this->last_reading = $value;
            $this->save();
        }

        return $result;
    }

    public function getReadingsByDateRange(string $startDate, string $endDate) {
        $db = self::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM readings 
            WHERE sensor_id = :sensor_id 
            AND created_at BETWEEN :start_date AND :end_date
            ORDER BY created_at ASC
        ");
        
        $stmt->execute([
            ':sensor_id' => $this->id,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAverageReading(string $startDate, string $endDate) {
        $db = self::getConnection();
        $stmt = $db->prepare("
            SELECT AVG(value) as average
            FROM readings 
            WHERE sensor_id = :sensor_id 
            AND created_at BETWEEN :start_date AND :end_date
        ");
        
        $stmt->execute([
            ':sensor_id' => $this->id,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result['average'] ?? null;
    }

    public function updateStatus(string $status): bool {
        $this->status = $status;
        return $this->save();
    }

    public function plants() {
        $db = self::getConnection();
        $sql = "
            SELECT p.* 
            FROM plant_sensors ps
            JOIN plants p ON ps.plant_id = p.id
            WHERE ps.sensor_id = :sensor_id
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':sensor_id' => $this->id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function save(): bool {
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public static function delete($id): bool {
        // Delete all readings first
        $db = self::getConnection();
        $stmt = $db->prepare("DELETE FROM readings WHERE sensor_id = :sensor_id");
        $stmt->execute([':sensor_id' => $id]);
        
        // Delete all pins
        $stmt = $db->prepare("DELETE FROM pins WHERE sensor_id = :sensor_id");
        $stmt->execute([':sensor_id' => $id]);
        
        // Delete plant associations
        $stmt = $db->prepare("DELETE FROM plant_sensors WHERE sensor_id = :sensor_id");
        $stmt->execute([':sensor_id' => $id]);
        
        return parent::delete($id);
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
            self::STATUS_MAINTENANCE
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'location' => $this->location,
            'description' => $this->description,
            'status' => $this->status,
            'last_reading' => $this->last_reading,
            'plot_url' => $this->plot_url,
            'plot_type' => $this->plot_type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
} 