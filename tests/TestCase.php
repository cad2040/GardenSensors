<?php
namespace GardenSensors\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use GardenSensors\Core\Database;

class TestCase extends BaseTestCase
{
    protected $db;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set testing environment
        putenv('TESTING=true');
        putenv('DB_DATABASE=garden_sensors');
        
        // Get database instance (this will connect to test database)
        $this->db = Database::getInstance();
        
        // Clear any existing data
        $this->db->execute("SET FOREIGN_KEY_CHECKS = 0");
        $tables = $this->db->query("SHOW TABLES");
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $this->db->execute("TRUNCATE TABLE `$tableName`");
        }
        $this->db->execute("SET FOREIGN_KEY_CHECKS = 1");
    }

    protected function tearDown(): void
    {
        // Clear test data
        if ($this->db) {
            $this->db->execute("SET FOREIGN_KEY_CHECKS = 0");
            $tables = $this->db->query("SHOW TABLES");
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                $this->db->execute("TRUNCATE TABLE `$tableName`");
            }
            $this->db->execute("SET FOREIGN_KEY_CHECKS = 1");
        }
        
        parent::tearDown();
    }
} 