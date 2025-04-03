<?php
namespace App\Utils;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection = null;
    private $config = [];

    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadConfig(): void {
        $this->config = require __DIR__ . '/../../config/database.php';
    }

    private function connect(): void {
        $connection = $this->config['connections'][$this->config['default']];

        try {
            $dsn = sprintf(
                '%s:host=%s;port=%s;dbname=%s;charset=%s',
                $connection['driver'],
                $connection['host'],
                $connection['port'],
                $connection['database'],
                $connection['charset']
            );

            $this->connection = new PDO(
                $dsn,
                $connection['username'],
                $connection['password'],
                $connection['options']
            );
        } catch (PDOException $e) {
            Logger::error('Database connection failed: ' . $e->getMessage());
            throw new PDOException('Database connection failed');
        }
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool {
        return $this->connection->commit();
    }

    public function rollBack(): bool {
        return $this->connection->rollBack();
    }

    public function prepare(string $query): \PDOStatement {
        return $this->connection->prepare($query);
    }

    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }

    public function query(string $query, array $params = []): \PDOStatement {
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetch(string $query, array $params = []): ?array {
        $stmt = $this->query($query, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function fetchAll(string $query, array $params = []): array {
        $stmt = $this->query($query, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function execute(string $query, array $params = []): int {
        $stmt = $this->query($query, $params);
        return $stmt->rowCount();
    }
} 