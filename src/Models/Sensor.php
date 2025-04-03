<?php
namespace App\Models;

class Sensor extends Model {
    protected static $table = 'sensors';
    protected static $primaryKey = 'sensor_id';
    protected static $fillable = [
        'user_id',
        'name',
        'type',
        'location',
        'status',
        'last_reading',
        'last_reading_at',
        'battery_level',
        'firmware_version',
        'created_at',
        'updated_at',
        'pin',
        'min_value',
        'max_value',
        'alert_threshold',
        'reading_interval'
    ];
    protected static $hidden = ['created_at', 'updated_at'];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_ERROR = 'error';

    public const TYPE_TEMPERATURE = 'temperature';
    public const TYPE_HUMIDITY = 'humidity';
    public const TYPE_SOIL_MOISTURE = 'soil_moisture';
    public const TYPE_LIGHT = 'light';
    public const TYPE_PH = 'ph';
    public const TYPE_NUTRIENT = 'nutrient';

    public function isActive(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isInMaintenance(): bool {
        return $this->status === self::STATUS_MAINTENANCE;
    }

    public function hasError(): bool {
        return $this->status === self::STATUS_ERROR;
    }

    public function user() {
        return User::find($this->user_id);
    }

    public function readings(int $limit = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM sensor_readings WHERE sensor_id = :sensor_id ORDER BY reading_time DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':sensor_id' => $this->sensor_id,
            ':limit' => $limit
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLatestReading() {
        $readings = $this->readings(1);
        return $readings[0] ?? null;
    }

    public function addReading(float $value, string $unit, ?string $notes = null): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO sensor_readings (sensor_id, value, unit, notes, reading_time)
            VALUES (:sensor_id, :value, :unit, :notes, NOW())
        ");
        
        $result = $stmt->execute([
            ':sensor_id' => $this->sensor_id,
            ':value' => $value,
            ':unit' => $unit,
            ':notes' => $notes
        ]);

        if ($result) {
            $this->last_reading = $value;
            $this->last_reading_at = date('Y-m-d H:i:s');
            $this->save();
        }

        return $result;
    }

    public function getReadingsByDateRange(string $startDate, string $endDate) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM sensor_readings 
            WHERE sensor_id = :sensor_id 
            AND reading_time BETWEEN :start_date AND :end_date
            ORDER BY reading_time ASC
        ");
        
        $stmt->execute([
            ':sensor_id' => $this->sensor_id,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAverageReading(string $startDate, string $endDate) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT AVG(value) as average
            FROM sensor_readings 
            WHERE sensor_id = :sensor_id 
            AND reading_time BETWEEN :start_date AND :end_date
        ");
        
        $stmt->execute([
            ':sensor_id' => $this->sensor_id,
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

    public function updateBatteryLevel(int $level): bool {
        $this->battery_level = $level;
        return $this->save();
    }

    public function updateFirmware(string $version): bool {
        $this->firmware_version = $version;
        return $this->save();
    }

    public function save(): bool {
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public function delete(): bool {
        // Delete all sensor readings first
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM sensor_readings WHERE sensor_id = :sensor_id");
        $stmt->execute([':sensor_id' => $this->sensor_id]);
        
        return parent::delete();
    }

    public static function getTypes(): array {
        return [
            self::TYPE_TEMPERATURE,
            self::TYPE_HUMIDITY,
            self::TYPE_SOIL_MOISTURE,
            self::TYPE_LIGHT,
            self::TYPE_PH,
            self::TYPE_NUTRIENT
        ];
    }

    public static function getStatuses(): array {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_MAINTENANCE,
            self::STATUS_ERROR
        ];
    }

    public function getReadings(int $limit = 100, string $startDate = null, string $endDate = null): array
    {
        $sql = "SELECT * FROM readings WHERE sensor_id = ?";
        $params = [$this->id];

        if ($startDate) {
            $sql .= " AND timestamp >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND timestamp <= ?";
            $params[] = $endDate;
        }

        $sql .= " ORDER BY timestamp DESC LIMIT ?";
        $params[] = $limit;

        return $this->query($sql, $params);
    }

    public function getLatestReading()
    {
        $sql = "SELECT * FROM readings WHERE sensor_id = ? ORDER BY timestamp DESC LIMIT 1";
        $result = $this->query($sql, [$this->id]);
        return $result[0] ?? null;
    }

    public function getAverageReading(string $startDate = null, string $endDate = null)
    {
        $sql = "SELECT AVG(value) as average FROM readings WHERE sensor_id = ?";
        $params = [$this->id];

        if ($startDate) {
            $sql .= " AND timestamp >= ?";
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND timestamp <= ?";
            $params[] = $endDate;
        }

        $result = $this->query($sql, $params);
        return $result[0]['average'] ?? null;
    }

    public function addReading(float $value, string $timestamp = null): array
    {
        $timestamp = $timestamp ?? date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO readings (sensor_id, value, timestamp) VALUES (?, ?, ?)";
        $this->execute($sql, [$this->id, $value, $timestamp]);
        
        return [
            'sensor_id' => $this->id,
            'value' => $value,
            'timestamp' => $timestamp
        ];
    }

    public function isInRange(float $value): bool
    {
        return $value >= $this->min_value && $value <= $this->max_value;
    }

    public function needsAlert(float $value): bool
    {
        return abs($value - $this->alert_threshold) >= $this->alert_threshold;
    }
} 