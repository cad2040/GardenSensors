<?php
namespace App\Models;

class Sensor extends Model {
    protected static $table = 'sensors';
    protected static $primaryKey = 'id';
    protected static $fillable = [
        'sensor',
        'description',
        'location',
        'status',
        'last_reading',
        'plot_url',
        'plot_type'
    ];
    protected static $hidden = ['inserted', 'updated'];

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

    public function pins() {
        $db = Database::getInstance();
        $sql = "SELECT * FROM pins WHERE sensor_id = :sensor_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':sensor_id' => $this->id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function readings(int $limit = null) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM readings WHERE sensor_id = :sensor_id ORDER BY inserted DESC";
        
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

    public function addReading(float $reading, ?float $temperature = null, ?float $humidity = null): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO readings (sensor_id, reading, temperature, humidity, inserted)
            VALUES (:sensor_id, :reading, :temperature, :humidity, NOW())
        ");
        
        $result = $stmt->execute([
            ':sensor_id' => $this->id,
            ':reading' => $reading,
            ':temperature' => $temperature,
            ':humidity' => $humidity
        ]);

        if ($result) {
            $this->last_reading = $reading;
            $this->save();
        }

        return $result;
    }

    public function getReadingsByDateRange(string $startDate, string $endDate) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM readings 
            WHERE sensor_id = :sensor_id 
            AND inserted BETWEEN :start_date AND :end_date
            ORDER BY inserted ASC
        ");
        
        $stmt->execute([
            ':sensor_id' => $this->id,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAverageReading(string $startDate, string $endDate) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT AVG(reading) as average
            FROM readings 
            WHERE sensor_id = :sensor_id 
            AND inserted BETWEEN :start_date AND :end_date
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
        $db = Database::getInstance();
        $sql = "
            SELECT p.* 
            FROM fact_plants fp
            JOIN dim_plants p ON fp.plant_id = p.id
            WHERE fp.sensor_id = :sensor_id
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute([':sensor_id' => $this->id]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function save(): bool {
        if (!isset($this->attributes['inserted'])) {
            $this->attributes['inserted'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public function delete(): bool {
        // Delete all readings first
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM readings WHERE sensor_id = :sensor_id");
        $stmt->execute([':sensor_id' => $this->id]);
        
        // Delete all pins
        $stmt = $db->prepare("DELETE FROM pins WHERE sensor_id = :sensor_id");
        $stmt->execute([':sensor_id' => $this->id]);
        
        // Delete plant associations
        $stmt = $db->prepare("DELETE FROM fact_plants WHERE sensor_id = :sensor_id");
        $stmt->execute([':sensor_id' => $this->id]);
        
        return parent::delete();
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
} 