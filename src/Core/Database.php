<?php
namespace GardenSensors\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;
    private $inTransaction = false;

    public function __construct() {
        $config = require __DIR__ . '/../Config/database.php';
        $isTest = getenv('APP_ENV') === 'testing';
        $dbName = $isTest ? 'garden_sensors_test' : $config['database'];
        $dsn = "mysql:host={$config['host']};dbname={$dbName};charset={$config['charset']}";
        
        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (PDOException $e) {
            throw new PDOException("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new PDOException("Query failed: " . $e->getMessage());
        }
    }

    public function execute(string $sql, array $params = []): bool {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new PDOException("Execute failed: " . $e->getMessage());
        }
    }

    public function beginTransaction(): bool {
        if (!$this->inTransaction) {
            $this->inTransaction = $this->connection->beginTransaction();
        }
        return $this->inTransaction;
    }

    public function commit(): bool {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->connection->commit();
        }
        return false;
    }

    public function rollback(): bool {
        if ($this->inTransaction) {
            $this->inTransaction = false;
            return $this->connection->rollBack();
        }
        return false;
    }

    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }

    public function quote(string $value): string {
        return $this->connection->quote($value);
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function exec($sql, $params = []): bool
    {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            throw new \Exception("Database error: " . $e->getMessage());
        }
    }
} 