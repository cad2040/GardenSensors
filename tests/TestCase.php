<?php
namespace GardenSensors\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use GardenSensors\Core\Database;

class TestCase extends BaseTestCase
{
    protected $db;
    protected $logFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set testing environment
        putenv('TESTING=true');
        putenv('DB_DATABASE=garden_sensors');
        $this->logFile = sys_get_temp_dir() . '/garden_sensors_test_' . getmypid() . '.log';
        putenv('LOG_FILE=' . $this->logFile);
        
        // Get database instance (this will connect to test database)
        $this->db = Database::getInstance();
        
        // Note: Not clearing data to preserve test data from schema
    }

    protected function tearDown(): void
    {
        if ($this->logFile && file_exists($this->logFile)) {
            @unlink($this->logFile);
        }
        // Note: Not clearing data to preserve test data from schema
        parent::tearDown();
    }
} 