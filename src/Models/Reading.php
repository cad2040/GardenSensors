<?php
namespace GardenSensors\Models;

class Reading extends BaseModel {
    protected $table = 'readings';
    protected $primaryKey = 'id';
    protected $fillable = [
        'sensor_id',
        'value',
        'unit',
        'temperature',
        'humidity',
        'created_at'
    ];

    protected $hidden = ['created_at'];

    // Add property declarations
    protected $id;
    protected $sensor_id;
    protected $value;
    protected $unit;
    protected $temperature;
    protected $humidity;
    protected $created_at;

    public function sensor() {
        return Sensor::find($this->sensor_id);
    }

    public function findBySensor($sensorId) {
        return $this->where('sensor_id', '=', $sensorId);
    }

    public function findByDateRange($sensorId, $startDate, $endDate) {
        return $this->where(
            'sensor_id = ? AND created_at BETWEEN ? AND ?',
            [$sensorId, $startDate, $endDate]
        );
    }

    public function getAverage($sensorId, $startDate, $endDate) {
        $readings = $this->findByDateRange($sensorId, $startDate, $endDate);
        if (empty($readings)) {
            return 0;
        }
        
        $sum = array_sum(array_column($readings, 'value'));
        return $sum / count($readings);
    }

    public function batchInsert($readings) {
        $db = $this->db;
        $table = $this->table;
        
        $columns = ['sensor_id', 'value', 'unit', 'temperature', 'humidity', 'created_at'];
        $values = [];
        $params = [];
        
        foreach ($readings as $reading) {
            $values[] = '(?, ?, ?, ?, ?, NOW())';
            $params = array_merge($params, [
                $reading['sensor_id'],
                $reading['value'],
                $reading['unit'] ?? 'units',
                $reading['temperature'],
                $reading['humidity']
            ]);
        }
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES " . implode(', ', $values);
        return $db->execute($sql, $params);
    }

    public function cleanup($daysToKeep) {
        $db = $this->db;
        $table = $this->table;
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        $sql = "DELETE FROM {$table} WHERE created_at < ?";
        return $db->execute($sql, [$cutoffDate]);
    }

    public function save(): bool {
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        
        $result = parent::save();
        
        if ($result) {
            // Update object properties from database
            if (isset($this->attributes['id'])) $this->id = $this->attributes['id'];
            if (isset($this->attributes['sensor_id'])) $this->sensor_id = $this->attributes['sensor_id'];
            if (isset($this->attributes['value'])) $this->value = $this->attributes['value'];
            if (isset($this->attributes['unit'])) $this->unit = $this->attributes['unit'];
            if (isset($this->attributes['temperature'])) $this->temperature = $this->attributes['temperature'];
            if (isset($this->attributes['humidity'])) $this->humidity = $this->attributes['humidity'];
            if (isset($this->attributes['created_at'])) $this->created_at = $this->attributes['created_at'];
        }
        
        return $result;
    }

    public function fill(array $attributes) {
        parent::fill($attributes);
        
        // Set properties from attributes
        if (isset($attributes['id'])) $this->id = $attributes['id'];
        if (isset($attributes['sensor_id'])) $this->sensor_id = $attributes['sensor_id'];
        if (isset($attributes['value'])) $this->value = $attributes['value'];
        if (isset($attributes['unit'])) $this->unit = $attributes['unit'];
        if (isset($attributes['temperature'])) $this->temperature = $attributes['temperature'];
        if (isset($attributes['humidity'])) $this->humidity = $attributes['humidity'];
        if (isset($attributes['created_at'])) $this->created_at = $attributes['created_at'];
    }

    // Getter methods
    public function getId(): ?int {
        return $this->id;
    }

    public function getSensorId(): ?int {
        return $this->sensor_id;
    }

    public function getValue(): ?float {
        return $this->value;
    }

    public function getUnit(): ?string {
        return $this->unit;
    }

    public function getTemperature(): ?float {
        return $this->temperature;
    }

    public function getHumidity(): ?float {
        return $this->humidity;
    }

    public function getCreatedAt(): ?string {
        return $this->created_at;
    }

    // Setter methods
    public function setSensorId(int $sensorId): void {
        $this->sensor_id = $sensorId;
    }

    public function setValue(float $value): void {
        $this->value = $value;
    }

    public function setUnit(string $unit): void {
        $this->unit = $unit;
    }

    public function setTemperature(float $temperature): void {
        $this->temperature = $temperature;
    }

    public function setHumidity(float $humidity): void {
        $this->humidity = $humidity;
    }
} 