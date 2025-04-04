<?php
namespace GardenSensors\Models;

use PDO;
use DateTime;
use JsonSerializable;
use GardenSensors\Core\Database;
use GardenSensors\Core\Validator;

abstract class BaseModel implements JsonSerializable {
    protected static $db;
    protected static $table;
    protected static $fillable = [];
    protected static $hidden = ['created_at', 'updated_at'];
    protected static $primaryKey = 'id';
    protected $attributes = [];

    public function __construct(array $attributes = []) {
        $this->fill($attributes);
    }

    public function __get($name) {
        return $this->attributes[$name] ?? null;
    }

    public function __set($name, $value) {
        $this->attributes[$name] = $value;
    }

    public function __isset($name) {
        return isset($this->attributes[$name]);
    }

    public static function getConnection() {
        if (!self::$db) {
            $isTest = getenv('TESTING') === 'true';
            $dbName = $isTest ? 'garden_sensors_test' : 'garden_sensors';
            
            $config = [
                'host' => getenv('DB_HOST') ?: 'localhost',
                'database' => $dbName,
                'username' => getenv('DB_USER') ?: 'garden_user',
                'password' => getenv('DB_PASS') ?: ($isTest ? 'test_password' : '')
            ];
            
            try {
                $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
                self::$db = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
                
                // Set the database name explicitly after connection
                if ($isTest) {
                    self::$db->exec("USE garden_sensors_test");
                }
            } catch (\PDOException $e) {
                throw new \PDOException("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$db;
    }

    protected static function quoteIdentifier(string $identifier): string {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public static function find($id) {
        $db = self::getConnection();
        $table = static::quoteIdentifier(static::$table);
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function all() {
        $db = self::getConnection();
        $table = static::quoteIdentifier(static::$table);
        $stmt = $db->prepare("SELECT * FROM {$table}");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create($data) {
        $db = self::getConnection();
        $fields = array_intersect_key($data, array_flip(static::$fillable));
        $columns = implode(', ', array_map([static::class, 'quoteIdentifier'], array_keys($fields)));
        $values = implode(', ', array_fill(0, count($fields), '?'));
        $table = static::quoteIdentifier(static::$table);
        
        $stmt = $db->prepare("INSERT INTO {$table} ({$columns}) VALUES ({$values})");
        $stmt->execute(array_values($fields));
        
        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = self::getConnection();
        $fields = array_intersect_key($data, array_flip(static::$fillable));
        $set = implode(' = ?, ', array_map([static::class, 'quoteIdentifier'], array_keys($fields))) . ' = ?';
        $table = static::quoteIdentifier(static::$table);
        
        $stmt = $db->prepare("UPDATE {$table} SET {$set} WHERE id = ?");
        $values = array_values($fields);
        $values[] = $id;
        
        return $stmt->execute($values);
    }

    public static function delete($id) {
        $db = self::getConnection();
        $table = static::quoteIdentifier(static::$table);
        $stmt = $db->prepare("DELETE FROM {$table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function save(): bool {
        $db = self::getConnection();
        $table = static::quoteIdentifier(static::$table);
        $now = date('Y-m-d H:i:s');
        
        if (!isset($this->attributes['id'])) {
            // Insert
            $this->attributes['created_at'] = $now;
            $this->attributes['updated_at'] = $now;
            
            $columns = array_keys($this->attributes);
            $values = array_fill(0, count($columns), '?');
            $sql = "INSERT INTO {$table} (" . implode(', ', array_map([static::class, 'quoteIdentifier'], $columns)) . ") VALUES (" . implode(', ', $values) . ")";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute(array_values($this->attributes));
            
            if ($result) {
                $this->attributes['id'] = $db->lastInsertId();
            }
            
            return $result;
        } else {
            // Update
            $this->attributes['updated_at'] = $now;
            
            $set = [];
            $values = [];
            
            foreach ($this->attributes as $key => $value) {
                if ($key !== 'id' && $key !== 'created_at') {
                    $set[] = static::quoteIdentifier($key) . " = ?";
                    $values[] = $value;
                }
            }
            
            $values[] = $this->attributes['id'];
            $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE " . static::quoteIdentifier('id') . " = ?";
            $stmt = $db->prepare($sql);
            return $stmt->execute($values);
        }
    }

    public function fill(array $data): self {
        $this->attributes = array_intersect_key($data, array_flip(static::$fillable));
        return $this;
    }

    public function toArray(): array {
        $array = $this->attributes;
        foreach (static::$hidden as $key) {
            unset($array[$key]);
        }
        return $array;
    }

    public function jsonSerialize(): mixed {
        return $this->toArray();
    }

    public function hasMany(string $relatedClass, string $foreignKey, ?string $localKey = null): array {
        $localKey = $localKey ?? static::$primaryKey;
        $db = self::getConnection();
        $table = $relatedClass::$table;
        $stmt = $db->prepare("SELECT * FROM {$table} WHERE {$foreignKey} = ?");
        $stmt->execute([$this->attributes[$localKey]]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
} 