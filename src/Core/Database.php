<?php
namespace GardenSensors\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;
    private $inTransaction = false;
    private const TIMEOUT = 5; // 5 second timeout

    public function __construct($connection = null) {
        if ($connection) {
            $this->connection = $connection;
            return;
        }
        
        $config = require __DIR__ . '/../Config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
        
        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $config['options']);
            $this->connection->setAttribute(PDO::ATTR_TIMEOUT, self::TIMEOUT);
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
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            $result = $this->connection->beginTransaction();
            if ($result) {
                $this->inTransaction = true;
            }
            return $result;
        }
        return false;
    }

    public function commit(): bool {
        if ($this->inTransaction) {
            $result = $this->connection->commit();
            if ($result) {
                $this->inTransaction = false;
            }
            return $result;
        }
        return false;
    }

    public function rollback(): bool {
        if ($this->inTransaction) {
            $result = $this->connection->rollBack();
            if ($result) {
                $this->inTransaction = false;
            }
            return $result;
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