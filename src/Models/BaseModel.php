<?php
namespace GardenSensors\Models;

use PDO;
use DateTime;
use JsonSerializable;
use GardenSensors\Core\Database;
use GardenSensors\Core\Validator;

abstract class BaseModel implements JsonSerializable {
    protected $table = '';
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $attributes = [];
    protected $original = [];
    protected $db;

    public function __construct(array $attributes = []) {
        $this->db = Database::getInstance();
        $this->fill($attributes);
        $this->original = $this->attributes;
    }

    public function getConnection(): Database {
        return $this->db;
    }

    public function find($id) {
        $sql = sprintf(
            "SELECT * FROM %s WHERE %s = ? LIMIT 1",
            $this->table,
            $this->primaryKey
        );

        $result = $this->db->query($sql, [$id]);
        if (empty($result)) {
            return null;
        }

        $model = new static();
        $model->fill($result[0]);
        $model->original = $model->attributes;
        return $model;
    }

    public function all() {
        $sql = sprintf("SELECT * FROM %s", $this->table);
        $results = $this->db->query($sql);

        $models = [];
        foreach ($results as $result) {
            $model = new static();
            $model->fill($result);
            $model->original = $model->attributes;
            $models[] = $model;
        }

        return $models;
    }

    public function create(array $attributes) {
        $this->fill($attributes);
        $this->save();
        return $this;
    }

    public function update(array $attributes) {
        $this->fill($attributes);
        $this->save();
        return $this;
    }

    public function delete($id = null) {
        if ($id === null) {
            $id = $this->getAttribute($this->primaryKey);
        }
        if ($id) {
            $this->db->execute("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?", [$id]);
        }
        return true;
    }

    public function save() {
        if (empty($this->attributes[$this->primaryKey])) {
            return $this->insert();
        }
        return $this->update();
    }

    protected function insert() {
        $fields = array_keys($this->attributes);
        $values = array_values($this->attributes);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $this->db->execute($sql, $values);
        $this->attributes[$this->primaryKey] = $this->db->lastInsertId();
        $this->original = $this->attributes;

        return true;
    }

    protected function update() {
        $fields = [];
        $values = [];

        foreach ($this->attributes as $field => $value) {
            if ($field !== $this->primaryKey) {
                $fields[] = "$field = ?";
                $values[] = $value;
            }
        }

        $values[] = $this->attributes[$this->primaryKey];

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = ?",
            $this->table,
            implode(', ', $fields),
            $this->primaryKey
        );

        return $this->db->execute($sql, $values);
    }

    public function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->setAttribute($key, $value);
            }
        }
    }

    public function getAttribute($key) {
        return $this->attributes[$key] ?? null;
    }

    public function setAttribute($key, $value) {
        if (in_array($key, $this->fillable)) {
            $this->attributes[$key] = $value;
        }
    }

    public function getDirty() {
        return array_diff_assoc($this->attributes, $this->original);
    }

    public function toArray() {
        $array = [];
        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $array[$key] = $value;
            }
        }
        return $array;
    }

    public function jsonSerialize(): mixed {
        return $this->toArray();
    }

    public function toJson($options = 0) {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function where($field, $operator, $value) {
        $sql = sprintf(
            "SELECT * FROM %s WHERE %s %s ?",
            $this->table,
            $field,
            $operator
        );

        $results = $this->db->query($sql, [$value]);

        $models = [];
        foreach ($results as $result) {
            $model = new static();
            $model->fill($result);
            $model->original = $model->attributes;
            $models[] = $model;
        }

        return $models;
    }

    public function hasMany($related, $foreignKey = null, $localKey = null) {
        $instance = new $related();
        $foreignKey = $foreignKey ?: strtolower(class_basename($this)) . '_id';
        $localKey = $localKey ?: $this->primaryKey;

        return $instance->where($foreignKey, $this->getAttribute($localKey));
    }
} 