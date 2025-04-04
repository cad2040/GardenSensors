<?php

namespace GardenSensors\Services;

use PDO;
use PDOException;
use Exception;

class DatabaseService {
    private string $host = DB_HOST;
    private string $db_name = DB_NAME;
    private string $username = DB_USER;
    private string $password = DB_PASS;
    private ?PDO $conn = null;

    public function getConnection(): PDO {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
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