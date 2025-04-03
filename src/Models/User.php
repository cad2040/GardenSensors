<?php
namespace App\Models;

class User extends Model {
    protected static $table = 'users';
    protected static $primaryKey = 'user_id';
    protected static $fillable = [
        'username',
        'email',
        'password',
        'role',
        'last_login',
        'created_at',
        'updated_at'
    ];

    public function setPassword(string $password): void {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }

    public function isAdmin(): bool {
        return $this->role === 'admin';
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
        return Sensor::where('user_id', $this->user_id);
    }

    public function plants() {
        return Plant::where('user_id', $this->user_id);
    }

    public function toArray(): array {
        $data = parent::toArray();
        unset($data['password']); // Never expose password hash
        return $data;
    }
} 