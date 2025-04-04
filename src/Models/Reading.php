<?php
namespace GardenSensors\Models;

class Reading extends BaseModel {
    protected static $table = 'readings';
    protected static $primaryKey = 'id';
    protected static $fillable = [
        'sensor_id',
        'reading',
        'temperature',
        'humidity'
    ];

    public function sensor() {
        return Sensor::find($this->sensor_id);
    }

    public static function findBySensor($sensorId) {
        return self::where('sensor_id = ?', [$sensorId]);
    }

    public static function findByDateRange($sensorId, $startDate, $endDate) {
        return self::where(
            'sensor_id = ? AND inserted BETWEEN ? AND ?',
            [$sensorId, $startDate, $endDate]
        );
    }

    public static function getAverage($sensorId, $startDate, $endDate) {
        $readings = self::findByDateRange($sensorId, $startDate, $endDate);
        if (empty($readings)) {
            return 0;
        }
        
        $sum = array_sum(array_column($readings, 'reading'));
        return $sum / count($readings);
    }

    public static function batchInsert($readings) {
        $db = self::getConnection();
        $table = static::quoteIdentifier(static::$table);
        
        $columns = ['sensor_id', 'reading', 'temperature', 'humidity', 'inserted', 'updated'];
        $values = [];
        $params = [];
        
        foreach ($readings as $reading) {
            $values[] = '(?, ?, ?, ?, NOW(), NOW())';
            $params = array_merge($params, [
                $reading['sensor_id'],
                $reading['reading'],
                $reading['temperature'],
                $reading['humidity']
            ]);
        }
        
        $sql = "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES " . implode(', ', $values);
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public static function cleanup($daysToKeep) {
        $db = self::getConnection();
        $table = static::quoteIdentifier(static::$table);
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        $sql = "DELETE FROM {$table} WHERE inserted < ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute([$cutoffDate]);
    }
} 