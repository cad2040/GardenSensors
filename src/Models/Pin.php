<?php
namespace GardenSensors\Models;

class Pin extends BaseModel {
    protected $table = 'pins';
    protected $primaryKey = 'id';
    protected $fillable = [
        'sensor_id',
        'pin_number',
        'pin',
        'pinType',
        'pin_type',
        'description',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    // Add property declarations
    protected $id;
    protected $sensor_id;
    protected $pin_number;
    protected $pin;
    protected $pinType;
    protected $pin_type;
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

    public function fill(array $attributes) {
        parent::fill($attributes);
        
        // Set properties from attributes
        if (isset($attributes['id'])) $this->id = $attributes['id'];
        if (isset($attributes['sensor_id'])) $this->sensor_id = $attributes['sensor_id'];
        if (isset($attributes['pin_number'])) $this->pin_number = $attributes['pin_number'];
        if (isset($attributes['pin'])) $this->pin = $attributes['pin'];
        if (isset($attributes['pinType'])) $this->pinType = $attributes['pinType'];
        if (isset($attributes['pin_type'])) $this->pin_type = $attributes['pin_type'];
        if (isset($attributes['description'])) $this->description = $attributes['description'];
        if (isset($attributes['status'])) $this->status = $attributes['status'];
        if (isset($attributes['created_at'])) $this->created_at = $attributes['created_at'];
        if (isset($attributes['updated_at'])) $this->updated_at = $attributes['updated_at'];
    }

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
        
        $result = parent::save();
        
        // After save, update properties from attributes
        if (isset($this->attributes['id'])) $this->id = $this->attributes['id'];
        if (isset($this->attributes['sensor_id'])) $this->sensor_id = $this->attributes['sensor_id'];
        if (isset($this->attributes['pin_number'])) $this->pin_number = $this->attributes['pin_number'];
        if (isset($this->attributes['pin'])) $this->pin = $this->attributes['pin'];
        if (isset($this->attributes['pinType'])) $this->pinType = $this->attributes['pinType'];
        if (isset($this->attributes['pin_type'])) $this->pin_type = $this->attributes['pin_type'];
        if (isset($this->attributes['description'])) $this->description = $this->attributes['description'];
        if (isset($this->attributes['status'])) $this->status = $this->attributes['status'];
        if (isset($this->attributes['created_at'])) $this->created_at = $this->attributes['created_at'];
        if (isset($this->attributes['updated_at'])) $this->updated_at = $this->attributes['updated_at'];
        
        return $result;
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

    public function getSensor() {
        return Sensor::find($this->sensor_id);
    }
    
    // Getter methods
    public function getId(): ?int {
        return $this->id ?? null;
    }
    
    public function getSensorId(): ?int {
        return $this->sensor_id ?? null;
    }
    
    public function getPinNumber(): ?int {
        return $this->pin_number ?? null;
    }
    
    public function getPin(): ?string {
        return $this->pin ?? null;
    }
    
    public function getPinType(): ?string {
        return $this->pinType ?? null;
    }
    
    public function getPinType2(): ?string {
        return $this->pin_type ?? null;
    }
    
    public function getDescription(): ?string {
        return $this->description ?? null;
    }
    
    public function getStatus(): ?string {
        return $this->status ?? null;
    }
    
    public function getCreatedAt(): ?string {
        return $this->created_at ?? null;
    }
    
    public function getUpdatedAt(): ?string {
        return $this->updated_at ?? null;
    }
    
    // Setter methods
    public function setSensorId(int $sensorId): void {
        $this->sensor_id = $sensorId;
        $this->attributes['sensor_id'] = $sensorId;
    }
    
    public function setPinNumber(int $pinNumber): void {
        $this->pin_number = $pinNumber;
        $this->attributes['pin_number'] = $pinNumber;
    }
    
    public function setPin(string $pin): void {
        $this->pin = $pin;
        $this->attributes['pin'] = $pin;
    }
    
    public function setPinType(string $pinType): void {
        $this->pinType = $pinType;
        $this->attributes['pinType'] = $pinType;
    }
    
    public function setPinType2(string $pinType): void {
        $this->pin_type = $pinType;
        $this->attributes['pin_type'] = $pinType;
    }
    
    public function setDescription(string $description): void {
        $this->description = $description;
        $this->attributes['description'] = $description;
    }
    
    public function setStatus(string $status): void {
        $this->status = $status;
        $this->attributes['status'] = $status;
    }
} 