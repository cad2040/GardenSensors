<?php
namespace GardenSensors\Models;

use GardenSensors\Core\Database;

class User extends BaseModel implements \JsonSerializable {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'username',
        'email',
        'password_hash',
        'role',
        'status',
        'last_login',
        'created_at',
        'updated_at'
    ];

    protected $hidden = ['password_hash', 'created_at', 'updated_at'];

    // Add property declarations
    protected $id;
    protected $username;
    protected $email;
    protected $password_hash;
    protected $role;
    protected $status;
    protected $last_login;
    protected $created_at;
    protected $updated_at;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public function getId(): ?int {
        return $this->id;
    }

    public function getUsername(): ?string {
        return $this->username;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function getPasswordHash(): ?string {
        return $this->password_hash;
    }

    public function getRole(): ?string {
        return $this->role;
    }

    public function getStatus(): ?string {
        return $this->status;
    }

    public function getLastLogin(): ?string {
        return $this->last_login;
    }

    public function getCreatedAt(): ?string {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?string {
        return $this->updated_at;
    }

    public function setPassword(string $password): void {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password_hash);
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
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $result = $db->query($sql, [':email' => $email]);
        return !empty($result) ? new static($result[0]) : null;
    }

    public static function findByUsername(string $username) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE username = :username LIMIT 1";
        $result = $db->query($sql, [':username' => $username]);
        return !empty($result) ? new static($result[0]) : null;
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