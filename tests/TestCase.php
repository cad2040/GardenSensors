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
        
        // Get database instance
        $this->db = Database::getInstance();
        
        // Create test database and tables
        $this->db->exec("DROP DATABASE IF EXISTS garden_sensors_test");
        $this->db->exec("CREATE DATABASE garden_sensors_test");
        $this->db->exec("USE garden_sensors_test");
        
        // Read and execute schema file
        $schema = file_get_contents(__DIR__ . '/database.sql');
        // Remove the USE statement to avoid syntax error
        $schema = preg_replace('/USE\s+garden_sensors_test\s*;/i', '', $schema);
        // Split the schema into individual statements and execute each
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $this->db->exec($statement);
            }
        }
    }

    protected function tearDown(): void
    {
        // Drop test database
        $this->db->exec("DROP DATABASE IF EXISTS garden_sensors_test");
        
        parent::tearDown();
    }
} 