<?php

namespace GardenSensors\Services;

use PDO;
use PDOException;
use Exception;

class DatabaseService {
    private array $config;
    private ?PDO $conn = null;

    public function __construct() {
        $this->config = require __DIR__ . '/../Config/database.php';
    }

    public function getConnection(): PDO {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset={$this->config['charset']}";
                $this->conn = new PDO($dsn, $this->config['username'], $this->config['password'], $this->config['options']);
            } catch(PDOException $e) {
                logError("Connection Error: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }

        return $this->conn;
    }

    public function beginTransaction(): bool {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool {
        return $this->getConnection()->rollBack();
    }

    public function lastInsertId(): string {
        return $this->getConnection()->lastInsertId();
    }

    public function prepare(string $query): \PDOStatement {
        return $this->getConnection()->prepare($query);
    }

    public function quoteIdentifier(string $identifier): string {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function quoteValue($value): string {
        return $this->getConnection()->quote($value);
    }
} 