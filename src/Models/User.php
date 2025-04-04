<?php
namespace GardenSensors\Models;

class User extends BaseModel implements \JsonSerializable {
    protected static $table = 'users';
    protected static $primaryKey = 'user_id';
    protected static $fillable = [
        'username',
        'email',
        'password',
        'role',
        'status',
        'last_login',
        'created_at',
        'updated_at'
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function setPassword(string $password): void {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }

    public function isAdmin(): bool {
        return $this->role === 'admin';
    }

    public function isActive(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function updateStatus(string $status): bool {
        $this->status = $status;
        return $this->save();
    }

    public function updateRole(string $role): bool {
        $this->role = $role;
        return $this->save();
    }

    public static function findByEmail(string $email) {
        return self::findBy('email', $email);
    }

    public static function findByUsername(string $username) {
        return self::findBy('username', $username);
    }

    public function updateLastLogin(): bool {
        $this->last_login = date('Y-m-d H:i:s');
        return $this->save();
    }

    public function save(): bool {
        if (!isset($this->attributes['created_at'])) {
            $this->attributes['created_at'] = date('Y-m-d H:i:s');
        }
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
        
        return parent::save();
    }

    public function sensors() {
        return $this->hasMany(Sensor::class, 'user_id');
    }

    public function plants() {
        return $this->hasMany(Plant::class, 'user_id');
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'last_login' => $this->last_login,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }
} 