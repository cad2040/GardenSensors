<?php

namespace GardenSensors\Tests\Models;

use GardenSensors\Tests\TestCase;
use GardenSensors\Models\Reading;
use GardenSensors\Models\Sensor;

class ReadingTest extends TestCase
{
    private $reading;
    private $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user with unique username
        $uniqueId = uniqid();
        $this->db->exec("
            INSERT INTO users (username, email, password_hash, role, status, created_at, updated_at)
            VALUES ('testuser_{$uniqueId}', 'test_{$uniqueId}@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', 'user', 'active', NOW(), NOW())
        ");
        
        // Create test sensor
        $this->db->exec("
            INSERT INTO sensors (name, type, description, location, status, created_at, updated_at)
            VALUES ('Soil Moisture Sensor', 'moisture', 'Test sensor', 'Garden Bed 1', 'active', NOW(), NOW())
        ");
        
        $this->sensor = new Sensor([
            'id' => 1,
            'name' => 'Soil Moisture Sensor',
            'type' => 'moisture',
            'description' => 'Test sensor',
            'location' => 'Garden Bed 1',
            'status' => 'active'
        ]);
        
        $this->reading = new Reading([
            'sensor_id' => 1,
            'reading' => 45,
            'temperature' => 25.5,
            'humidity' => 60.0
        ]);
    }

    public function testReadingCreation()
    {
        $this->reading->save();
        
        $this->assertNotNull($this->reading->getId());
        $this->assertEquals(1, $this->reading->getSensorId());
        $this->assertEquals(45, $this->reading->getReading());
        $this->assertEquals(25.5, $this->reading->getTemperature());
        $this->assertEquals(60.0, $this->reading->getHumidity());
    }

    public function testReadingSensorRelationship()
    {
        $this->reading->save();
        
        $sensor = $this->reading->getSensor();
        $this->assertNotNull($sensor);
        $this->assertEquals('Soil Moisture Sensor', $sensor->getName());
    }

    public function testReadingTimestamps()
    {
        $this->reading->save();
        
        $this->assertNotNull($this->reading->getCreatedAt());
    }

    public function testReadingValidation()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        new Reading([
            'sensor_id' => 1,
            'reading' => null
        ]);
    }

    public function testReadingFindBySensor()
    {
        $this->reading->save();
        
        $readings = Reading::findBySensor(1);
        $this->assertCount(1, $readings);
        $this->assertEquals(45, $readings[0]->getReading());
    }

    public function testReadingFindByDateRange()
    {
        $this->reading->save();
        
        $startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        $endDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $readings = Reading::findByDateRange(1, $startDate, $endDate);
        $this->assertCount(1, $readings);
        $this->assertEquals(45, $readings[0]->getReading());
    }

    public function testReadingAverage()
    {
        $this->reading->save();
        
        // Add another reading
        $reading2 = new Reading([
            'sensor_id' => 1,
            'reading' => 55,
            'temperature' => 26.0,
            'humidity' => 65.0
        ]);
        $reading2->save();
        
        $startDate = date('Y-m-d H:i:s', strtotime('-1 day'));
        $endDate = date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $average = Reading::getAverage(1, $startDate, $endDate);
        $this->assertEquals(50, $average);
    }

    public function testReadingDeletion()
    {
        $this->reading->save();
        $id = $this->reading->getId();
        
        $this->reading->delete();
        
        $deleted = Reading::find($id);
        $this->assertNull($deleted);
    }

    public function testReadingBatchInsert()
    {
        $readings = [
            [
                'sensor_id' => 1,
                'reading' => 45,
                'temperature' => 25.5,
                'humidity' => 60.0
            ],
            [
                'sensor_id' => 1,
                'reading' => 55,
                'temperature' => 26.0,
                'humidity' => 65.0
            ]
        ];
        
        $result = Reading::batchInsert($readings);
        $this->assertTrue($result);
        
        $allReadings = Reading::findBySensor(1);
        $this->assertCount(2, $allReadings);
    }

    public function testReadingCleanup()
    {
        // Create old reading
        $oldReading = new Reading([
            'sensor_id' => 1,
            'reading' => 45,
            'temperature' => 25.5,
            'humidity' => 60.0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-31 days'))
        ]);
        $oldReading->save();
        
        // Create new reading
        $newReading = new Reading([
            'sensor_id' => 1,
            'reading' => 55,
            'temperature' => 26.0,
            'humidity' => 65.0
        ]);
        $newReading->save();
        
        // Clean up readings older than 30 days
        Reading::cleanup(30);
        
        $allReadings = Reading::findBySensor(1);
        $this->assertCount(1, $allReadings);
        $this->assertEquals(55, $allReadings[0]->getReading());
    }
} 