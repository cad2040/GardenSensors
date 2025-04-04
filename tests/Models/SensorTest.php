<?php
namespace GardenSensors\Tests\Models;

use PHPUnit\Framework\TestCase;
use GardenSensors\Models\Sensor;
use GardenSensors\Core\Database;

class SensorTest extends TestCase
{
    private $sensor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sensor = new Sensor([
            'id' => 1,
            'name' => 'Soil Moisture Sensor',
            'type' => 'moisture',
            'location' => 'Garden Bed 1',
            'min_threshold' => 20,
            'max_threshold' => 80,
            'unit' => '%',
            'last_reading' => 45,
            'last_reading_time' => '2023-04-03 12:00:00'
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