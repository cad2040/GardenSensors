<?php
namespace App\Models;

use App\Utils\Database;
use PDO;
use Exception;

abstract class Model {
    protected static $table;
    protected static $primaryKey = 'id';
    protected static $fillable = [];
    protected static $hidden = [];
    protected $attributes = [];
    protected $original = [];
    protected PDO $db;

    public function __construct(array $attributes = []) {
        global $config;

        try {
            $this->db = new PDO(
                "mysql:host={$config['database']['host']};dbname={$config['database']['database']};charset={$config['database']['charset']}",
                $config['database']['username'],
                $config['database']['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }

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

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?");
        $stmt->execute([$id]);
        return $this->hideFields($stmt->fetch());
    }

    public function all(array $conditions = [], array $orderBy = []): array {
        $sql = "SELECT * FROM " . static::$table;
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $field => $value) {
                $where[] = "$field = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if (!empty($orderBy)) {
            $orders = [];
            foreach ($orderBy as $field => $direction) {
                $orders[] = "$field $direction";
            }
            $sql .= " ORDER BY " . implode(', ', $orders);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map([$this, 'hideFields'], $stmt->fetchAll());
    }

    public function create(array $data) {
        $data = $this->filterFillable($data);
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            static::$table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));

        return $this->find($this->db->lastInsertId());
    }

    public function update($id, array $data) {
        $data = $this->filterFillable($data);
        $fields = array_keys($data);
        $set = array_map(function($field) {
            return "$field = ?";
        }, $fields);

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            static::$table,
            implode(', ', $set),
            static::$primaryKey
        );

        $values = array_values($data);
        $values[] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        return $this->find($id);
    }

    public function delete($id): bool {
        $stmt = $this->db->prepare("DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = ?");
        return $stmt->execute([$id]);
    }

    public static function findBy(string $column, $value) {
        $stmt = $this->db->prepare("SELECT * FROM " . static::$table . " WHERE {$column} = :value LIMIT 1");
        $stmt->execute([':value' => $value]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            return null;
        }
        
        return new static($result);
    }

    public static function where(string $column, $value): array {
        $stmt = $this->db->prepare("SELECT * FROM " . static::$table . " WHERE {$column} = :value");
        $stmt->execute([':value' => $value]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($result) {
            return new static($result);
        }, $results);
    }

    public static function whereIn(string $column, array $values): array {
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $stmt = $this->db->prepare("SELECT * FROM " . static::$table . " WHERE {$column} IN ({$placeholders})");
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
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($data);
        } else {
            // Insert
            $columns = implode(', ', array_keys($data));
            $values = implode(', ', array_map(function($key) {
                return ":{$key}";
            }, array_keys($data)));
            
            $sql = "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$values})";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($data);
            
            if ($result) {
                $this->attributes[static::$primaryKey] = $this->db->lastInsertId();
            }
        }

        if ($result) {
            $this->original = $this->attributes;
        }

        return $result;
    }

    public static function paginate(int $page = 1, int $perPage = 10): array {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM " . static::$table);
        $stmt->execute();
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Calculate offset
        $offset = ($page - 1) * $perPage;
        
        // Get paginated results
        $stmt = $this->db->prepare("SELECT * FROM " . static::$table . " LIMIT :limit OFFSET :offset");
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

    protected function filterFillable(array $data): array {
        return array_intersect_key($data, array_flip(static::$fillable));
    }

    protected function hideFields($data) {
        if (!$data) {
            return $data;
        }
        return array_diff_key($data, array_flip(static::$hidden));
    }

    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }

    public function commit(): bool {
        return $this->db->commit();
    }

    public function rollBack(): bool {
        return $this->db->rollBack();
    }

    protected function query(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): int {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
} 