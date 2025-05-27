<?php
namespace GardenSensors\Models;

class Pin extends BaseModel {
    protected $table = 'pins';
    protected $primaryKey = 'id';
    protected $fillable = [
        'sensor_id',
        'pin',
        'pinType',
        'description',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // Add property declarations
    protected $id;
    protected $sensor_id;
    protected $pin;
    protected $pinType;
    protected $description;
    protected $status;
    protected $created_at;
    protected $updated_at;

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
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public function getTypes(): array {
        return [
            self::TYPE_PUMP,
            self::TYPE_SENSOR,
            self::TYPE_RELAY
        ];
    }

    public function getStatuses(): array {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_FAULTY
        ];
    }

    public function findBySensor($sensorId) {
        return $this->where('sensor_id', '=', $sensorId);
    }

    public function findByPin($pin) {
        return $this->where('pin', '=', $pin);
    }
} 