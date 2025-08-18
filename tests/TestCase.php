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
        
        // Note: Not clearing data to preserve test data from schema
    }

    protected function tearDown(): void
    {
        // Note: Not clearing data to preserve test data from schema
        parent::tearDown();
    }
} 