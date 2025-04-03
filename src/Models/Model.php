<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

abstract class Model {
    protected static $table;
    protected static $primaryKey = 'id';
    protected static $fillable = [];
    protected $attributes = [];
    protected $original = [];

    public function __construct(array $attributes = []) {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    public function fill(array $attributes): self {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function __get($key) {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value) {
        if (in_array($key, static::$fillable)) {
            $this->attributes[$key] = $value;
        }
    }

    public function __isset($key) {
        return isset($this->attributes[$key]);
    }

    public function toArray(): array {
        return $this->attributes;
    }

    public function isDirty(): bool {
        return $this->attributes !== $this->original;
    }

    public function getChanges(): array {
        return array_diff_assoc($this->attributes, $this->original);
    }

    public static function getTable(): string {
        return static::$table;
    }

    public static function find($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        return new static($result);
    }

    public static function findBy(string $column, $value) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " WHERE {$column} = :value LIMIT 1");
        $stmt->execute([':value' => $value]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        return new static($result);
    }

    public static function all(): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM " . static::$table);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($result) {
            return new static($result);
        }, $results);
    }

    public static function where(string $column, $value): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " WHERE {$column} = :value");
        $stmt->execute([':value' => $value]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($result) {
            return new static($result);
        }, $results);
    }

    public static function whereIn(string $column, array $values): array {
        $db = Database::getInstance();
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " WHERE {$column} IN ({$placeholders})");
        $stmt->execute($values);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($result) {
            return new static($result);
        }, $results);
    }

    public function save(): bool {
        if (!$this->isDirty()) {
            return true;
        }

        $db = Database::getInstance();
        $data = $this->attributes;

        if (isset($data[static::$primaryKey])) {
            // Update
            $id = $data[static::$primaryKey];
            unset($data[static::$primaryKey]);
            
            $sets = [];
            foreach ($data as $key => $value) {
                $sets[] = "{$key} = :{$key}";
            }
            
            $sql = "UPDATE " . static::$table . " SET " . implode(', ', $sets) . " WHERE " . static::$primaryKey . " = :id";
            $data['id'] = $id;
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($data);
        } else {
            // Insert
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_map(function($key) {
                return ":{$key}";
            }, array_keys($data)));
            
            $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$values})";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute($data);
            
            if ($result) {
                $this->attributes[static::$primaryKey] = $db->lastInsertId();
            }
        }

        if ($result) {
            $this->original = $this->attributes;
        }

        return $result;
    }

    public function delete(): bool {
        if (!isset($this->attributes[static::$primaryKey])) {
            return false;
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id");
        return $stmt->execute([':id' => $this->attributes[static::$primaryKey]]);
    }

    public static function create(array $attributes) {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    public function update(array $attributes): bool {
        $this->fill($attributes);
        return $this->save();
    }

    public static function paginate(int $page = 1, int $perPage = 10): array {
        $db = Database::getInstance();
        
        // Get total count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM " . static::$table);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get paginated results
        $stmt = $db->prepare("SELECT * FROM " . static::$table . " LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $items = array_map(function($result) {
            return new static($result);
        }, $results);
        
        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
} 