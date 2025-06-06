<?php
namespace GardenSensors\Tests\Models;

use GardenSensors\Tests\TestCase;
use GardenSensors\Models\Sensor;

class SensorTest extends TestCase
{
    private $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->db->exec("
            INSERT INTO users (username, email, password, role, status, created_at, updated_at)
            VALUES ('testuser', 'test@example.com', 'password', 'user', 'active', NOW(), NOW())
        ");
        
        // Create test sensor
        $this->db->exec("
            INSERT INTO sensors (name, type, description, location, status, min_threshold, max_threshold, unit, user_id, created_at, updated_at)
            VALUES ('Soil Moisture Sensor', 'moisture', 'Test sensor', 'Garden Bed 1', 'active', 20, 80, '%', 1, NOW(), NOW())
        ");
        
        $this->sensor = new Sensor([
            'id' => 1,
            'name' => 'Soil Moisture Sensor',
            'type' => 'moisture',
            'description' => 'Test sensor',
            'location' => 'Garden Bed 1',
            'status' => 'active',
            'min_threshold' => 20,
            'max_threshold' => 80,
            'unit' => '%',
            'last_reading' => 45,
            'last_reading_time' => '2023-04-03 12:00:00',
            'user_id' => 1
        ]);
    }

    public function testSensorInitialization()
    {
        $this->assertEquals(1, $this->sensor->getId());
        $this->assertEquals('Soil Moisture Sensor', $this->sensor->getName());
        $this->assertEquals('moisture', $this->sensor->getType());
        $this->assertEquals('Garden Bed 1', $this->sensor->getLocation());
        $this->assertEquals(20, $this->sensor->getMinThreshold());
        $this->assertEquals(80, $this->sensor->getMaxThreshold());
        $this->assertEquals('%', $this->sensor->getUnit());
        $this->assertEquals(45, $this->sensor->getLastReading());
        $this->assertEquals('2023-04-03 12:00:00', $this->sensor->getLastReadingTime());
    }

    public function testSensorStatusCalculation()
    {
        // Test normal status
        $this->assertEquals('normal', $this->sensor->calculateStatus(45));
        
        // Test below threshold
        $this->assertEquals('below_threshold', $this->sensor->calculateStatus(15));
        
        // Test above threshold
        $this->assertEquals('above_threshold', $this->sensor->calculateStatus(85));
    }

    public function testSensorReadingUpdate()
    {
        $newReading = 60;
        $newTime = '2023-04-03 13:00:00';
        
        $this->sensor->updateReading($newReading, $newTime);
        
        $this->assertEquals($newReading, $this->sensor->getLastReading());
        $this->assertEquals($newTime, $this->sensor->getLastReadingTime());
    }
} 