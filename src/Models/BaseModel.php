<?php

namespace App\Models;

use PDO;

class BaseModel {
    protected static $db;
    protected static $table;
    protected static $fillable = [];

    public static function getConnection() {
        if (!self::$db) {
            $config = require __DIR__ . '/../Config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            self::$db = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
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
} 