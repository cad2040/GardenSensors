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
        
        return parent::save();
    }
} 