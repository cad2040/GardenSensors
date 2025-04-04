<?php
namespace GardenSensors\Models;

class Pin extends BaseModel {
    protected static $table = 'pins';
    protected static $primaryKey = 'id';
    protected static $fillable = [
        'sensor_id',
        'pin',
        'pinType',
        'description',
        'status'
    ];

    public const TYPE_PUMP = 'pump';
    public const TYPE_SENSOR = 'sensor';
    public const TYPE_RELAY = 'relay';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FAULTY = 'faulty';

    public function sensor() {
        return Sensor::find($this->sensor_id);
    }

    public function isActive(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isInactive(): bool {
        return $this->status === self::STATUS_INACTIVE;
    }

    public function isFaulty(): bool {
        return $this->status === self::STATUS_FAULTY;
    }

    public function updateStatus(string $status): bool {
        $this->status = $status;
        return $this->save();
    }

    public function save(): bool {
        if (!isset($this->attributes['inserted'])) {
            $this->attributes['inserted'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public static function getTypes(): array {
        return [
            self::TYPE_PUMP,
            self::TYPE_SENSOR,
            self::TYPE_RELAY
        ];
    }

    public static function getStatuses(): array {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_FAULTY
        ];
    }

    public static function findBySensor($sensorId) {
        $db = self::getConnection();
        $sql = "SELECT * FROM pins WHERE sensor_id = :sensor_id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':sensor_id' => $sensorId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function findByPin($pin) {
        $db = self::getConnection();
        $sql = "SELECT * FROM pins WHERE pin = :pin";
        $stmt = $db->prepare($sql);
        $stmt->execute([':pin' => $pin]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
} 